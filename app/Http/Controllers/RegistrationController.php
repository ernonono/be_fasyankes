<?php

namespace App\Http\Controllers;

use App\Exports\ExportRegistration;
use App\Models\Doctor;
use App\Models\Registration;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Carbon;
use App\Exports\DoctorScheduleExport;

class RegistrationController extends Controller
{
    public function getSummaryRegistrationByDoctor(Request $request)
    {
        return Excel::download(new ExportRegistration(
            $request->doctor_id,
            $request->start_date,
            $request->end_date
        ), 'registrations.xlsx');
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role == 'patient') {
            // Ambil semua registrasi pasien, diurutkan untuk perhitungan queue_number
            // Urutkan berdasarkan appointment_date ASC, lalu created_at ASC untuk memastikan urutan antrian yang benar
            $allPatientRegistrations = Registration::with(['patient.user', 'doctor.poli', 'medical_records'])
                ->orderBy('appointment_date', 'asc') // Urutan penting untuk perhitungan antrian harian
                ->orderBy('created_at', 'asc')
                ->get();

            $previousAppointmentDate = null;
            $queueNumber = 1;

            // Hitung dan tambahkan queue_number ke setiap registrasi
            foreach ($allPatientRegistrations as $registration) {
                if ($registration->appointment_date != $previousAppointmentDate) {
                    $queueNumber = 1; // Reset nomor antrian untuk tanggal yang berbeda
                    $previousAppointmentDate = $registration->appointment_date;
                }
                $registration->queue_number = $queueNumber;
                $queueNumber++;
            }

            $loggedInPatientRegistrations = $allPatientRegistrations->where('patient_id', $user->patient_id);

            // Sekarang pisahkan registrasi berdasarkan status dan urutkan kembali untuk tampilan riwayat (terbaru dulu)
            $registrationsBelumSelesai = $loggedInPatientRegistrations->where('status', 'Belum Selesai')
                ->sortByDesc('created_at') // Urutkan sesuai kebutuhan di Riwayat.jsx
                ->values(); // Reset keys

            $registrationsSelesai = $loggedInPatientRegistrations->where('status', 'Selesai')
                ->sortByDesc('created_at')
                ->values();

            $registrationsDibatalkan = $loggedInPatientRegistrations->where('status', 'Dibatalkan')
                ->sortByDesc('created_at')
                ->values();

            return response()->json([
                'selesai' => $registrationsSelesai,
                'belum_selesai' => $registrationsBelumSelesai,
                'dibatalkan' => $registrationsDibatalkan
            ], 200);
        }


        $patient_name = $request->query('patient_name');
        $poli_id = $request->query('poli_id');
        $start_date = $request->query('start_date');
        $end_date = $request->query('end_date');

        $registrations = Registration::with(['patient', 'doctor.poli', 'medical_records'])
            ->when($patient_name, function ($query) use ($patient_name) {
                return $query->whereHas('patient', function ($query) use ($patient_name) {
                    return $query->where('name', 'like', "%$patient_name%");
                });
            })
            ->when($poli_id, function ($query) use ($poli_id) {
                return $query->whereHas('doctor', function ($query) use ($poli_id) {
                    return $query->where('poli_id', $poli_id);
                });
            })
            ->when($start_date, function ($query) use ($start_date) {
                return $query->whereDate('created_at', '>=', $start_date);
            })
            ->when($end_date, function ($query) use ($end_date) {
                return $query->whereDate('created_at', '<=', $end_date);
            })
            ->where('type', 'appointment')
            // sort by appointment date
            ->orderBy('created_at', 'asc')
            ->get();

        // Logika perhitungan queue_number untuk role selain patient (admin/dokter)
        $previousAppoinmentDate = null;
        $queueNumber = 1;

        foreach ($registrations as $registration) {
            if ($registration->appointment_date != $previousAppoinmentDate) {
                $queueNumber = 1;
                $previousAppoinmentDate = $registration->appointment_date;
            }
            $registration->queue_number = $queueNumber;
            $queueNumber++;
        }
        $registrations = $registrations->sortByDesc('created_at')->values();

        return response()->json($registrations, 200);
    }


    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $reg  = Registration::where('id', $id)
            ->where('status', 'Belum Selesai')
            ->first();

        if (! $reg) {
            return response()->json(['message' => 'Tidak dapat membatalkan pendaftaran ini.'], 400);
        }

        $reg->status = 'Dibatalkan';
        $reg->save();

        return response()->json(['message' => 'Pendaftaran berhasil dibatalkan.'], 200);
    }



    public function indexAgenda(Request $request)
    {
        $user = $request->user();

        if ($user->role == 'patient') {
            $registrations = Registration::with(['patient.user', 'doctor.poli', 'medical_records'])->where('patient_id', $user->patient_id)->get();
            return response()->json($registrations, 200);
        }

        $patient_name = $request->query('patient_name');
        $poli_id = $request->query('poli_id');
        $start_date = $request->query('start_date');
        $end_date = $request->query('end_date');

        $registrations = Registration::with(['patient', 'doctor.poli', 'medical_records'])
            ->when($patient_name, function ($query) use ($patient_name) {
                return $query->whereHas('patient', function ($query) use ($patient_name) {
                    return $query->where('name', 'like', "%$patient_name%");
                });
            })
            ->when($poli_id, function ($query) use ($poli_id) {
                return $query->whereHas('doctor', function ($query) use ($poli_id) {
                    return $query->where('poli_id', $poli_id);
                });
            })
            ->when($start_date, function ($query) use ($start_date) {
                return $query->whereDate('appointment_date', '>=', $start_date);
            })
            ->when($end_date, function ($query) use ($end_date) {
                return $query->whereDate('appointment_date', '<=', $end_date);
            })
            ->get();

        return response()->json($registrations, 200);
    }


    public function store(Request $request)
    {
        $dateOnly = Carbon::parse($request->appointment_date)->toDateString();

        // Validasi apakah pasien sudah punya janji di tanggal tersebut (dengan dokter manapun)
        $existRegistration = Registration::whereDate('appointment_date', $dateOnly)
            ->where('patient_id', $request->patient_id)
            ->whereNotIn('status', ['Dibatalkan', 'Selesai'])
            ->first();

        if ($existRegistration) {
            return response()->json([
                'error' => true,
                'error_message' => 'Kamu sudah mempunyai janji pada tanggal tersebut, hanya diperbolehkan satu janji per hari.'
            ], 400);
        }

        $registration = Registration::create($request->all());
        return response()->json($registration, 201);
    }

    public function show(Registration $registration)
    {
        return $registration;
    }

    public function update(Request $request, Registration $registration)
    {
        $registration->update($request->all());
        return response()->json($registration, 200);
    }

    public function destroy(Registration $registration)
    {
        $registration->delete();
        return response()->json(null, 204);
    }

    public function getRegistrationByDoctor(Request $request)
    {
        $user = $request->user();

        $patient_name = $request->query('patient_name');
        $start_date = $request->query('start_date');
        $end_date = $request->query('end_date');

        $registrations = Registration::with(['patient', 'doctor.poli', 'medical_records'])
            ->where('doctor_id', $user->doctor_id)
            ->where('type', 'appointment')
            ->when($patient_name, function ($query) use ($patient_name) {
                return $query->whereHas('patient', function ($query) use ($patient_name) {
                    return $query->where('name', 'like', "%$patient_name%");
                });
            })
            ->when($start_date, function ($query) use ($start_date) {
                return $query->whereDate('appointment_date', '>=', $start_date);
            })
            ->when($end_date, function ($query) use ($end_date) {
                return $query->whereDate('appointment_date', '<=', $end_date);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $previousAppoinmentDate = null;
        $queueNumber = 1;

        foreach ($registrations as $registration) {
            if ($registration->appointment_date != $previousAppoinmentDate) {
                $queueNumber = 1;
                $previousAppoinmentDate = $registration->appointment_date;
            }
            $registration->queue_number = $queueNumber;
            $queueNumber++;
        }
        $registrations = $registrations->sortByDesc('created_at')->values();

        return response()->json($registrations, 200);

        // Baris return kedua ini redundan dan tidak akan pernah dieksekusi.
        // return response()->json($registrations, 200);
    }
    public function getRegistrationByDoctorAgenda(Request $request)
    {
        $user = $request->user();
        $registrations = Registration::with(['patient', 'doctor.poli', 'medical_records'])->where('doctor_id', $user->doctor_id)->get();
        return response()->json($registrations, 200);
    }
    public function getRegistrationByDoctorAgendaById($dokter_id)
    {
        $registrations = Registration::where('doctor_id', $dokter_id)->get();
        return response()->json($registrations, 200);
    }

    public function getDetailRegistrationByDoctor(Request $request, Registration $registration)
    {
        $user = $request->user();
        $data = Registration::with(['patient', 'doctor.poli'])->where('doctor_id', $user->doctor_id)->where('type', 'appointment')->where('id', $registration->id)->first();
        return response()->json($data, 200);
    }

    public function getDetailRegistrationByDoctorAgenda(Request $request, Registration $registration)
    {
        $user = $request->user();
        $data = Registration::with(['patient', 'doctor.poli'])->where('doctor_id', $user->doctor_id)->where('id', $registration->id)->first();
        return response()->json($data, 200);
    }

    public function getRegistrationQuotaByHour(Request $request)
    {
        $doctor_id = $request->query('doctor_id');
        $date = $request->query('date');

        $doctor = Doctor::find($doctor_id);
        $doctor_quota = $doctor->quota;

        $quota_08 = $doctor_quota;
        $quota_10 = $doctor_quota;
        $quota_13 = $doctor_quota;
        $quota_15 = $doctor_quota;

        $date_08 = $date . ' 08:00:00';
        $date_10 = $date . ' 10:00:00';
        $date_13 = $date . ' 13:00:00';
        $date_15 = $date . ' 15:00:00';

        $registrations_08 = Registration::where('doctor_id', $doctor_id)->where('appointment_date', $date_08)->where('status', '!=', 'Dibatalkan')->count();
        $registrations_10 = Registration::where('doctor_id', $doctor_id)->where('appointment_date', $date_10)->where('status', '!=', 'Dibatalkan')->count();
        $registrations_13 = Registration::where('doctor_id', $doctor_id)->where('appointment_date', $date_13)->where('status', '!=', 'Dibatalkan')->count();
        $registrations_15 = Registration::where('doctor_id', $doctor_id)->where('appointment_date', $date_15)->where('status', '!=', 'Dibatalkan')->count();

        $quota_08 = $quota_08 - $registrations_08;
        $quota_10 = $quota_10 - $registrations_10;
        $quota_13 = $quota_13 - $registrations_13;
        $quota_15 = $quota_15 - $registrations_15;

        return response()->json([
            8 => $quota_08,
            10 => $quota_10,
            13 => $quota_13,
            15 => $quota_15,
        ], 200);
    }

    public function exportDoctorSchedule(Request $request)
    {
        $start = $request->input('start_date');
        $end = $request->input('end_date');

        return Excel::download(new DoctorScheduleExport($start, $end), 'jadwal_dokter.xlsx');
    }
}

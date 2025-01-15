<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function index(Request $request)
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
            ->where('type', 'appointment')
            ->get();

        return response()->json($registrations, 200);
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
        $registrations = Registration::with(['patient', 'doctor.poli', 'medical_records'])->where('doctor_id', $user->doctor_id)->where('type', 'appointment')->get();
        return response()->json($registrations, 200);
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
}

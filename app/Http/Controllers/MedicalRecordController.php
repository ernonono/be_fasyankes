<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Registration;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    public function index(Request $request)
    {
        $patientId = $request->query('patient_id');
        $medicalRecords = MedicalRecord::when($patientId, function ($query) use ($patientId) {
            return $query->where('patient_id', $patientId);
        })->orderBy('created_at', 'desc')->get();

        return response()->json($medicalRecords, 200);
    }

    public function store(Request $request)
    {
        $data = $request->all();

        $registration = Registration::find($request->registration_id);
        if (!$registration) {
            return response()->json(['message' => 'Registration not found'], 404);
        }

        // get latest medical record with the same 'rm_number' format = 'RMYYMMXXXXX'
        $latestMedicalRecord = MedicalRecord::where('rm_number', 'like', 'RM' . date('ym') . '%')->latest()->first();
        $rm_number = 'RM' . date('ym') . '00001';
        if ($latestMedicalRecord) {
            $rm_number = 'RM' . date('ym') . str_pad((int)substr($latestMedicalRecord->rm_number, 6) + 1, 5, '0', STR_PAD_LEFT);
        }

        $data['rm_number'] = $rm_number;

        $medicalrecord = MedicalRecord::create($data);

        // update status registration
        $registration->status = 'Selesai';
        $registration->save();

        return response()->json($medicalrecord, 201);
    }

    public function show(MedicalRecord $medicalrecord)
    {
        $data = MedicalRecord::with(['patient', 'doctor.poli', 'registration'])->find($medicalrecord->id);
        return response()->json($data, 200);
    }

    public function getMedicalRecordByRegistration($registration_id)
    {
        $data = Registration::with(['medical_records', 'doctor.poli', 'patient.user'])->find($registration_id);
        return response()->json($data, 200);
    }

    public function update(Request $request, MedicalRecord $medicalrecord)
    {
        $medicalrecord->update($request->all());
        return response()->json($medicalrecord, 200);
    }

    public function destroy(MedicalRecord $medicalrecord)
    {
        // update status registration if there is no medical record
        $registration = Registration::find($medicalrecord->registration_id);
        $medicalrecords = MedicalRecord::where('registration_id', $medicalrecord->registration_id)->get();

        if ($medicalrecords->count() == 1) { // if there is only one medical record
            $registration->status = 'Belum Selesai';
            $registration->save();
        }

        $medicalrecord->delete();

        return response()->json(null, 204);
    }
}

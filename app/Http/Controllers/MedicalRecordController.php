<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Registration;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    public function index()
    {
        return response()->json(MedicalRecord::all(), 200);
    }

    public function store(Request $request)
    {
        $registration = Registration::find($request->registration_id);
        if (!$registration) {
            return response()->json(['message' => 'Registration not found'], 404);
        }


        $medicalrecord = MedicalRecord::create($request->all());

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

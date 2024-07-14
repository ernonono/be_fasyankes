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
            $registrations = Registration::with(['patient', 'doctor.poli', 'medical_records'])->where('patient_id', $user->patient_id)->get();
            return response()->json($registrations, 200);
        }

        $registrations = Registration::with(['patient', 'doctor.poli'])->get();
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
        $registrations = Registration::with(['patient', 'doctor.poli', 'medical_records'])->where('doctor_id', $user->doctor_id)->get();
        return response()->json($registrations, 200);
    }

    public function getDetailRegistrationByDoctor(Request $request, Registration $registration)
    {
        $user = $request->user();
        $data = Registration::with(['patient', 'doctor.poli'])->where('doctor_id', $user->doctor_id)->where('id', $registration->id)->first();
        return response()->json($data, 200);
    }
}

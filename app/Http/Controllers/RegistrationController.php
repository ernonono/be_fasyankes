<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function index()
    {
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
}

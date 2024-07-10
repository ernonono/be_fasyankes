<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index()
    {
        return response()->json(Patient::all(), 200);
    }

    public function store(Request $request)
    {
        $patient = Patient::create($request->all());
        return response()->json($patient, 201);
    }

    public function show(Patient $patient)
    {
        return $product;
    }

    public function update(Request $request, Patient $patient)
    {
        $patient->update($request->all());
        return response()->json($patient, 200);
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();
        return response()->json(null,204);
}
}

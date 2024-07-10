<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $poli_id = $request->query('poli_id');

        $dokters = Doctor::when($poli_id, function ($query, $poli_id) {
            return $query->where('poli_id', '=', $poli_id);
        })->get();

        return response()->json($dokters, 200);
    }

    public function store(Request $request)
    {
        $doctor = Doctor::create($request->all());
        return response()->json($doctor, 201);
    }

    public function show(Doctor $doctor)
    {
        return $product;
    }

    public function update(Request $request, Doctor $doctor)
    {
        $doctor->update($request->all());
        return response()->json($doctor, 200);
    }

    public function destroy(Doctor $doctor)
    {
        $doctor->delete();
        return response()->json(null,204);
}
}

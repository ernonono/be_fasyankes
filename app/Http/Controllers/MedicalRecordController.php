<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    public function index()
    {
        return response()->json(MedicalRecord::all(), 200);
    }

    public function store(Request $request)
    {
        $medicalrecord = MedicalRecord::create($request->all());
        return response()->json($poli, 201);
    }

    public function show(MedicalRecord $medicalrecord)
    {
        return $product;
    }

    public function update(Request $request, MEdicalRecord $medicalrecord)
    {
        $medicalrecord->update($request->all());
        return response()->json($medicalrecord, 200);
    }

    public function destroy(MedicalRecord $medicalrecord)
    {
        $medicalrecord->delete();
        return response()->json(null,204);
}
}

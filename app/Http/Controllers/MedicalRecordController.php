<?php

namespace App\Http\Controllers;

use App\Models\Medical_Record;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    public function index()
    {
        return response()->json(Medical_Record::all(), 200);
    }

    public function store(Request $request)
    {
        $medicalrecord = Medical_Record::create($request->all());
        return response()->json($medicalrecord, 201);
    }

    public function show(Medical_Record $medicalrecord)
    {
        return $medicalrecord;
    }

    public function update(Request $request, Medical_Record $medicalrecord)
    {
        $medicalrecord->update($request->all());
        return response()->json($medicalrecord, 200);
    }

    public function destroy(Medical_Record $medicalrecord)
    {
        $medicalrecord->delete();
        return response()->json(null, 204);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Registration;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getCardData()
    {
        $total_patient = Patient::count();
        $total_registration = Registration::count();
        $total_doctor = Doctor::count();

        $data = [
            'total_patient' => $total_patient,
            'total_registration' => $total_registration,
            'total_doctor' => $total_doctor,
        ];

        return response()->json($data, 200);
    }

    public function getChartData()
    {
        $registrations = Registration::selectRaw('count(*) as total, date(appointment_date) as date')
            ->where('type', 'appointment')
            ->groupBy('date')
            ->get();

        $data = [
            'labels' => $registrations->pluck('date'),
            'data' => $registrations->pluck('total'),
        ];

        return response()->json($data, 200);
    }
}

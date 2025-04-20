<?php

namespace App\Exports;

use App\Models\Registration;
use Maatwebsite\Excel\Concerns\FromCollection;

class ExportRegistration implements FromCollection
{
    protected $doctor_id;
    protected $start_date;
    protected $end_date;

    public function __construct($doctor_id, $start_date, $end_date)
    {
        $this->doctor_id = $doctor_id;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $doctor_id = $this->doctor_id;
        $start_date = $this->start_date;
        $end_date = $this->end_date;


        $registrations = Registration::with(['patient', 'doctor.poli', 'medical_records'])
            ->where('doctor_id', $doctor_id)
            ->where('type', 'appointment')
            ->when($doctor_id, function ($query) use ($doctor_id) {
                return $query->where('doctor_id', $doctor_id);
            })
            ->when($start_date, function ($query) use ($start_date) {
                return $query->whereDate('appointment_date', '>=', $start_date);
            })
            ->when($end_date, function ($query) use ($end_date) {
                return $query->whereDate('appointment_date', '<=', $end_date);
            })
            ->orderBy('appointment_date', 'desc')
            ->get();

        return collect($registrations)->map(function ($registration) {
            return [
                'id' => $registration->id,
                'patient_name' => $registration->patient->name,
                'doctor_name' => $registration->doctor->name,
                'poli_name' => $registration->doctor->poli->name,
                'appointment_date' => $registration->appointment_date,
                'status' => $registration->status,
                'description' => $registration->description,
            ];
        });
    }
}

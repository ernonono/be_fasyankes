<?php

namespace App\Exports;

use App\Models\Registration;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class DoctorScheduleExport implements FromCollection, WithHeadings
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate . ' 00:00:00';
        $this->endDate = $endDate . ' 23:59:59';
    }

    public function collection()
{
    return Registration::with(['doctor.user', 'doctor.poli', 'patient'])
        ->whereBetween('appointment_date', [
            $this->startDate, // ✅ ini yang benar
            $this->endDate,   // ✅ ini yang benar
        ])
        ->get()
        ->map(function ($item, $index) {
            return [
                'No'               => $index + 1,
                'Pasien'           => $item->patient->name ?? '-',
                'Dokter'           => $item->doctor->user->name ?? '-',
                'Poli'             => $item->doctor->poli->name ?? '-',
                'Tanggal Daftar'   => \Carbon\Carbon::parse($item->appointment_date)->format('Y-m-d H:i'),
                'Status'           => $item->status ?? '-',
            ];
        });
}


    public function headings(): array
    {
        return ['No', 'Pasien', 'Dokter', 'Poli', 'Tanggal Daftar', 'Status'];
    }
}

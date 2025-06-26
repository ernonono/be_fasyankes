<?php

namespace App\Exports;

use App\Models\Registration;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DoctorScheduleExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return Registration::with(['doctor.user', 'doctor.poli', 'patient'])
            ->where('type', 'appointment')
            ->whereDate('appointment_date', '>=', $this->startDate)
            ->whereDate('appointment_date', '<=', $this->endDate)
            ->get()
            ->map(function ($item, $index) {
                return [
                    'No'      => $index + 1,
                    'Pasien'  => optional($item->patient)->name ?? '-',
                    'Dokter'  => optional($item->doctor->user)->name ?? '-',
                    'Poli'    => optional($item->doctor->poli)->name ?? '-',
                    'Tanggal' => Carbon::parse($item->appointment_date)->format('Y-m-d'),
                    'Status'  => $item->status,
                ];
            });
    }

    public function headings(): array
    {
        return ['No', 'Pasien', 'Dokter', 'Poli', 'Tanggal', 'Status'];
    }

    // Bold untuk header
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    // Atur lebar kolom manual (opsional)
    public function columnWidths(): array
    {
        return [
            'A' => 5,   // No
            'B' => 25,  // Pasien
            'C' => 25,  // Dokter
            'D' => 20,  // Poli
            'E' => 18,  // Tanggal
            'F' => 15,  // Status
        ];
    }
}

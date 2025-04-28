<?php

namespace App\Exports;

use App\Models\Registration;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;


class ExportRegistration implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $doctor_id;
    protected $start_date;
    protected $end_date;

    public function __construct($doctor_id, $start_date, $end_date)
    {
        $this->doctor_id  = $doctor_id;
        $this->start_date = $start_date;
        $this->end_date   = $end_date;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $doctor_id  = $this->doctor_id;
        $start_date = $this->start_date;
        $end_date   = $this->end_date;

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

        return collect($registrations)->map(function ($registration, $index) {
            return [
                'no'               => $index + 1,
                'patient_name'     => $registration->patient->name,
                'doctor_name'      => $registration->doctor->name,
                'poli_name'        => $registration->doctor->poli->name,
                'appointment_date' => $registration->appointment_date,
                'status'           => $registration->status,
                'description'      => $registration->description,
                'medical_records'  => $registration->medical_records->map(function ($record) {
                    // Ambil nama-nama obat dari drug_code
                    $drugNames = [];
                    if (!empty($record->drug_code)) {
                        $decoded = json_decode($record->drug_code, true);
                        if (is_array($decoded)) {
                            $drugNames = collect($decoded)->pluck('name')->toArray();
                        }
                    }
                    return
                        'Diagnosa : ' . ($record->diagnosis ?? '-') . "\n" .
                        'Gejala   : ' . ($record->symptomps ?? '-') . "\n".
                        'Catatan  : ' . ($record->prescription ?? '-') . "\n" .
                        'Obat     : ' . (empty($drugNames) ? '-' : implode(', ', $drugNames)). "\n";
                })->implode("\n\n"),
            ];
        });
    }
    
     // Baris pertama sebagai header kolom di Excel
    public function headings(): array
    {
        return [
            'No',
            'Nama Pasien',
            'Nama Dokter',
            'Poli',
            'Tanggal Daftar',
            'Status',
            'Deskripsi',
            'Rekam Medis',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Buat header bold
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        // Buat header background warna kuning
        $sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF00');

        // Buat wrap text di kolom 'Rekam Medis' (kolom H)
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('H2:H' . $highestRow)->getAlignment()->setWrapText(true);

        // Buat border semua cell
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle('A1:H' . $highestRow)->applyFromArray($styleArray);

        return [];
    }
}

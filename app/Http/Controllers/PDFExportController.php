<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;

class PDFExportController extends Controller
{
    public function exportPdf($id)
    {

        $data = MedicalRecord::find($id);

        if (!$data) {
            // Jika data tidak ditemukan, kirim respons JSON error (ini bagus)
            return response()->json(['message' => 'Rekam medis tidak ditemukan untuk ID ini.'], 404);
        }

        $drugList = json_decode($data->drug_code);
        // Tambahkan pengecekan jika drug_code bukan JSON valid
        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::error("Invalid JSON in drug_code for MedicalRecord ID: {$id}");
            $drugList = []; // Atur sebagai array kosong agar tidak error saat di-loop di view
        }

        $pdf = Pdf::loadView('exports.instruksi-obat', [
            'data' => $data,
            'drugList' => $drugList,
        ]);

        // <-- UBAH BAGIAN INI UNTUK MENGEMBALIKAN PDF
        $fileName = 'instruksi_pengambilan_obat_' . $id . '.pdf'; // Nama file dengan ID
        return $pdf->download($fileName); // <-- Ini akan membuat PDF dan memicu unduhan
}
};

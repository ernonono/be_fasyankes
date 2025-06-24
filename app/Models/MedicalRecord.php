<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasFactory;
    protected $fillable = ['patient_id', 'drug_code', 'rm_number', 'doctor_id', 'date', 'symptomps', 'diagnosis', 'prescription', 'registration_id', 'dosis',];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
    public function registration()
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;
    protected $fillable = ['patient_id', 'doctor_id', 'doctor', 'registry_date', 'is_canceled', 'appointment_date', 'payment_type', 'status', 'description'];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
    public function medical_records()
    {
        return $this->hasMany(Medical_Record::class);
    }
}

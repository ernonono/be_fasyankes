<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;
    protected $fillable = ['patient_id', 'doctor_id', 'doctor', 'registry_date', 'is_canceled'];

    public function patient(): BelongsTo
    {
        return $this->BelongsTo(Patient::class, 'patient_id');
    }
    public function doctor(): BelongsTo
    {
        return $this->BelongsTo(Doctor::class, 'doctor_id');
    }
}

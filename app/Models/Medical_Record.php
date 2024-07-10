<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medical_Record extends Model
{
    use HasFactory;
    protected $fillable = ['patient_id', 'doctor_id', 'date', 'symptomps', 'diagnosis', 'prescription'];

    public function user(): BelongsTo
    {
        return $this->BelongsTo(User::class, 'patient_id');
    }
    public function doctor(): BelongTo
    {
        return $this->BelongsTo(Doctor::class, 'doctor_id');
    }
}

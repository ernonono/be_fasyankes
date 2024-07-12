<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medical_Record extends Model
{
    use HasFactory;
    protected $fillable = ['patient_id', 'doctor_id', 'date', 'symptomps', 'diagnosis', 'prescription'];

    public function user()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}

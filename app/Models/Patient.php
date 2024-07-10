<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'phone', 'gender', 'birth', 'address', 'status', 'religion', 'nik', 'kk', 'blood_type', 'parents', 'password',];

    public function registration(): HasMany
    {
        return $this->HasMany(Registration::class, 'registration_id');
    }
    public function medicalrecord(): HasMany
    {
        return $this->HasMany(MedicalRecord::class, 'medical_record_id');
    }
}

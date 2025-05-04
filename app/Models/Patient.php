<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'phone', 'gender', 'birth', 'address', 'religion', 'nik', 'kk', 'blood_type', 'user_id', 'image', 'status', 'father_name', 'mother_name', 'related_contact', 'bpjs'];

    public function registration()
    {
        return $this->hasMany(Registration::class);
    }
    public function medicalrecord()
    {
        return $this->hasMany(MedicalRecord::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

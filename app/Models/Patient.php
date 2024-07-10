<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'email', 'phone', 'gender', 'birth', 'address', 'religion', 'nik', 'kk', 'blood_type', 'password',];

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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Doctor extends Model
{
    use HasFactory;
    protected $fillable = ['poli_id', 'specialty_description', 'name', 'user_id', 'specialty', 'about', 'education', 'actions', 'image', 'nik', 'birthdate', 'gender', 'address', 'phone_number', 'hospital_code', 'profession', 'unique_number', 'facebook_link', 'twitter_link', 'google_plus_link', 'linkedin_link'];

    public function poli()
    {
        return $this->BelongsTo(Poli::class, 'poli_id');
    }
    public function medical_records()
    {
        return $this->hasMany(MedicalRecord::class);
    }
    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

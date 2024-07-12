<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;
    protected $fillable = ['poli_id', 'name', 'user_id', 'specialty', 'about', 'education', 'actions'];

    public function poli()
    {
        return $this->BelongsTo(Poli::class, 'poli_id');
    }
    public function medical_records()
    {
        return $this->hasMany(Medical_record::class);
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poli extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'location','id_user', 'doctor_id'];

    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }
}

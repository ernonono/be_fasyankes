<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quota extends Model
{
    use HasFactory;
    protected $fillable = ['doctor_id', 'dayIndex', 'quota', 'time', ];

    public function doctor()
    {
        return $this->BelongsTo(Doctor::class, 'doctor_id');
    }
}

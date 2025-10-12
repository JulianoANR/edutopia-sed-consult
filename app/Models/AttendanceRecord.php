<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_code',
        'date',
        'student_ra',
        'discipline_id',
        'status',
        'note',
        'user_id',
        'tenant_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function discipline()
    {
        return $this->belongsTo(\App\Models\Discipline::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
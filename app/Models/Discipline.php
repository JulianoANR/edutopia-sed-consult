<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discipline extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'tenant_id',
    ];

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function links()
    {
        return $this->hasMany(TeacherClassDisciplineLink::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherClassDisciplineLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'discipline_id',
        'class_name',
        'school_year',
        'full_access',
        'tenant_id',
        'class_code',
        'school_code',
    ];

    protected $table = 'teacher_class_discipline_links';

    protected $casts = [
        'full_access' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
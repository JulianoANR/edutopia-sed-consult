<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManagerSchoolLink extends Model
{
    use HasFactory;

    protected $table = 'manager_school_links';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'school_code',
        'school_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
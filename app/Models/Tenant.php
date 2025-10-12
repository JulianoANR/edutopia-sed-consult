<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'diretoria_id',
        'municipio_id',
        'rede_ensino_cod',
        'sed_username',
        'sed_password_encrypted',
        'status',
        'last_validated_at',
    ];

    protected $casts = [
        'last_validated_at' => 'datetime',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
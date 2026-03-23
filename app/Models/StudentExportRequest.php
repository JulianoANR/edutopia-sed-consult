<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentExportRequest extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_id',
        'school_codes',
        'ano_letivo',
        'selected_fields',
        'status',
        'progress_current',
        'file_path',
        'error_message',
    ];

    protected $casts = [
        'school_codes' => 'array',
        'selected_fields' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}

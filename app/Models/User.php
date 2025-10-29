<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'tenant_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'roles',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // -------------------------------------------------------------
    // Roles (multi-role support)
    // -------------------------------------------------------------
    public function roleLinks()
    {
        return $this->hasMany(\App\Models\UserRole::class);
    }

    // Expose roles as a simple array for frontend (Inertia serialization)
    public function getRolesAttribute(): array
    {
        // Prefer roleLinks; fall back to single role if not set
        $roles = $this->roleLinks()->pluck('role')->all();
        if (empty($roles) && $this->role) {
            $roles = [$this->role];
        }
        return $roles;
    }

    public function hasRole(string $role): bool
    {
        // Accept the legacy single role for backward compatibility
        if ($this->role && $this->role === $role) {
            return true;
        }
        return $this->roleLinks()->where('role', $role)->exists();
    }

    public function hasAnyRole(array|string $roles): bool
    {
        $roles = is_array($roles) ? $roles : array_map('trim', explode(',', $roles));
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isGestor(): bool
    {
        return $this->hasRole('gestor');
    }

    public function isProfessor(): bool
    {
        return $this->hasRole('professor');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function links()
    {
        return $this->hasMany(\App\Models\TeacherClassDisciplineLink::class);
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}

<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User Model
 *
 * Represents system users with role-based access control.
 * Supports multi-factor authentication and activity tracking.
 *
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $password_hash
 * @property UserRole $role
 * @property bool $mfa_enabled
 * @property string|null $mfa_secret
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'role',
        'mfa_enabled',
        'mfa_secret',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password_hash',
        'mfa_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'role' => \App\Enums\UserRole::class,
        'mfa_enabled' => 'boolean',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    /**
     * Get the password for authentication.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Get all transactions created by this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Check if user has admin role.
     */
    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    /**
     * Check if user has manager or admin role.
     */
    public function isManager(): bool
    {
        return $this->role->isManager();
    }

    /**
     * Check if user has compliance officer or admin role.
     */
    public function isComplianceOfficer(): bool
    {
        return $this->role->isComplianceOfficer();
    }
}

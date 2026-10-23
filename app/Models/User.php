<?php
namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'ADMIN';
    public const ROLE_ELECTION_OFFICER = 'ELECTION_OFFICER';
    public const ROLE_OBSERVER = 'OBSERVER';

    public const ROLES = [self::ROLE_ADMIN, self::ROLE_ELECTION_OFFICER, self::ROLE_OBSERVER];

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_active' => 'boolean'];
    }

    public function isAdmin(): bool { return $this->role === self::ROLE_ADMIN; }
    public function isElectionOfficer(): bool { return $this->role === self::ROLE_ELECTION_OFFICER; }
    public function isObserver(): bool { return $this->role === self::ROLE_OBSERVER; }

    public function assignedElections(): BelongsToMany
    {
        return $this->belongsToMany(Election::class, 'election_user')->withTimestamps();
    }
}

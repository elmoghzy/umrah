<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Validation\ValidationException;

#[Fillable(['name', 'email', 'password', 'job', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    public const ROLE_MAIN_ADMIN = 'main_admin';

    public const ROLE_BRANCH_EMPLOYEE = 'branch_employee';

    private bool $accessFieldsMayBeUpdated = false;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasPanelAccess();
    }

    public function hasPanelAccess(): bool
    {
        return $this->exists
            && (bool) $this->is_active
            && ($this->isMainAdmin() || ($this->isBranchEmployee() && filled($this->broker_id)));
    }

    public function isMainAdmin(): bool
    {
        return $this->role === self::ROLE_MAIN_ADMIN;
    }

    public function isBranchEmployee(): bool
    {
        return $this->role === self::ROLE_BRANCH_EMPLOYEE;
    }

    public function assignAccessFromUserResource(string $role, ?int $brokerId): void
    {
        $this->role = $role;
        $this->broker_id = $role === self::ROLE_BRANCH_EMPLOYEE ? $brokerId : null;
        $this->accessFieldsMayBeUpdated = true;
    }

    public function promoteToMainAdminForEmergency(): void
    {
        $this->role = self::ROLE_MAIN_ADMIN;
        $this->broker_id = null;
        $this->is_active = true;
        $this->accessFieldsMayBeUpdated = true;
        $this->save();
    }

    public function isActiveMainAdmin(): bool
    {
        return $this->isMainAdmin() && (bool) $this->is_active;
    }

    public function broker(): BelongsTo
    {
        return $this->belongsTo(Broker::class);
    }

    public function createdBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'created_by');
    }

    public function updatedBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'updated_by');
    }

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            $hasProtectedChanges = $user->isDirty(['role', 'broker_id']);
            $canUpdateProtectedFields = $user->accessFieldsMayBeUpdated;
            $user->accessFieldsMayBeUpdated = false;

            if ($user->exists && $hasProtectedChanges && ! $canUpdateProtectedFields) {
                throw ValidationException::withMessages([
                    'role' => 'تعديل نوع المستخدم والفرع مسموح من شاشة إدارة المستخدمين فقط.',
                ]);
            }

            if (! $user->exists) {
                return;
            }

            if (
                $hasProtectedChanges
                && (int) auth()->id() === (int) $user->getKey()
                && $user->getOriginal('role') === self::ROLE_MAIN_ADMIN
                && $user->role !== self::ROLE_MAIN_ADMIN
            ) {
                throw ValidationException::withMessages([
                    'role' => 'لا يمكنك تخفيض صلاحية حسابك الشخصي.',
                ]);
            }

            $wasActiveMainAdmin = $user->getOriginal('role') === self::ROLE_MAIN_ADMIN
                && (bool) $user->getOriginal('is_active');

            if ($wasActiveMainAdmin && ! $user->isActiveMainAdmin() && ! $user->hasAnotherActiveMainAdmin()) {
                $field = $user->isDirty('role') ? 'role' : 'is_active';

                throw ValidationException::withMessages([
                    $field => 'لا يمكن تخفيض أو تعطيل آخر مدير نشط.',
                ]);
            }
        });

        static::deleting(function (User $user): void {
            if ((int) auth()->id() === (int) $user->getKey()) {
                throw ValidationException::withMessages([
                    'user' => 'لا يمكنك حذف الحساب الذي سجلت الدخول به.',
                ]);
            }

            if ($user->isActiveMainAdmin() && ! $user->hasAnotherActiveMainAdmin()) {
                throw ValidationException::withMessages([
                    'user' => 'لا يمكن حذف آخر مدير نشط.',
                ]);
            }
        });
    }

    private function hasAnotherActiveMainAdmin(): bool
    {
        return self::query()
            ->whereKeyNot($this->getKey())
            ->where('role', self::ROLE_MAIN_ADMIN)
            ->where('is_active', true)
            ->exists();
    }

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
            'is_active' => 'boolean',
        ];
    }
}

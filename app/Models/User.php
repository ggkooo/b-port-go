<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'state',
        'city',
        'school',
        'class',
        'shift',
        'is_admin',
        'password',
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'class' => 'integer',
            'shift' => 'integer',
            'is_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public static function findByUuidOrFail(string $uuid): self
    {
        return static::query()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function scopeWithAdminRelations(Builder $query): Builder
    {
        return $query->with([
            'schoolClass:id,name',
            'schoolShift:id,name',
        ]);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class');
    }

    public function schoolShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift');
    }

    public function dailyChallenges(): HasMany
    {
        return $this->hasMany(DailyChallenge::class);
    }

    public function streak(): HasOne
    {
        return $this->hasOne(UserStreak::class);
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification(mixed $token): void
    {
        $this->notify(new ResetPasswordNotification($token, $this->email));
    }

    public function hasCompletedProfile(): bool
    {
        $requiredFields = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'state',
            'city',
            'school',
            'class',
            'shift',
        ];

        foreach ($requiredFields as $field) {
            if (blank($this->getAttribute($field))) {
                return false;
            }
        }

        return true;
    }
}

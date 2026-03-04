<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Challenge extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'unit',
        'target_value',
        'xp_reward',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_value' => 'integer',
            'xp_reward' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function dailyChallenges(): HasMany
    {
        return $this->hasMany(DailyChallenge::class);
    }
}

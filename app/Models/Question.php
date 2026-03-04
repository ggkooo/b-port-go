<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'statement',
        'alternative_a',
        'alternative_b',
        'alternative_c',
        'alternative_d',
        'correct_alternative',
        'tip',
        'difficulty_id',
        'class_id',
        'activity_type_id',
    ];

    public function difficulty(): BelongsTo
    {
        return $this->belongsTo(Difficulty::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function activityType(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class);
    }
}

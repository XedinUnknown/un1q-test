<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RRule\RRule;

class Event extends Model
{
    use HasFactory;

    public const ALLOWED_FREQUENCIES = RRule::FREQUENCIES;

    protected $fillable = [
        'start',
        'end',
        'frequency',
        'interval',
        'until',
    ];

    protected function getStartAttribute($value): DateTimeInterface
    {
        return new DateTimeImmutable($value);
    }

    protected function getEndAttribute($value): DateTimeInterface
    {
        return new DateTimeImmutable($value);
    }
}

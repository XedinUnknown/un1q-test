<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

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

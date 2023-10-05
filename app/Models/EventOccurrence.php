<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventOccurrence extends Model
{
    use HasFactory;

    protected $fillable = [
        'start',
        'end',
        'event_id',
    ];

    public $timestamps = false;

    protected function getStartAttribute($value): DateTimeInterface
    {
        return new DateTimeImmutable($value);
    }

    protected function getEndAttribute($value): DateTimeInterface
    {
        return new DateTimeImmutable($value);
    }

    protected function getUntilAttribute($value): DateTimeInterface
    {
        return new DateTimeImmutable($value);
    }
}

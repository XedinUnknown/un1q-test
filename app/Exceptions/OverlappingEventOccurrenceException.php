<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Signifies that an occurrence was attempted to be created,
 * whereas another occurrence already exists, and they overlap.
 */
class OverlappingEventOccurrenceException extends RuntimeException
{
}

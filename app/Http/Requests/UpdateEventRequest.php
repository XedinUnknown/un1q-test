<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    protected const DATETIME_FORMAT_ISO8601 = 'Y-m-d\TH:i:s';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => [
                'numeric',
                'integer',
                'gt:0',
            ],
            'start' => [
                sprintf('date_format:%1$s', static::DATETIME_FORMAT_ISO8601),
            ],
            'end' => [
                sprintf('date_format:%1$s', static::DATETIME_FORMAT_ISO8601),
            ],
            'frequency' => [
                Rule::in(array_map('strtolower', array_keys(Event::ALLOWED_FREQUENCIES))),
            ],
            'interval' => [
                'numeric',
                'integer',
                'gt:0',
            ],
            'until' => [
                sprintf('date_format:%1$s', static::DATETIME_FORMAT_ISO8601),
            ],
        ];
    }
}

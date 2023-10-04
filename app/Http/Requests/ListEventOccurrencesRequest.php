<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ListEventOccurrencesRequest extends FormRequest
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
            'from' => [
                'required',
                sprintf('date_format:%1$s', static::DATETIME_FORMAT_ISO8601),
            ],
            'to' => [
                'required',
                sprintf('date_format:%1$s', static::DATETIME_FORMAT_ISO8601),
            ],
            'event_id' => [
                'numeric',
                'integer',
                'gt:0',
            ],
            'page' => [
                'numeric',
                'integer',
                'gt:0',
            ],
            'limit' => [
                'numeric',
                'integer',
                'gt:0',
            ],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'errors' => $validator->errors(),
        ], 422));
    }
}

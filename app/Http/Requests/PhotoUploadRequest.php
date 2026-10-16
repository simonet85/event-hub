<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class PhotoUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                File::image(allowSvg: false)->types(['jpg', 'jpeg', 'png', 'webp'])->max(5 * 1024)->dimensions(
                    Rule::dimensions()->maxWidth(8000)->maxHeight(8000)
                ),
            ],
            'caption' => ['nullable', 'string', 'max:255'],
        ];
    }
}

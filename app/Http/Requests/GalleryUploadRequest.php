<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class GalleryUploadRequest extends FormRequest
{
    /** Photos acceptées par envoi (limite PHP `max_file_uploads` : 20 par défaut). */
    public const MAX_FILES = 20;

    /** Poids maximal d'une photo, en Ko : un reflex ou un téléphone récent produit 5 à 12 Mo. */
    public const MAX_SIZE_KB = 15 * 1024;

    /** Plus grand côté accepté : au-delà, le décodage dépasse la mémoire d'un hébergement mutualisé. */
    public const MAX_SIDE = 6500;

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
            'photos' => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES],
            'photos.*' => [
                File::image(allowSvg: false)->types(['jpg', 'jpeg', 'png', 'webp'])->max(self::MAX_SIZE_KB)->dimensions(
                    Rule::dimensions()->maxWidth(self::MAX_SIDE)->maxHeight(self::MAX_SIDE)
                ),
            ],
        ];
    }
}

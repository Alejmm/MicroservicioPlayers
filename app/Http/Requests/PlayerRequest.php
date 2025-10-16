<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required','string','max:100'],
            'number'    => ['nullable','integer','between:0,99'],
            'position'  => ['nullable','string','max:50'],
            'team_id'   => ['nullable','integer','min:1'],
            'photo_url' => ['nullable','string','max:255'], 
        ];
    }
}

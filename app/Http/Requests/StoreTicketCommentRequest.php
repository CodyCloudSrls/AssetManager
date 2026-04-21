<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'message' => $this->input('message', $this->input('note')),
            'is_public' => $this->boolean('is_public'),
        ]);
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:50000',
            'is_public' => 'nullable|boolean',
        ];
    }
}

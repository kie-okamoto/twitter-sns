<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'post_id' => ['required'], 
            'content' => ['required', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'post_id.required' => 'post_id がありません。',
            'content.required' => 'コメントを入力してください。',
            'content.max' => 'コメントは120文字以内で入力してください。',
        ];
    }
}

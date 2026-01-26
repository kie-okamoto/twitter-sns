<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // firebase middlewareで認証済み想定
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => '投稿内容を入力してください。',
            'content.max' => '投稿は120文字以内で入力してください。',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\TransitionAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::enum(TransitionAction::class)],
            // Bekor qilish va qaytarishda izoh majburiy.
            'comment' => [
                Rule::requiredIf(fn () => in_array($this->input('action'), [
                    TransitionAction::Reject->value,
                    TransitionAction::Return->value,
                ], true)),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function action(): TransitionAction
    {
        return TransitionAction::from($this->validated('action'));
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'Бекор қилиш/қайтаришда сабабни киритинг.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\ContractActionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canControlContracts() ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in([
                ContractActionType::Suspend->value,
                ContractActionType::Resume->value,
                ContractActionType::Terminate->value,
            ])],
            'reason' => [
                Rule::requiredIf(fn () => in_array($this->input('action'), [
                    ContractActionType::Suspend->value,
                    ContractActionType::Terminate->value,
                ], true)),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function action(): ContractActionType
    {
        return ContractActionType::from($this->validated('action'));
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Тўхтатиш/бекор қилиш сабабини киритинг.',
        ];
    }
}

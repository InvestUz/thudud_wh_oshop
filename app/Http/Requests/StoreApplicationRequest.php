<?php

namespace App\Http\Requests;

use App\Enums\RoleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRole(RoleType::Applicant) ?? false;
    }

    /** Yangi obyekt rejimi tanlanganmi? */
    public function isNewObject(): bool
    {
        return $this->input('object_mode') === 'new';
    }

    public function rules(): array
    {
        $isNew = $this->isNewObject();

        return [
            'object_mode' => ['required', 'in:existing,new'],

            // Mavjud obyekt rejimi.
            'object_id' => [Rule::requiredIf(! $isNew), 'nullable', 'exists:objects,id'],

            // Yangi obyekt rejimi.
            'cadastre_number' => [Rule::requiredIf($isNew), 'nullable', 'string', 'max:30'],
            'company_name' => [Rule::requiredIf($isNew), 'nullable', 'string', 'max:255'],
            'tin_pinfl' => ['nullable', 'string', 'max:20'],
            'region_id' => [Rule::requiredIf($isNew), 'nullable', 'exists:regions,id'],
            'district_id' => [Rule::requiredIf($isNew), 'nullable', 'exists:districts,id'],
            'mahalla_id' => ['nullable', 'exists:mahallas,id'],
            'street' => [Rule::requiredIf($isNew), 'nullable', 'string', 'max:255'],
            'house_number' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],

            // Umumiy (ariza) maydonlari.
            'activity' => ['nullable', 'string', 'max:255'],
            'area_m2' => ['required', 'numeric', 'min:0.1', 'max:100000'],
            'structures' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'submit_now' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'object_id.required' => 'Объектни танланг.',
            'cadastre_number.required' => 'Объект кадастр рақамини киритинг.',
            'company_name.required' => 'Фирма номини киритинг.',
            'region_id.required' => 'Шаҳарни танланг.',
            'district_id.required' => 'Туманни танланг.',
            'street.required' => 'Кўча номини киритинг.',
            'area_m2.required' => 'Туташ ҳудуд майдонини киритинг.',
        ];
    }
}

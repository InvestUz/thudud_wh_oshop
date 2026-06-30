<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Lending sahifadagi ochiq ariza topshirish formasi.
 */
class PublicApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'pinfl' => ['required', 'digits:14'],
            'cadastre_number' => ['required', 'string', 'max:100'],
            'company_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            // Ҳудуд: шаҳар (вилоят) -> туман -> маҳалла танланади, кўча қўлда киритилади.
            'region_id' => ['required', 'exists:regions,id'],
            'district_id' => ['required', 'exists:districts,id'],
            'mahalla_id' => ['nullable', 'exists:mahallas,id'],
            'street' => ['required', 'string', 'max:255'],
            'house_number' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Исмни киритинг.',
            'last_name.required' => 'Фамилияни киритинг.',
            'pinfl.required' => 'ПИНФЛ ни киритинг.',
            'pinfl.digits' => 'ПИНФЛ 14 та рақамдан иборат бўлиши керак.',
            'cadastre_number.required' => 'Объект кадастр рақамини киритинг.',
            'company_name.required' => 'Фирма номини киритинг.',
            'region_id.required' => 'Шаҳар/вилоятни танланг.',
            'district_id.required' => 'Туманни танланг.',
            'street.required' => 'Кўча номини киритинг.',
        ];
    }
}

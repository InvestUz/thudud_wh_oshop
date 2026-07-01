<?php

namespace App\Http\Requests;

use App\Enums\RoleType;
use App\Models\ApplicationSurvey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRole(RoleType::ResponsibleOfficer) ?? false;
    }

    public function rules(): array
    {
        // Расмлар — мажбурий 4 та. Аммо аввал сақланган расмлар бўлса (таҳрирлашда),
        // янги юкламаса ҳам бўлади (эскилари сақланиб қолади).
        $application = $this->route('application');
        $hasExistingPhotos = $application
            && $application->surveys()->whereNotNull('photos')->exists();
        $hasStudyReport = $application
            && $application->surveys()->whereNotNull('study_report_path')->exists();

        return [
            'length_m' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'width_m' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'total_area' => ['required', 'numeric', 'min:0.1', 'max:100000'],
            'facade_length_m' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'distance_to_road_m' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'distance_to_sidewalk_m' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            // Фойдаланиш мақсади ва кўча тури — мажбурий, рўйхатдан танланади.
            'usage_purpose' => ['required', Rule::in(ApplicationSurvey::USAGE_PURPOSES)],
            'street_type' => ['required', Rule::in(ApplicationSurvey::STREET_TYPES)],
            'contract_type' => [
                Rule::requiredIf(fn () => $this->input('street_type') === ApplicationSurvey::GASTRONOMIC_STREET_TYPE),
                'nullable',
                Rule::in(array_keys(ApplicationSurvey::CONTRACT_TYPES)),
            ],
            'activity_type' => ['required', Rule::in(ApplicationSurvey::ACTIVITY_TYPES)],
            'terrace_structures' => ['nullable', 'string', 'max:255'],
            'permanent_structures' => ['nullable', 'string', 'max:255'],
            'permit' => ['required', Rule::in(ApplicationSurvey::PERMIT_STATUSES)],
            'extra_info' => ['nullable', 'string', 'max:2000'],
            // Расм юклаш — камида 4 та (jpg/png/webp), кўпи 10 та.
            'photos' => [$hasExistingPhotos ? 'nullable' : 'required', 'array', 'min:4', 'max:10'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            // "Керакли ҳужжатлар" — ихтиёрий файллар (pdf/расм/офис).
            'documents' => ['nullable', 'array', 'max:10'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx', 'max:10240'],
            'study_report' => [$hasStudyReport ? 'nullable' : 'required', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            // Xaritada belgilangan maydon (GeoJSON matni) + markaz koordinatalari
            'geo_area' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'usage_purpose.required' => 'Фойдаланиш мақсадини танланг.',
            'usage_purpose.in' => 'Фойдаланиш мақсадини рўйхатдан танланг.',
            'street_type.required' => 'Кўча турини танланг.',
            'street_type.in' => 'Кўча турини рўйхатдан танланг.',
            'contract_type.required' => 'Гастрономик кўча учун шартнома турини танланг.',
            'contract_type.in' => 'Шартнома турини рўйхатдан танланг.',
            'photos.required' => 'Объектнинг камида 4 та расмини юкланг.',
            'photos.min' => 'Камида 4 та расм юкланг.',
            'photos.max' => 'Кўпи билан 10 та расм юклаш мумкин.',
            'photos.*.image' => 'Файл расм бўлиши керак (jpg, png, webp).',
            'photos.*.max' => 'Ҳар бир расм 5 МБ дан ошмаслиги керак.',
            'documents.*.mimes' => 'Ҳужжат pdf, расм, Word ёки Excel бўлиши керак.',
            'documents.*.max' => 'Ҳар бир ҳужжат 10 МБ дан ошмаслиги керак.',
            'study_report.required' => 'Ўрганиш далолатномасини юкланг.',
            'study_report.mimes' => 'Ўрганиш далолатномаси PDF ёки Word файл бўлиши керак.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // length × width avtomatik hisoblangan maydon (agar total kiritilmagan bo'lsa ko'rsatkich uchun).
        if ($this->filled('length_m') && $this->filled('width_m') && ! $this->filled('total_area')) {
            $this->merge([
                'total_area' => round((float) $this->input('length_m') * (float) $this->input('width_m'), 2),
            ]);
        }
    }
}

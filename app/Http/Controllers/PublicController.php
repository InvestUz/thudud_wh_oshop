<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStage;
use App\Enums\ApplicationStatus;
use App\Enums\RoleType;
use App\Http\Requests\PublicApplicationRequest;
use App\Models\Application;
use App\Models\RealEstateObject;
use App\Models\Region;
use App\Models\User;
use App\Services\ApplicationWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function landing(): View
    {
        // Ҳозирча фақат Тошкент шаҳри аризалари қабул қилинади (вилоятлар йўқ).
        // Туман ва маҳалла AJAX орқали юкланади.
        $regions = Region::where('name', 'Тошкент шаҳри')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('landing', compact('regions'));
    }

    /**
     * Ochiq (autentifikatsiyасиз) ariza topshirish.
     * Tadbirkor va ob'ekt topiladi yoki yaratiladi, ariza moderatsiyaга узатилади.
     */
    public function submit(PublicApplicationRequest $request, ApplicationWorkflowService $workflow): RedirectResponse
    {
        $data = $request->validated();
        $fullName = trim($data['last_name'].' '.$data['first_name']);

        $application = DB::transaction(function () use ($data, $fullName, $workflow) {
            // Tadbirkorni PINFL bo'yicha topamiz yoki yaratamiz.
            $user = User::firstOrCreate(
                ['pinfl' => $data['pinfl']],
                [
                    'name' => $fullName,
                    'full_name' => $fullName,
                    'email' => 'applicant.'.$data['pinfl'].'@thudud.local',
                    'phone' => $data['phone'] ?? null,
                    'password' => Hash::make(Str::random(16)),
                    'is_active' => true,
                ]
            );

            if (! $user->hasRole(RoleType::Applicant->value)) {
                $user->assignRole(RoleType::Applicant->value);
            }

            // Formada tanlangan hudud: shahar -> tuman -> mahalla, ko'cha qo'lda.
            $object = RealEstateObject::firstOrCreate(
                ['cadastre_number' => $data['cadastre_number']],
                [
                    'owner_id' => $user->id,
                    'company_name' => $data['company_name'],
                    'tin_pinfl' => $data['pinfl'],
                    'phone' => $data['phone'] ?? null,
                    'region_id' => $data['region_id'],
                    'district_id' => $data['district_id'],
                    'mahalla_id' => $data['mahalla_id'] ?? null,
                    'street' => $data['street'],
                    'street_status' => 'Шаҳар кўчаси',
                    'house_number' => $data['house_number'] ?? null,
                    'created_by' => $user->id,
                ]
            );

            $application = Application::create([
                'application_number' => 'А-'.now()->format('y').'-'.Str::upper(Str::random(5)),
                'object_id' => $object->id,
                'applicant_id' => $user->id,
                'status' => ApplicationStatus::Draft,
                'current_stage' => ApplicationStage::Draft,
                'region_id' => $object->region_id,
                'district_id' => $object->district_id,
            ]);

            $application->adjacentAreas()->create([
                'activity' => $data['company_name'],
                'area_m2' => 0, // майдон мас'ул ходим томонидан ўлчанади
                'structures' => null,
            ]);

            // draft -> moderation (moderator panelida кўринади).
            $workflow->submit($application, $user, 'Лендинг орқали онлайн топширилди');

            return $application;
        });

        return redirect()
            ->route('landing')
            ->with('public_app_number', $application->application_number)
            ->withFragment('ariza');
    }
}

<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\District;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Асосий демо туман — барча pipeline ходимлари шу туманда.
        $district = District::where('name', 'Мирзо-Улуғбек тумани')->first();
        $regionId = $district?->region_id;
        $districtId = $district?->id;

        // Демо ходимлар. Мас'ул ходим (officer) — фақат ўз тумани (Мирзо-Улуғбек) аризаларини
        // кўради (ҳудудий филтр намунаси). Қолган pipeline ходимлари республика даражаси
        // (барча туманлар) — шунда ихтиёрий туманга юборилган ариза модераторда кўринади.
        // Ҳудудий филтр scopeForDistrictOf'да: district_id берилса — фақат ўша туман.
        $staff = [
            ['email' => 'moderator@test.uz',  'role' => RoleType::Moderator,         'name' => 'Ниязова Дилноза',     'district' => null,        'phone' => '+998 71 200 11 11'],
            ['email' => 'masul@test.uz',      'legacy_email' => 'officer@test.uz', 'role' => RoleType::ResponsibleOfficer, 'name' => 'Абдуллаев Бахром', 'district' => $districtId, 'phone' => '+998 94 999 99 99'],
            ['email' => 'orinbosar@test.uz',  'legacy_email' => 'deputy@test.uz',  'role' => RoleType::DeputyHead,         'name' => 'Мирзаев Жаҳонгир', 'district' => null,        'phone' => '+998 99 888 88 88'],
            ['email' => 'rahbar@test.uz',     'legacy_email' => 'head@test.uz',    'role' => RoleType::Head,               'name' => 'Каримов Шавкат',   'district' => null,        'phone' => '+998 99 777 77 77'],
        ];

        foreach ($staff as $s) {
            $user = User::where('email', $s['email'])->first()
                ?? (isset($s['legacy_email']) ? User::where('email', $s['legacy_email'])->first() : null)
                ?? new User;
            $user->fill([
                'email' => $s['email'],
                'name' => $s['name'],
                'full_name' => $s['name'],
                'phone' => $s['phone'],
                'password' => Hash::make('password'),
                'region_id' => $s['district'] ? $regionId : null,
                'district_id' => $s['district'],
                'is_active' => true,
            ])->save();
            $user->syncRoles([$s['role']->value]);
        }

        // Жараёнда қатнашмайдиган эски demo аккаунтлар кириш рўйхатидан чиқарилади.
        User::whereIn('email', [
            'workgroup@test.uz',
            'lawyer@test.uz',
            'compliance@test.uz',
        ])->each(function (User $user): void {
            $user->update(['is_active' => false]);
            $user->syncRoles([]);
        });

        // Асосий тест мулкдор.
        $mainApplicant = User::where('email', 'tadbirkor@test.uz')->first()
            ?? User::where('email', 'applicant@test.uz')->first()
            ?? new User;
        $mainApplicant->fill([
            'email' => 'tadbirkor@test.uz',
            'name' => 'Полатов Бойсун',
            'full_name' => 'Полатов Бойсун Ғозиевич',
            'pinfl' => '31234567890123',
            'phone' => '+99897 777 77 77',
            'password' => Hash::make('password'),
            'region_id' => $regionId,
            'district_id' => $districtId,
            'is_active' => true,
        ])->save();
        $mainApplicant->syncRoles([RoleType::Applicant->value]);

        // Қўшимча мулкдорлар (faker).
        $names = [
            'Каримов Алишер', 'Усмонов Сардор', 'Юлдашева Малика', 'Эргашев Дилшод',
            'Набиев Жамшид', 'Қодирова Зухра', 'Тўраев Ботир', 'Саидова Гулнора',
        ];
        foreach ($names as $i => $name) {
            $user = User::updateOrCreate(
                ['email' => 'applicant'.($i + 1).'@test.uz'],
                [
                    'name' => $name,
                    'full_name' => $name,
                    'pinfl' => (string) (30000000000000 + ($i + 1) * 11111),
                    'phone' => '+998 9'.rand(0, 9).' '.rand(100, 999).' '.rand(10, 99).' '.rand(10, 99),
                    'password' => Hash::make('password'),
                    'region_id' => $regionId,
                    'district_id' => $districtId,
                    'is_active' => true,
                ]
            );
            $user->syncRoles([RoleType::Applicant->value]);
        }
    }
}

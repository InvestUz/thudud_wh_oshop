<?php

namespace App\Policies;

use App\Enums\ApplicationStage;
use App\Enums\RoleType;
use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->roleType() !== null;
    }

    public function view(User $user, Application $application): bool
    {
        // Ariza egasi har doim ko'radi.
        if ($application->isOwnedBy($user)) {
            return true;
        }

        // Pipeline xodimlari va shartnoma nazoratchilari — o'z tumani arizalarini.
        if ($user->isPipelineActor() || $user->canControlContracts()) {
            return $this->districtMatches($user, $application);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isRole(RoleType::Applicant);
    }

    /** Draft arizani faqat egasi tahrirlay oladi. */
    public function update(User $user, Application $application): bool
    {
        return $application->isOwnedBy($user)
            && $application->stage() === ApplicationStage::Draft;
    }

    public function delete(User $user, Application $application): bool
    {
        return $application->isOwnedBy($user)
            && $application->stage() === ApplicationStage::Draft;
    }

    protected function districtMatches(User $user, Application $application): bool
    {
        return $user->district_id === null
            || $application->district_id === $user->district_id;
    }
}

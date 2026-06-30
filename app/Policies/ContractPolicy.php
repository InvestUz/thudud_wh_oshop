<?php

namespace App\Policies;

use App\Enums\ContractStatus;
use App\Enums\RoleType;
use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canControlContracts() || $user->isRole(RoleType::Applicant);
    }

    public function view(User $user, Contract $contract): bool
    {
        if ($contract->owner_id === $user->id) {
            return true;
        }

        return $user->canControlContracts() && $this->districtMatches($user, $contract);
    }

    /** To'xtatish / qayta tiklash / bekor qilish. */
    public function control(User $user, Contract $contract): bool
    {
        return $user->canControlContracts()
            && $this->districtMatches($user, $contract);
    }

    public function suspend(User $user, Contract $contract): bool
    {
        return $this->control($user, $contract)
            && $contract->status === ContractStatus::Active;
    }

    public function resume(User $user, Contract $contract): bool
    {
        return $this->control($user, $contract)
            && $contract->status === ContractStatus::Suspended;
    }

    public function terminate(User $user, Contract $contract): bool
    {
        return $this->control($user, $contract)
            && $contract->status !== ContractStatus::Terminated;
    }

    protected function districtMatches(User $user, Contract $contract): bool
    {
        return $user->district_id === null
            || $contract->district_id === $user->district_id;
    }
}

<?php

namespace App\Policies;

use App\Models\KasClient;
use App\Models\User;

class KasClientPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, KasClient $model): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, KasClient $model): bool
    {
        return false;
    }

    public function delete(User $user, KasClient $model): bool
    {
        return false;
    }
}

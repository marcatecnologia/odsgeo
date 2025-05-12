<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WmsCamada;
use Illuminate\Auth\Access\HandlesAuthorization;

class WmsCamadaPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['visualizador_sigef', 'admin']);
    }

    public function view(User $user, WmsCamada $wmsCamada): bool
    {
        return $user->hasAnyRole(['visualizador_sigef', 'admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, WmsCamada $wmsCamada): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, WmsCamada $wmsCamada): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, WmsCamada $wmsCamada): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, WmsCamada $wmsCamada): bool
    {
        return $user->hasRole('admin');
    }
} 
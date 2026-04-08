<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function getUserProfile(User $user)
    {
        return $user;
    }

    public function updateProfile(User $user, array $data)
    {
        if (isset($data["password"])) {
            $data["password"] = Hash::make($data["password"]);
        }
        $user->update($data);
        return $user;
    }
}
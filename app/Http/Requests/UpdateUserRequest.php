<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => 'required|string|max:255',
            'login' => 'required|string|max:255|unique:users,login,' . $user->id,
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|in:admin,user',
        ];
    }
}

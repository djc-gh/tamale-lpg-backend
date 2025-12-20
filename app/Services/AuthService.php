<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Register a new user.
     *
     * @param  array  $data
     * @return User
     */
    public function register(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'station_id' => $data['station_id'] ?? null,
            'is_active' => true,
        ]);
    }

    /**
     * Login user and generate token.
     *
     * @param  string  $email
     * @param  string  $password
     * @return array|null
     */
    public function login(string $email, string $password): ?array
    {
        $user = User::where('email', $email)
            ->where('is_active', true)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Logout user and revoke token.
     *
     * @param  User  $user
     * @return bool
     */
    public function logout(User $user): bool
    {
        return $user->currentAccessToken()->delete();
    }

    /**
     * Refresh access token.
     *
     * @param  User  $user
     * @return string
     */
    public function refresh(User $user): string
    {
        $user->tokens()->delete();

        return $user->createToken('api-token')->plainTextToken;
    }
}

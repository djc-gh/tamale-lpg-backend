<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    protected string $testEmail = 'test@auth.com';
    protected string $testPassword = 'TestPassword123!';

    /**
     * Test user can register successfully as station manager (default role).
     */
    public function test_user_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'TestPassword123!',
            'password_confirmation' => 'TestPassword123!',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['user', 'token']);
        // Self-registration should default to 'station' role
        $response->assertJsonPath('user.role', 'station');
        
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role' => 'station',
        ]);
    }

    /**
     * Test user cannot register with mismatched passwords.
     */
    public function test_user_cannot_register_with_mismatched_passwords()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'TestPassword123!',
            'password_confirmation' => 'DifferentPassword!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.password', ['The password field confirmation does not match.']);
    }

    /**
     * Test user cannot register with duplicate email.
     */
    public function test_user_cannot_register_with_duplicate_email()
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'TestPassword123!',
            'password_confirmation' => 'TestPassword123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.email', ['The email has already been taken.']);
    }

    /**
     * Test user can login successfully.
     */
    public function test_user_can_login()
    {
        User::factory()->create([
            'email' => $this->testEmail,
            'password' => bcrypt($this->testPassword),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $this->testEmail,
            'password' => $this->testPassword,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['user', 'token']);
        $response->assertJsonPath('user.email', $this->testEmail);
    }

    /**
     * Test user cannot login with wrong password.
     */
    public function test_user_cannot_login_with_wrong_password()
    {
        User::factory()->create([
            'email' => $this->testEmail,
            'password' => bcrypt($this->testPassword),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $this->testEmail,
            'password' => 'WrongPassword123!',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Invalid credentials');
    }

    /**
     * Test user cannot login with non-existent email.
     */
    public function test_user_cannot_login_with_non_existent_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'AnyPassword123!',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Invalid credentials');
    }

    /**
     * Test authenticated user can logout.
     */
    public function test_authenticated_user_can_logout()
    {
        $user = User::factory()->create([
            'email' => $this->testEmail,
            'password' => bcrypt($this->testPassword),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Logged out successfully');
    }

    /**
     * Test unauthenticated user cannot logout.
     */
    public function test_unauthenticated_user_cannot_logout()
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    /**
     * Test user can refresh their token.
     */
    public function test_user_can_refresh_token()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/auth/refresh');

        $response->assertStatus(200);
        $response->assertJsonStructure(['user', 'token']);
    }

    /**
     * Test unauthenticated user cannot refresh token.
     */
    public function test_unauthenticated_user_cannot_refresh_token()
    {
        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(401);
    }

    /**
     * Test login response includes user information.
     */
    public function test_login_response_includes_user_info()
    {
        $user = User::factory()->create([
            'email' => $this->testEmail,
            'password' => bcrypt($this->testPassword),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $this->testEmail,
            'password' => $this->testPassword,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.id', $user->id);
        $response->assertJsonPath('user.email', $this->testEmail);
        $response->assertJsonPath('user.role', 'admin');
        $response->assertJsonPath('user.is_active', true);
        $response->assertJsonPath('user.name', $user->name);
    }

    /**
     * Test registration response includes user information.
     */
    public function test_registration_response_includes_user_info()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'TestPassword123!',
            'password_confirmation' => 'TestPassword123!',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('user.name', 'Jane Doe');
        $response->assertJsonPath('user.email', 'jane@example.com');
        $response->assertJsonPath('user.role', 'station');
        $response->assertJsonPath('user.is_active', true);
        $response->hasHeader('Authorization');
    }
}

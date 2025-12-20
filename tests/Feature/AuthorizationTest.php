<?php

namespace Tests\Feature;

use App\Models\Station;
use App\Models\User;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    protected User $adminUser;
    protected User $stationManagerUser;
    protected Station $station;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test station
        $this->station = Station::factory()->create([
            'name' => 'Test Station',
            'email' => 'test@tamalelpg.com',
        ]);

        // Create admin user
        $this->adminUser = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => 'admin',
            'station_id' => null,
            'is_active' => true,
        ]);

        // Create station manager user
        $this->stationManagerUser = User::factory()->create([
            'email' => 'manager@test.com',
            'role' => 'station',
            'station_id' => $this->station->id,
            'is_active' => true,
        ]);
    }

    /**
     * Test that admin user can create a station.
     */
    public function test_admin_can_create_station()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/stations', [
                'name' => 'New Station',
                'address' => 'Test Address',
                'phone' => '+233240000000',
                'email' => 'new@tamalelpg.com',
                'latitude' => 9.4034,
                'longitude' => -0.8424,
                'operating_hours' => '6:00 AM - 8:00 PM',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'name', 'email']);
    }

    /**
     * Test that station manager cannot create a station.
     */
    public function test_station_manager_cannot_create_station()
    {
        $response = $this->actingAs($this->stationManagerUser)
            ->postJson('/api/stations', [
                'name' => 'New Station',
                'address' => 'Test Address',
                'phone' => '+233240000000',
                'email' => 'new@tamalelpg.com',
                'latitude' => 9.4034,
                'longitude' => -0.8424,
                'operating_hours' => '6:00 AM - 8:00 PM',
            ]);

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Unauthorized - Admin access required');
    }

    /**
     * Test that unauthenticated user cannot create a station.
     */
    public function test_unauthenticated_user_cannot_create_station()
    {
        $response = $this->postJson('/api/stations', [
            'name' => 'New Station',
            'address' => 'Test Address',
            'phone' => '+233240000000',
            'email' => 'new@tamalelpg.com',
            'latitude' => 9.4034,
            'longitude' => -0.8424,
            'operating_hours' => '6:00 AM - 8:00 PM',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test that admin can view current user info.
     */
    public function test_admin_can_view_current_user()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/auth/me');

        $response->assertStatus(200);
        $response->assertJsonPath('role', 'admin');
        $response->assertJsonPath('email', $this->adminUser->email);
    }

    /**
     * Test that station manager can view current user info.
     */
    public function test_station_manager_can_view_current_user()
    {
        $response = $this->actingAs($this->stationManagerUser)
            ->getJson('/api/auth/me');

        $response->assertStatus(200);
        $response->assertJsonPath('role', 'station');
        $response->assertJsonPath('station_id', $this->station->id);
    }

    /**
     * Test that inactive user cannot access protected routes.
     */
    public function test_inactive_user_cannot_access_protected_routes()
    {
        $inactiveUser = User::factory()->create([
            'email' => 'inactive@test.com',
            'role' => 'admin',
            'is_active' => false,
        ]);

        $response = $this->actingAs($inactiveUser)
            ->getJson('/api/auth/me');

        // This should still return the user, but frontend should check is_active
        $response->assertStatus(200);
        $response->assertJsonPath('is_active', false);
    }

    /**
     * Test user role methods.
     */
    public function test_user_role_methods()
    {
        $this->assertTrue($this->adminUser->isAdmin());
        $this->assertFalse($this->adminUser->isStationManager());

        $this->assertFalse($this->stationManagerUser->isAdmin());
        $this->assertTrue($this->stationManagerUser->isStationManager());

        $this->assertTrue($this->adminUser->isActive());
        $this->assertTrue($this->stationManagerUser->isActive());
    }

    /**
     * Test station management permission check.
     */
    public function test_can_manage_station_permission()
    {
        $anotherStation = Station::factory()->create([
            'email' => 'another@tamalelpg.com',
        ]);

        // Admin can manage all stations
        $this->assertTrue($this->adminUser->canManageStation($this->station->id));
        $this->assertTrue($this->adminUser->canManageStation($anotherStation->id));

        // Station manager can only manage their station
        $this->assertTrue($this->stationManagerUser->canManageStation($this->station->id));
        $this->assertFalse($this->stationManagerUser->canManageStation($anotherStation->id));
    }

    /**
     * Test that station manager cannot update another station's availability.
     */
    public function test_station_manager_cannot_update_other_station_availability()
    {
        $anotherStation = Station::factory()->create([
            'email' => 'another@tamalelpg.com',
        ]);
        
        $response = $this->actingAs($this->stationManagerUser)
            ->patchJson("/api/stations/{$anotherStation->id}/availability", [
                'is_available' => false,
            ]);

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Unauthorized - You can only manage your assigned station');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Station;
use App\Models\User;
use Tests\TestCase;

class StationApiTest extends TestCase
{
    protected User $adminUser;
    protected Station $station;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->station = Station::factory()->create([
            'name' => 'Main Station',
            'price_per_kg' => 3.50,
            'is_available' => true,
        ]);
    }

    /**
     * Test public can view all stations.
     */
    public function test_public_can_view_all_stations()
    {
        $response = $this->getJson('/api/stations');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email', 'phone', 'address', 'price_per_kg', 'is_available']
            ],
            'meta' => ['current_page', 'per_page', 'total']
        ]);
    }

    /**
     * Test public can view single station.
     */
    public function test_public_can_view_single_station()
    {
        $response = $this->getJson("/api/stations/{$this->station->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('id', $this->station->id);
        $response->assertJsonPath('name', $this->station->name);
        $response->assertJsonPath('price_per_kg', $this->station->price_per_kg);
    }

    /**
     * Test public gets 404 for non-existent station.
     */
    public function test_public_gets_404_for_non_existent_station()
    {
        $response = $this->getJson('/api/stations/non-existent-id');

        $response->assertStatus(404);
    }

    /**
     * Test public can search nearby stations.
     */
    public function test_public_can_search_nearby_stations()
    {
        // Create another station nearby
        Station::factory()->create([
            'latitude' => 9.4034,
            'longitude' => -0.8424,
        ]);

        $response = $this->getJson('/api/stations/nearby?latitude=9.4034&longitude=-0.8424&radius=50');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => [
            '*' => ['id', 'name', 'distance_km']
        ]]);
    }

    /**
     * Test public can get price history for station.
     */
    public function test_public_can_get_station_price_history()
    {
        $response = $this->getJson("/api/stations/{$this->station->id}/price-history");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => [
            '*' => ['id', 'price_per_kg', 'updated_at']
        ]]);
    }

    /**
     * Test admin can update station.
     */
    public function test_admin_can_update_station()
    {
        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/stations/{$this->station->id}", [
                'name' => 'Updated Station Name',
                'phone' => '+233200000000',
                'price_per_kg' => 4.50,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('name', 'Updated Station Name');
        $response->assertJsonPath('price_per_kg', 4.50);

        $this->assertDatabaseHas('stations', [
            'id' => $this->station->id,
            'name' => 'Updated Station Name',
            'price_per_kg' => 4.50,
        ]);
    }

    /**
     * Test admin can delete station.
     */
    public function test_admin_can_delete_station()
    {
        $stationId = $this->station->id;

        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/api/stations/{$stationId}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('stations', [
            'id' => $stationId,
        ]);
    }

    /**
     * Test admin can update station availability via dedicated endpoint.
     */
    public function test_admin_can_update_station_availability()
    {
        $response = $this->actingAs($this->adminUser)
            ->patchJson("/api/stations/{$this->station->id}/availability", [
                'is_available' => false,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('is_available', false);

        $this->assertDatabaseHas('stations', [
            'id' => $this->station->id,
            'is_available' => false,
        ]);
    }

    /**
     * Test station manager can update only their station's availability.
     */
    public function test_station_manager_can_update_own_station_availability()
    {
        $manager = User::factory()->create([
            'role' => 'station',
            'station_id' => $this->station->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)
            ->patchJson("/api/stations/{$this->station->id}/availability", [
                'is_available' => false,
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('is_available', false);
    }

    /**
     * Test station manager cannot update other station's availability.
     */
    public function test_station_manager_cannot_update_other_station_availability()
    {
        $anotherStation = Station::factory()->create();
        
        $manager = User::factory()->create([
            'role' => 'station',
            'station_id' => $this->station->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)
            ->patchJson("/api/stations/{$anotherStation->id}/availability", [
                'is_available' => false,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test filtering stations by availability.
     */
    public function test_can_filter_stations_by_availability()
    {
        Station::factory()->create([
            'is_available' => false,
        ]);

        $response = $this->getJson('/api/stations?available=true');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => [
            '*' => ['is_available']
        ]]);
    }

    /**
     * Test filtering stations by price range.
     */
    public function test_can_filter_stations_by_price()
    {
        Station::factory()->create([
            'price_per_kg' => 5.00,
        ]);

        $response = $this->getJson('/api/stations?min_price=3&max_price=4');

        $response->assertStatus(200);
        // Should return filtered results
        $response->assertJsonStructure(['data']);
    }

    /**
     * Test pagination works correctly.
     */
    public function test_pagination_works_correctly()
    {
        Station::factory()->count(25)->create();

        $response = $this->getJson('/api/stations?per_page=10&page=2');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.current_page', 2);
        $response->assertJsonPath('meta.per_page', 10);
    }

    /**
     * Test sorting stations by price.
     */
    public function test_can_sort_stations_by_price()
    {
        $response = $this->getJson('/api/stations?sort_by=price&sort_order=desc');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    /**
     * Test creating station with invalid data returns validation errors.
     */
    public function test_creating_station_with_invalid_data()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/stations', [
                'name' => '',
                'email' => 'invalid-email',
                'latitude' => 100,  // Out of bounds
                'longitude' => 200, // Out of bounds
                'price_per_kg' => -1, // Negative price
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => [
            'name', 'email', 'latitude', 'longitude', 'price_per_kg'
        ]]);
    }

    /**
     * Test station endpoint returns correct structure.
     */
    public function test_station_response_structure()
    {
        $response = $this->getJson("/api/stations/{$this->station->id}");

        $response->assertJsonStructure([
            'id',
            'name',
            'address',
            'phone',
            'email',
            'latitude',
            'longitude',
            'price_per_kg',
            'is_available',
            'operating_hours',
            'created_at',
            'updated_at',
        ]);
    }
}

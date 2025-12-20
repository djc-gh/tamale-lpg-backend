<?php

namespace Database\Seeders;

use App\Models\Station;
use Illuminate\Database\Seeder;

class StationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Station::create([
            'name' => 'Tamale Central Gas Station',
            'address' => '123 Main Road, Tamale Central',
            'phone' => '+233 24 123 4567',
            'email' => 'central@tamalelpg.com',
            'is_available' => true,
            'price_per_kg' => 12.50,
            'operating_hours' => '6:00 AM - 8:00 PM',
            'image' => 'https://images.unsplash.com/photo-1616432043562-3671ea2e5242?w=400&h=300&fit=crop',
            'latitude' => 9.4034,
            'longitude' => -0.8424,
        ]);

        Station::create([
            'name' => 'Northern Star LPG',
            'address' => '45 Hospital Road, Tamale',
            'phone' => '+233 20 987 6543',
            'email' => 'northstar@tamalelpg.com',
            'is_available' => false,
            'price_per_kg' => 12.00,
            'operating_hours' => '7:00 AM - 7:00 PM',
            'image' => 'https://images.unsplash.com/photo-1567954970774-58d6aa6c50dc?w=400&h=300&fit=crop',
            'latitude' => 9.4156,
            'longitude' => -0.8398,
        ]);

        Station::create([
            'name' => 'Savanna Gas Hub',
            'address' => '78 Market Street, Tamale',
            'phone' => '+233 27 555 1234',
            'email' => 'savanna@tamalelpg.com',
            'is_available' => true,
            'price_per_kg' => 13.00,
            'operating_hours' => '6:30 AM - 9:00 PM',
            'image' => 'https://images.unsplash.com/photo-1605600659908-0ef719419d41?w=400&h=300&fit=crop',
            'latitude' => 9.3987,
            'longitude' => -0.8512,
        ]);

        Station::create([
            'name' => 'Golden Gate LPG Station',
            'address' => '22 Industrial Area, Tamale',
            'phone' => '+233 24 777 8899',
            'email' => 'goldengate@tamalelpg.com',
            'is_available' => true,
            'price_per_kg' => 11.80,
            'operating_hours' => '5:00 AM - 10:00 PM',
            'image' => 'https://images.unsplash.com/photo-1513828583688-c52646db42da?w=400&h=300&fit=crop',
            'latitude' => 9.4201,
            'longitude' => -0.8356,
        ]);

        Station::create([
            'name' => 'Vittin Gas Point',
            'address' => '56 Vittin Road, Tamale',
            'phone' => '+233 20 111 2233',
            'email' => 'vittin@tamalelpg.com',
            'is_available' => false,
            'price_per_kg' => 12.20,
            'operating_hours' => '6:00 AM - 8:00 PM',
            'image' => 'https://images.unsplash.com/photo-1595437193398-f24279553f4f?w=400&h=300&fit=crop',
            'latitude' => 9.3876,
            'longitude' => -0.8489,
        ]);

        Station::create([
            'name' => 'Kalpohin LPG Depot',
            'address' => '99 Kalpohin Estate, Tamale',
            'phone' => '+233 27 444 5566',
            'email' => 'kalpohin@tamalelpg.com',
            'is_available' => true,
            'price_per_kg' => 12.75,
            'operating_hours' => '24 Hours',
            'image' => 'https://images.unsplash.com/photo-1612544448445-b8232cff3b6c?w=400&h=300&fit=crop',
            'latitude' => 9.4112,
            'longitude' => -0.8567,
        ]);
    }
}

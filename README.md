
admin account

admin@lpg-tamale.com
password123

Check Migration Status
docker exec lpg_app php artisan migrate:status

Force Migrations
docker exec lpg_app php artisan migrate --force

Check api routes
docker exec lpg_app php artisan route:list | grep -i api

Run Database Seed
docker exec lpg_app php artisan db:seed

Clear Cache
docker exec lpg_app php artisan cache:clear && docker exec lpg_app php artisan config:clear


docker compose exec app php artisan tinker --execute="
\$admin = \App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@lpg-tamale.com',
    'password' => bcrypt('Chentiwuni1999@gmail.com'),
    'role' => 'admin',
    'is_active' => true
]);
echo 'Admin created: ' . \$admin->email;
"


local:
Email: admin@lpg-tamale.com
Password: admin123
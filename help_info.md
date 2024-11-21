Install Laravel and Sanctum
==================================================================
composer create-project laravel/laravel minimal-api
cd minimal-api
composer require laravel/sanctum
php artisan migrate


Enable Sanctum In config/sanctum.php, update stateful domains for local development:
===================================================================
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1')),


User Management
===========================================
php artisan make:model User --migration --factory --resource --controller
php artisan make:controller AuthController



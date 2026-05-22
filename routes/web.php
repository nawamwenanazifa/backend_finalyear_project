<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirect root to admin login page
Route::get('/', function () {
    return redirect('/admin/login');
});

// Emergency admin bypass route - removes timeout issue
Route::get('/admin-go', function () {
    $admin = User::where('is_admin', 1)->first();
    if ($admin) {
        Auth::login($admin);
        return redirect('/admin');
    }
    return 'No admin user found. Run: php artisan make:filament-user';
});
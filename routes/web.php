<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\StorageCors;

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

// Serve storage files with CORS headers so Flutter Web (browser) can load images
Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        abort(404);
    }
    return response()->file($fullPath);
})->where('path', '.*')->middleware(StorageCors::class);

// Web-based migration runner (no terminal needed)
// Usage: https://admin.fynbridals.com/run-migrate?key=FynBridals2026SecretMigrate
Route::get('/run-migrate', function (\Illuminate\Http\Request $request) {
    if ($request->query('key') !== 'FynBridals2026SecretMigrate') {
        abort(403, 'Invalid key.');
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();

        return response('<h2>✅ Migrations completed successfully</h2><pre>' . e($output) . '</pre>', 200)
            ->header('Content-Type', 'text/html');
    } catch (\Exception $e) {
        return response('<h2>❌ Migration failed</h2><pre>' . e($e->getMessage()) . '</pre>', 500)
            ->header('Content-Type', 'text/html');
    }
});
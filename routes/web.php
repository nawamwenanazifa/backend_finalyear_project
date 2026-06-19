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

    $log = [];

    try {
        // Ensure the migrations table exists
        if (!\Illuminate\Support\Facades\Schema::hasTable('migrations')) {
            \Illuminate\Support\Facades\Artisan::call('migrate:install');
            $log[] = '✅ Created migrations table';
        }

        // Get list of already-ran migrations
        $ran = \Illuminate\Support\Facades\DB::table('migrations')->pluck('migration')->toArray();

        // Get all migration files
        $migrationPath = database_path('migrations');
        $files = collect(scandir($migrationPath))
            ->filter(fn ($f) => str_ends_with($f, '.php'))
            ->map(fn ($f) => str_replace('.php', '', $f))
            ->values();

        // Find the next batch number
        $batch = \Illuminate\Support\Facades\DB::table('migrations')->max('batch') ?? 0;
        $batch++;

        // For each migration not yet recorded, check if it would fail due to existing table
        foreach ($files as $migration) {
            if (in_array($migration, $ran)) {
                continue;
            }

            // Check if this is a "create_X_table" migration and the table already exists
            if (preg_match('/create_(\w+)_table$/', $migration, $matches)) {
                $tableName = $matches[1];
                if (\Illuminate\Support\Facades\Schema::hasTable($tableName)) {
                    // Mark as already run so migrate skips it
                    \Illuminate\Support\Facades\DB::table('migrations')->insert([
                        'migration' => $migration,
                        'batch' => $batch,
                    ]);
                    $log[] = "⏭️ Skipped {$migration} (table '{$tableName}' already exists)";
                }
            }
        }

        // Now run migrate normally — skipped migrations won't re-run
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();
        $log[] = '';
        $log[] = '--- Migrate Output ---';
        $log[] = $output;

        $html = '<h2>✅ Migrations completed successfully</h2><pre>' . e(implode("\n", $log)) . '</pre>';
        return response($html, 200)->header('Content-Type', 'text/html');
    } catch (\Exception $e) {
        $log[] = '❌ Error: ' . $e->getMessage();
        $html = '<h2>❌ Migration failed</h2><pre>' . e(implode("\n", $log)) . '</pre>';
        return response($html, 500)->header('Content-Type', 'text/html');
    }
});
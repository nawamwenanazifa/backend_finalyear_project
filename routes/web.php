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
    $Schema = \Illuminate\Support\Facades\Schema::class;
    $DB = \Illuminate\Support\Facades\DB::class;

    try {
        // 1. Ensure the migrations table exists
        if (!$Schema::hasTable('migrations')) {
            \Illuminate\Support\Facades\Artisan::call('migrate:install');
            $log[] = '✅ Created migrations table';
        }

        // 2. Create all missing tables in correct dependency order
        // --- Independent tables first ---

        if (!$Schema::hasTable('users')) {
            $Schema::create('users', function ($t) {
                $t->id();
                $t->string('name');
                $t->string('email')->unique();
                $t->string('phone')->nullable();
                $t->string('role')->default('bride');
                $t->string('gender')->nullable();
                $t->string('profile_image')->nullable();
                $t->text('bio')->nullable();
                $t->string('location')->nullable();
                $t->boolean('is_admin')->default(false);
                $t->timestamp('email_verified_at')->nullable();
                $t->string('password');
                $t->timestamp('locked_until')->nullable();
                $t->integer('failed_login_attempts')->default(0);
                $t->string('verification_code')->nullable();
                $t->timestamp('verification_code_sent_at')->nullable();
                $t->rememberToken();
                $t->timestamps();
            });
            $log[] = '✅ Created: users';
        } else {
            // Add missing columns to users
            foreach ([
                'role' => fn($t) => $t->string('role')->default('bride'),
                'gender' => fn($t) => $t->string('gender')->nullable(),
                'profile_image' => fn($t) => $t->string('profile_image')->nullable(),
                'bio' => fn($t) => $t->text('bio')->nullable(),
                'location' => fn($t) => $t->string('location')->nullable(),
                'is_admin' => fn($t) => $t->boolean('is_admin')->default(false),
                'locked_until' => fn($t) => $t->timestamp('locked_until')->nullable(),
                'failed_login_attempts' => fn($t) => $t->integer('failed_login_attempts')->default(0),
                'verification_code' => fn($t) => $t->string('verification_code')->nullable(),
                'verification_code_sent_at' => fn($t) => $t->timestamp('verification_code_sent_at')->nullable(),
            ] as $col => $fn) {
                if (!$Schema::hasColumn('users', $col)) {
                    $Schema::table('users', $fn);
                    $log[] = "  ➕ Added column users.{$col}";
                }
            }
            $log[] = '⏭️ Skipped: users (exists)';
        }

        if (!$Schema::hasTable('password_reset_tokens')) {
            $Schema::create('password_reset_tokens', function ($t) {
                $t->string('email')->primary();
                $t->string('token');
                $t->timestamp('created_at')->nullable();
            });
            $log[] = '✅ Created: password_reset_tokens';
        } else {
            $log[] = '⏭️ Skipped: password_reset_tokens (exists)';
        }

        if (!$Schema::hasTable('sessions')) {
            $Schema::create('sessions', function ($t) {
                $t->string('id')->primary();
                $t->foreignId('user_id')->nullable()->index();
                $t->string('ip_address', 45)->nullable();
                $t->text('user_agent')->nullable();
                $t->longText('payload');
                $t->integer('last_activity')->index();
            });
            $log[] = '✅ Created: sessions';
        } else {
            $log[] = '⏭️ Skipped: sessions (exists)';
        }

        if (!$Schema::hasTable('cache')) {
            $Schema::create('cache', function ($t) {
                $t->string('key')->primary();
                $t->mediumText('value');
                $t->integer('expiration');
            });
            $log[] = '✅ Created: cache';
        } else {
            $log[] = '⏭️ Skipped: cache (exists)';
        }

        if (!$Schema::hasTable('cache_locks')) {
            $Schema::create('cache_locks', function ($t) {
                $t->string('key')->primary();
                $t->string('owner');
                $t->integer('expiration');
            });
            $log[] = '✅ Created: cache_locks';
        } else {
            $log[] = '⏭️ Skipped: cache_locks (exists)';
        }

        if (!$Schema::hasTable('jobs')) {
            $Schema::create('jobs', function ($t) {
                $t->id();
                $t->string('queue')->index();
                $t->longText('payload');
                $t->unsignedTinyInteger('attempts');
                $t->unsignedInteger('reserved_at')->nullable();
                $t->unsignedInteger('available_at');
                $t->unsignedInteger('created_at');
            });
            $log[] = '✅ Created: jobs';
        } else {
            $log[] = '⏭️ Skipped: jobs (exists)';
        }

        if (!$Schema::hasTable('job_batches')) {
            $Schema::create('job_batches', function ($t) {
                $t->string('id')->primary();
                $t->string('name');
                $t->integer('total_jobs');
                $t->integer('pending_jobs');
                $t->integer('failed_jobs');
                $t->longText('failed_job_ids');
                $t->mediumText('options')->nullable();
                $t->integer('cancelled_at')->nullable();
                $t->integer('created_at');
                $t->integer('finished_at')->nullable();
            });
            $log[] = '✅ Created: job_batches';
        } else {
            $log[] = '⏭️ Skipped: job_batches (exists)';
        }

        if (!$Schema::hasTable('failed_jobs')) {
            $Schema::create('failed_jobs', function ($t) {
                $t->id();
                $t->string('uuid')->unique();
                $t->text('connection');
                $t->text('queue');
                $t->longText('payload');
                $t->longText('exception');
                $t->timestamp('failed_at')->useCurrent();
            });
            $log[] = '✅ Created: failed_jobs';
        } else {
            $log[] = '⏭️ Skipped: failed_jobs (exists)';
        }

        if (!$Schema::hasTable('personal_access_tokens')) {
            $Schema::create('personal_access_tokens', function ($t) {
                $t->id();
                $t->morphs('tokenable');
                $t->text('name');
                $t->string('token', 64)->unique();
                $t->text('abilities')->nullable();
                $t->timestamp('last_used_at')->nullable();
                $t->timestamp('expires_at')->nullable()->index();
                $t->timestamps();
            });
            $log[] = '✅ Created: personal_access_tokens';
        } else {
            $log[] = '⏭️ Skipped: personal_access_tokens (exists)';
        }

        if (!$Schema::hasTable('categories')) {
            $Schema::create('categories', function ($t) {
                $t->id();
                $t->string('name');
                $t->string('icon')->nullable();
                $t->text('description')->nullable();
                $t->timestamps();
            });
            $log[] = '✅ Created: categories';
        } else {
            $log[] = '⏭️ Skipped: categories (exists)';
        }

        if (!$Schema::hasTable('otp_codes')) {
            $Schema::create('otp_codes', function ($t) {
                $t->id();
                $t->string('email');
                $t->string('code');
                $t->timestamp('expires_at');
                $t->boolean('used')->default(false);
                $t->timestamps();
            });
            $log[] = '✅ Created: otp_codes';
        } else {
            $log[] = '⏭️ Skipped: otp_codes (exists)';
        }

        if (!$Schema::hasTable('login_attempts')) {
            $Schema::create('login_attempts', function ($t) {
                $t->id();
                $t->string('email')->index();
                $t->string('ip_address')->index();
                $t->boolean('was_successful')->default(false);
                $t->timestamp('attempted_at');
                $t->timestamps();
            });
            $log[] = '✅ Created: login_attempts';
        } else {
            $log[] = '⏭️ Skipped: login_attempts (exists)';
        }

        if (!$Schema::hasTable('audit_logs')) {
            $Schema::create('audit_logs', function ($t) {
                $t->id();
                $t->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $t->string('action');
                $t->string('table_name')->nullable();
                $t->unsignedBigInteger('record_id')->nullable();
                $t->json('old_values')->nullable();
                $t->json('new_values')->nullable();
                $t->string('ip_address', 45);
                $t->text('user_agent')->nullable();
                $t->timestamps();
                $t->index(['table_name', 'record_id']);
                $t->index('created_at');
                $t->index('action');
            });
            $log[] = '✅ Created: audit_logs';
        } else {
            $log[] = '⏭️ Skipped: audit_logs (exists)';
        }

        if (!$Schema::hasTable('galleries')) {
            $Schema::create('galleries', function ($t) {
                $t->id();
                $t->string('title');
                $t->text('description')->nullable();
                $t->string('image_url');
                $t->string('category');
                $t->json('tags')->nullable();
                $t->string('photographer_name')->nullable();
                $t->string('price')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
            $log[] = '✅ Created: galleries';
        } else {
            $log[] = '⏭️ Skipped: galleries (exists)';
        }

        // --- Tables with foreign keys (depend on tables above) ---

        if (!$Schema::hasTable('products')) {
            $Schema::create('products', function ($t) {
                $t->id();
                $t->foreignId('category_id')->constrained()->onDelete('cascade');
                $t->string('name');
                $t->decimal('price', 12, 2);
                $t->text('description')->nullable();
                $t->string('image')->nullable();
                $t->string('color')->nullable();
                $t->float('rating')->default(5.0);
                $t->boolean('in_stock')->default(true);
                $t->boolean('is_featured')->default(false);
                $t->integer('stock_quantity')->default(0);
                $t->integer('low_stock_threshold')->default(5);
                $t->timestamps();
            });
            $log[] = '✅ Created: products';
        } else {
            foreach (['stock_quantity' => fn($t) => $t->integer('stock_quantity')->default(0), 'low_stock_threshold' => fn($t) => $t->integer('low_stock_threshold')->default(5)] as $col => $fn) {
                if (!$Schema::hasColumn('products', $col)) {
                    $Schema::table('products', $fn);
                    $log[] = "  ➕ Added column products.{$col}";
                }
            }
            $log[] = '⏭️ Skipped: products (exists)';
        }

        if (!$Schema::hasTable('bookings')) {
            $Schema::create('bookings', function ($t) {
                $t->id();
                $t->foreignId('user_id')->constrained()->onDelete('cascade');
                $t->string('service_type');
                $t->dateTime('booking_date');
                $t->string('status')->default('pending');
                $t->string('phone')->nullable();
                $t->string('payment_method')->nullable();
                $t->timestamps();
            });
            $log[] = '✅ Created: bookings';
        } else {
            foreach (['phone' => fn($t) => $t->string('phone')->nullable(), 'service_type' => fn($t) => $t->string('service_type')->nullable(), 'payment_method' => fn($t) => $t->string('payment_method')->nullable()] as $col => $fn) {
                if (!$Schema::hasColumn('bookings', $col)) {
                    $Schema::table('bookings', $fn);
                    $log[] = "  ➕ Added column bookings.{$col}";
                }
            }
            $log[] = '⏭️ Skipped: bookings (exists)';
        }

        if (!$Schema::hasTable('moodboard_items')) {
            $Schema::create('moodboard_items', function ($t) {
                $t->id();
                $t->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $t->string('image')->nullable();
                $t->text('notes')->nullable();
                $t->timestamps();
            });
            $log[] = '✅ Created: moodboard_items';
        } else {
            $log[] = '⏭️ Skipped: moodboard_items (exists)';
        }

        if (!$Schema::hasTable('conversations')) {
            $Schema::create('conversations', function ($t) {
                $t->id();
                $t->foreignId('user_one_id')->constrained('users')->onDelete('cascade');
                $t->foreignId('user_two_id')->constrained('users')->onDelete('cascade');
                $t->timestamp('last_message_at')->nullable();
                $t->timestamps();
                $t->unique(['user_one_id', 'user_two_id']);
            });
            $log[] = '✅ Created: conversations';
        } else {
            $log[] = '⏭️ Skipped: conversations (exists)';
        }

        if (!$Schema::hasTable('messages')) {
            $Schema::create('messages', function ($t) {
                $t->id();
                $t->foreignId('conversation_id')->constrained()->cascadeOnDelete();
                $t->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
                $t->enum('type', ['text', 'image', 'audio'])->default('text');
                $t->text('content');
                $t->boolean('is_read')->default(false);
                $t->timestamps();
            });
            $log[] = '✅ Created: messages';
        } else {
            if (!$Schema::hasColumn('messages', 'conversation_id')) {
                $Schema::table('messages', function ($t) {
                    $t->unsignedBigInteger('conversation_id')->nullable();
                });
                $log[] = '  ➕ Added column messages.conversation_id';
            }
            $log[] = '⏭️ Skipped: messages (exists)';
        }

        if (!$Schema::hasTable('orders')) {
            $Schema::create('orders', function ($t) {
                $t->id();
                $t->foreignId('user_id')->constrained()->onDelete('cascade');
                $t->foreignId('booking_id')->nullable()->constrained()->onDelete('set null');
                $t->string('order_number')->unique();
                $t->decimal('subtotal', 12, 2);
                $t->decimal('tax', 12, 2)->default(0);
                $t->decimal('delivery_fee', 12, 2)->default(0);
                $t->decimal('total', 12, 2);
                $t->enum('payment_method', ['cash_on_delivery', 'mobile_money', 'bank_transfer'])->default('cash_on_delivery');
                $t->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
                $t->enum('order_status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
                $t->text('shipping_address');
                $t->text('notes')->nullable();
                $t->timestamp('delivered_at')->nullable();
                $t->timestamps();
            });
            $log[] = '✅ Created: orders';
        } else {
            $log[] = '⏭️ Skipped: orders (exists)';
        }

        if (!$Schema::hasTable('order_items')) {
            $Schema::create('order_items', function ($t) {
                $t->id();
                $t->foreignId('order_id')->constrained()->onDelete('cascade');
                $t->foreignId('product_id')->constrained();
                $t->integer('quantity');
                $t->decimal('price', 12, 2);
                $t->timestamps();
            });
            $log[] = '✅ Created: order_items';
        } else {
            $log[] = '⏭️ Skipped: order_items (exists)';
        }

        if (!$Schema::hasTable('carts')) {
            $Schema::create('carts', function ($t) {
                $t->id();
                $t->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $t->timestamps();
            });
            $log[] = '✅ Created: carts';
        } else {
            $log[] = '⏭️ Skipped: carts (exists)';
        }

        if (!$Schema::hasTable('cart_items')) {
            $Schema::create('cart_items', function ($t) {
                $t->id();
                $t->foreignId('cart_id')->nullable()->constrained()->onDelete('cascade');
                $t->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
                $t->integer('quantity')->default(1);
                $t->timestamps();
            });
            $log[] = '✅ Created: cart_items';
        } else {
            $log[] = '⏭️ Skipped: cart_items (exists)';
        }

        if (!$Schema::hasTable('notifications')) {
            $Schema::create('notifications', function ($t) {
                $t->id();
                $t->string('title');
                $t->text('message');
                $t->string('type')->default('info');
                $t->string('icon')->nullable();
                $t->string('link')->nullable();
                $t->boolean('is_read')->default(false);
                $t->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $t->timestamp('read_at')->nullable();
                $t->timestamps();
            });
            $log[] = '✅ Created: notifications';
        } else {
            $log[] = '⏭️ Skipped: notifications (exists)';
        }

        // 3. Mark ALL migration files as completed so artisan migrate won't re-run them
        $migrationPath = database_path('migrations');
        $files = collect(scandir($migrationPath))
            ->filter(fn ($f) => str_ends_with($f, '.php'))
            ->map(fn ($f) => str_replace('.php', '', $f))
            ->values();

        $ran = $DB::table('migrations')->pluck('migration')->toArray();
        $batch = ($DB::table('migrations')->max('batch') ?? 0) + 1;
        $marked = 0;

        foreach ($files as $migration) {
            if (!in_array($migration, $ran)) {
                $DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => $batch,
                ]);
                $marked++;
            }
        }

        $log[] = '';
        $log[] = "✅ Marked {$marked} migration files as completed";
        $log[] = '';
        $log[] = '🎉 All done! Your database is ready.';

        $html = '<h2>✅ Database setup completed successfully</h2><pre>' . e(implode("\n", $log)) . '</pre>';
        return response($html, 200)->header('Content-Type', 'text/html');
    } catch (\Exception $e) {
        $log[] = '❌ Error: ' . $e->getMessage();
        $html = '<h2>❌ Migration failed</h2><pre>' . e(implode("\n", $log)) . '</pre>';
        return response($html, 500)->header('Content-Type', 'text/html');
    }
});

// Web-based seeder runner
// Usage: https://admin.fynbridals.com/run-seeders?key=FynBridals2026SecretMigrate
Route::get('/run-seeders', function (\Illuminate\Http\Request $request) {
    if ($request->query('key') !== 'FynBridals2026SecretMigrate') {
        abort(403, 'Invalid key.');
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();

        return response('<h2>✅ Seeding completed successfully</h2><pre>' . e($output) . '</pre>', 200)
            ->header('Content-Type', 'text/html');
    } catch (\Exception $e) {
        return response('<h2>❌ Seeding failed</h2><pre>' . e($e->getMessage()) . '</pre>', 500)
            ->header('Content-Type', 'text/html');
    }
});
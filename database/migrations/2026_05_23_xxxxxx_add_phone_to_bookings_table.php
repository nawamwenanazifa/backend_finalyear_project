<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'phone')) {
                $table->string('phone')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('bookings', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['phone', 'email']);
        });
    }
};
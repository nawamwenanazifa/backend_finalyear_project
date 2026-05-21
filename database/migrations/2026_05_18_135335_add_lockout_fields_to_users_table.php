<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('locked_until')->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->string('verification_code')->nullable();
            $table->timestamp('verification_code_sent_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locked_until', 'failed_login_attempts', 'verification_code', 'verification_code_sent_at']);
        });
    }
};
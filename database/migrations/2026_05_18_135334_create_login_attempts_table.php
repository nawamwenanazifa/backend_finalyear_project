<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('ip_address');
            $table->boolean('was_successful')->default(false);
            $table->timestamp('attempted_at');
            $table->timestamps();
            
            $table->index('email');
            $table->index('ip_address');
        });
    }

    public function down()
    {
        Schema::dropIfExists('login_attempts');
    }
};
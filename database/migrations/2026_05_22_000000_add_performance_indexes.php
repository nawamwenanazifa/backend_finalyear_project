<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Indexes for messages table (for faster chat loading)
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                if (!Schema::hasIndex('messages', 'messages_conversation_id_index')) {
                    $table->index('conversation_id');
                }
                if (!Schema::hasIndex('messages', 'messages_sender_id_index')) {
                    $table->index('sender_id');
                }
                if (!Schema::hasIndex('messages', 'messages_receiver_id_index')) {
                    $table->index('receiver_id');
                }
                if (!Schema::hasIndex('messages', 'messages_created_at_index')) {
                    $table->index('created_at');
                }
                if (!Schema::hasIndex('messages', 'messages_is_read_index')) {
                    $table->index('is_read');
                }
            });
        }

        // Indexes for conversations table
        if (Schema::hasTable('conversations')) {
            Schema::table('conversations', function (Blueprint $table) {
                if (!Schema::hasIndex('conversations', 'conversations_user_one_id_index')) {
                    $table->index('user_one_id');
                }
                if (!Schema::hasIndex('conversations', 'conversations_user_two_id_index')) {
                    $table->index('user_two_id');
                }
                if (!Schema::hasIndex('conversations', 'conversations_updated_at_index')) {
                    $table->index('updated_at');
                }
            });
        }

        // Indexes for products table
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasIndex('products', 'products_category_id_index')) {
                    $table->index('category_id');
                }
                if (!Schema::hasIndex('products', 'products_is_featured_index')) {
                    $table->index('is_featured');
                }
            });
        }

        // Indexes for bookings table
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (!Schema::hasIndex('bookings', 'bookings_user_id_index')) {
                    $table->index('user_id');
                }
                if (!Schema::hasIndex('bookings', 'bookings_booking_date_index')) {
                    $table->index('booking_date');
                }
            });
        }
    }

    public function down(): void
    {
        // Drop indexes if needed (optional)
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropIndex(['conversation_id']);
                $table->dropIndex(['sender_id']);
                $table->dropIndex(['receiver_id']);
                $table->dropIndex(['created_at']);
                $table->dropIndex(['is_read']);
            });
        }

        if (Schema::hasTable('conversations')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->dropIndex(['user_one_id']);
                $table->dropIndex(['user_two_id']);
                $table->dropIndex(['updated_at']);
            });
        }
    }
};
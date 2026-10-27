<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index(['is_active', 'name'], 'products_is_active_name_idx');
                $table->index(['deleted_at'], 'products_deleted_at_idx');
            });
        }

        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['status', 'completed_at'], 'transactions_status_completed_at_idx');
                $table->index(['sales_channel', 'created_at'], 'transactions_sales_channel_created_at_idx');
            });
        }

        if (Schema::hasTable('stock_logs')) {
            Schema::table('stock_logs', function (Blueprint $table) {
                $table->index(['created_at'], 'stock_logs_created_at_idx');
                $table->index(['type', 'created_at'], 'stock_logs_type_created_at_idx');
            });
        }

        if (Schema::hasTable('transaction_details')) {
            Schema::table('transaction_details', function (Blueprint $table) {
                $table->index(['created_at'], 'transaction_details_created_at_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_is_active_name_idx');
                $table->dropIndex('products_deleted_at_idx');
            });
        }

        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropIndex('transactions_status_completed_at_idx');
                $table->dropIndex('transactions_sales_channel_created_at_idx');
            });
        }

        if (Schema::hasTable('stock_logs')) {
            Schema::table('stock_logs', function (Blueprint $table) {
                $table->dropIndex('stock_logs_created_at_idx');
                $table->dropIndex('stock_logs_type_created_at_idx');
            });
        }

        if (Schema::hasTable('transaction_details')) {
            Schema::table('transaction_details', function (Blueprint $table) {
                $table->dropIndex('transaction_details_created_at_idx');
            });
        }
    }
};

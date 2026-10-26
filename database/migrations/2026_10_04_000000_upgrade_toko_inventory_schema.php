<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales') && ! Schema::hasTable('transactions')) {
            Schema::rename('sales', 'transactions');
        }

        if (Schema::hasTable('sale_details') && ! Schema::hasTable('transaction_details')) {
            Schema::rename('sale_details', 'transaction_details');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('role')->default('cashier');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('unit', 30)->default('pcs');
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('stock', 12, 3)->default(0);
            $table->decimal('min_stock', 12, 3)->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('transaction_number')->unique();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('change_amount', 15, 2)->default(0);
            $table->string('payment_method', 40)->default('cash');
            $table->string('status', 30)->default('completed');
            $table->text('notes')->nullable();
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('unit', 30);
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
        });

        Schema::create('stock_logs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->decimal('quantity_change', 12, 3);
            $table->decimal('stock_before', 12, 3);
            $table->decimal('stock_after', 12, 3);
            $table->string('reference')->nullable()->unique();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_logs');

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
            $table->dropForeign(['product_id']);
            $table->dropColumn([
                'transaction_id', 'product_id', 'product_name', 'unit', 'quantity', 'unit_price', 'subtotal',
            ]);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['transaction_number']);
            $table->dropColumn([
                'user_id', 'transaction_number', 'subtotal', 'total', 'amount_paid', 'change_amount',
                'payment_method', 'status', 'notes',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropUnique(['sku']);
            $table->dropColumn([
                'category_id', 'name', 'sku', 'unit', 'purchase_price', 'selling_price', 'stock', 'min_stock',
                'image_path', 'is_active',
            ]);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropColumn(['name', 'description', 'is_active']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        if (Schema::hasTable('transactions') && ! Schema::hasTable('sales')) {
            Schema::rename('transactions', 'sales');
        }

        if (Schema::hasTable('transaction_details') && ! Schema::hasTable('sale_details')) {
            Schema::rename('transaction_details', 'sale_details');
        }
    }
};

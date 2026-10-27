<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_units')) {
            Schema::create('product_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('unit_name', 50); // Dus, Karton, Renteng, Pack, dsb.
                $table->decimal('conversion_factor', 12, 3); // Pengali ke satuan dasar (misal 1 dus = 24 pcs)
                $table->decimal('selling_price', 15, 2);
                $table->decimal('purchase_price', 15, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['product_id', 'is_active']);
            });
        }

        if (Schema::hasTable('transaction_details')) {
            Schema::table('transaction_details', function (Blueprint $table) {
                if (! Schema::hasColumn('transaction_details', 'product_unit_id')) {
                    $table->foreignId('product_unit_id')->nullable()->after('product_id')->constrained('product_units')->nullOnDelete();
                }
                if (! Schema::hasColumn('transaction_details', 'conversion_factor')) {
                    $table->decimal('conversion_factor', 12, 3)->default(1.000)->after('quantity');
                }
                if (! Schema::hasColumn('transaction_details', 'base_quantity')) {
                    $table->decimal('base_quantity', 12, 3)->nullable()->after('conversion_factor');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transaction_details')) {
            Schema::table('transaction_details', function (Blueprint $table) {
                if (Schema::hasColumn('transaction_details', 'product_unit_id')) {
                    $table->dropForeign(['product_unit_id']);
                    $table->dropColumn('product_unit_id');
                }
                if (Schema::hasColumn('transaction_details', 'conversion_factor')) {
                    $table->dropColumn('conversion_factor');
                }
                if (Schema::hasColumn('transaction_details', 'base_quantity')) {
                    $table->dropColumn('base_quantity');
                }
            });
        }

        Schema::dropIfExists('product_units');
    }
};

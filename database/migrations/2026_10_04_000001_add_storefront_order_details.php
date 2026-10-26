<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('customer_name', 150)->nullable();
            $table->string('customer_phone', 40)->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('sales_channel', 30)->default('pos');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'customer_phone', 'shipping_address', 'sales_channel']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->string('payment_status', 20)
                ->default('pending')
                ->after('payment_method');

            $table->timestamp('completed_at')
                ->nullable()
                ->after('status');
        });

        DB::table('transactions')
            ->where('status', 'completed')
            ->update([
                'payment_status' => 'paid',
                'completed_at' => DB::raw(
                    'COALESCE(updated_at, created_at)'
                ),
            ]);
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropColumn([
                'payment_status',
                'completed_at',
            ]);
        });
    }
};
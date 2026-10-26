<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('wa_number', 40)->nullable()->unique();
            $table->text('alamat')->nullable();
        });

        DB::table('users')->whereNull('wa_number')->whereNotNull('phone')->update([
            'wa_number' => DB::raw('phone'),
        ]);
        DB::table('users')->whereNull('alamat')->whereNotNull('address')->update([
            'alamat' => DB::raw('address'),
        ]);
        DB::table('users')->where('role', 'owner')->update(['role' => 'admin_toko']);
        DB::table('users')->where('role', 'admin')->update(['role' => 'admin_toko']);
        DB::table('users')->where('role', 'cashier')->update(['role' => 'kasir']);
        DB::table('users')->where('role', 'customer')->update(['role' => 'pembeli']);

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(255) NOT NULL DEFAULT 'pembeli'");
        }
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'admin_toko')->update(['role' => 'owner']);
        DB::table('users')->where('role', 'kasir')->update(['role' => 'cashier']);
        DB::table('users')->where('role', 'pembeli')->update(['role' => 'customer']);

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role VARCHAR(255) NOT NULL DEFAULT 'cashier'");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['wa_number']);
            $table->dropColumn(['wa_number', 'alamat']);
        });
    }
};

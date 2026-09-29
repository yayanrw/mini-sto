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
            $table->string('area')->nullable()->index()->after('phone');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->string('area')->nullable()->index()->after('address');
        });

        // Toko lama mewarisi area pembuatnya. Saat migrasi pertama semua user
        // berarea null, jadi ini tidak mengubah apa pun; tetap aman dijalankan ulang.
        DB::statement('UPDATE stores JOIN users ON users.id = stores.created_by SET stores.area = users.area');
    }

    public function down(): void
    {
        Schema::table('stores', fn (Blueprint $table) => $table->dropColumn('area'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('area'));
    }
};

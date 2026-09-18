<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
        });

        // sku punya unique index biasa yang gak ngerti soft-delete — produk
        // yang di-soft-delete masih fisik ada di tabel, jadi sku-nya masih
        // "kepakai" untuk index lama. Ganti ke unique(sku, deleted_at):
        // deleted_at NULL cuma boleh satu per sku (produk aktif), sedangkan
        // baris yang sudah di-trash (deleted_at beda-beda per waktu hapus)
        // gak akan pernah bentrok satu sama lain atau dengan produk aktif baru.
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_sku_unique');
            $table->unique(['sku', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['sku', 'deleted_at']);
            $table->unique('sku');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};

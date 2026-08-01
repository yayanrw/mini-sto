<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = [
            ['name' => 'Super Admin', 'email' => 'super@ministo.test', 'role' => 'superadmin'],
            ['name' => 'Admin Gudang', 'email' => 'admin@ministo.test', 'role' => 'admin'],
            ['name' => 'Budi Sales', 'email' => 'budi@ministo.test', 'role' => 'sales'],
            ['name' => 'Sari Sales', 'email' => 'sari@ministo.test', 'role' => 'sales'],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                [...$user, 'password' => 'password', 'phone' => '08120000000'],
            );
        }

        $products = [
            ['name' => 'Keripik Singkong Original', 'sku' => 'KSO-250', 'unit' => 'bungkus'],
            ['name' => 'Keripik Singkong Balado', 'sku' => 'KSB-250', 'unit' => 'bungkus'],
            ['name' => 'Kacang Telur', 'sku' => 'KCT-200', 'unit' => 'bungkus'],
            ['name' => 'Sambal Roa Botol', 'sku' => 'SRB-150', 'unit' => 'botol'],
            ['name' => 'Kopi Bubuk Robusta', 'sku' => 'KBR-100', 'unit' => 'pack'],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(['sku' => $product['sku']], $product);
        }
    }
}

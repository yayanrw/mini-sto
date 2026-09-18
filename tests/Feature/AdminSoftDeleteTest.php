<?php

namespace Tests\Feature;

use App\Actions\RecordVisit;
use App\Livewire\Admin\Products;
use App\Livewire\Admin\Stores;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $sales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'password', 'role' => 'admin',
        ]);
        $this->sales = User::create([
            'name' => 'Budi', 'email' => 'budi@test.local', 'password' => 'password', 'role' => 'sales',
        ]);
    }

    public function test_admin_bisa_hapus_dan_memulihkan_toko(): void
    {
        $store = Store::create(['name' => 'Toko Maju', 'created_by' => $this->sales->id]);

        Livewire::actingAs($this->admin)->test(Stores::class)
            ->call('delete', $store->id)
            ->assertSet('trashed', false);

        $this->assertSoftDeleted('stores', ['id' => $store->id]);
        $this->assertNull(Store::find($store->id));
        $this->assertNotNull(Store::withTrashed()->find($store->id));

        Livewire::actingAs($this->admin)->test(Stores::class)
            ->assertDontSee('Toko Maju')
            ->call('toggleTrashed')
            ->assertSee('Toko Maju')
            ->call('restore', $store->id);

        $this->assertNotNull(Store::find($store->id));
        $this->assertDatabaseHas('stores', ['id' => $store->id, 'deleted_at' => null]);
    }

    public function test_toko_terhapus_hilang_dari_daftar_default_admin(): void
    {
        $store = Store::create(['name' => 'Toko Sunyi', 'created_by' => $this->sales->id]);
        $store->delete();

        Livewire::actingAs($this->admin)->test(Stores::class)
            ->assertDontSee('Toko Sunyi');
    }

    public function test_admin_bisa_hapus_dan_memulihkan_produk(): void
    {
        $product = Product::create(['name' => 'Keripik', 'unit' => 'bungkus']);

        Livewire::actingAs($this->admin)->test(Products::class)
            ->call('delete', $product->id);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertNull(Product::find($product->id));

        Livewire::actingAs($this->admin)->test(Products::class)
            ->assertDontSee('Keripik')
            ->call('toggleTrashed')
            ->assertSee('Keripik')
            ->call('restore', $product->id);

        $this->assertNotNull(Product::find($product->id));
    }

    public function test_produk_terhapus_hilang_dari_daftar_default_admin(): void
    {
        $product = Product::create(['name' => 'Sirup', 'unit' => 'botol']);
        $product->delete();

        Livewire::actingAs($this->admin)->test(Products::class)
            ->assertDontSee('Sirup');
    }

    public function test_sales_tidak_bisa_akses_halaman_toko_dan_produk_admin(): void
    {
        $this->actingAs($this->sales)->get(route('admin.stores'))->assertForbidden();
        $this->actingAs($this->sales)->get(route('admin.products'))->assertForbidden();
    }

    public function test_produk_dengan_sisa_stok_tidak_bisa_dihapus(): void
    {
        $store = Store::create(['name' => 'Toko Maju', 'created_by' => $this->sales->id]);
        $product = Product::create(['name' => 'Keripik', 'unit' => 'bungkus']);

        app(RecordVisit::class)($store, $this->sales, [
            ['product_id' => $product->id, 'qty_found' => 0, 'qty_added' => 20],
        ]);

        Livewire::actingAs($this->admin)->test(Products::class)
            ->call('delete', $product->id);

        $this->assertNull($product->fresh()->deleted_at);

        app(RecordVisit::class)($store, $this->sales, [
            ['product_id' => $product->id, 'qty_found' => 0, 'qty_added' => 0],
        ]);

        Livewire::actingAs($this->admin)->test(Products::class)
            ->call('delete', $product->id);

        $this->assertNotNull($product->fresh()->deleted_at);
    }

    public function test_sku_produk_terhapus_bisa_dipakai_ulang(): void
    {
        $old = Product::create(['name' => 'Keripik Lama', 'sku' => 'SKU-001', 'unit' => 'bungkus']);
        $old->delete();

        Livewire::actingAs($this->admin)->test(Products::class)
            ->set('name', 'Keripik Baru')
            ->set('sku', 'SKU-001')
            ->set('unit', 'bungkus')
            ->call('save')
            ->assertHasNoErrors('sku');

        $this->assertDatabaseHas('products', [
            'name' => 'Keripik Baru', 'sku' => 'SKU-001', 'deleted_at' => null,
        ]);
        $this->assertSoftDeleted('products', ['id' => $old->id]);
    }
}

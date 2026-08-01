<?php

namespace Tests\Feature;

use App\Actions\RecordVisit;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecordVisitTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;

    private Store $store;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sales = User::create([
            'name' => 'Budi', 'email' => 'budi@test.local', 'password' => 'password', 'role' => 'sales',
        ]);
        $this->store = Store::create(['name' => 'Toko Maju', 'created_by' => $this->sales->id]);
        $this->product = Product::create(['name' => 'Keripik', 'unit' => 'bungkus']);
    }

    private function record(array $lines)
    {
        return app(RecordVisit::class)($this->store, $this->sales, $lines);
    }

    public function test_kunjungan_pertama_mulai_dari_nol(): void
    {
        $visit = $this->record([
            ['product_id' => $this->product->id, 'qty_found' => 0, 'qty_added' => 20],
        ]);

        $item = $visit->items->first();

        $this->assertSame(0, $item->qty_before);
        $this->assertSame(0, $item->qty_sold);
        $this->assertSame(20, $item->qty_left);
    }

    public function test_kunjungan_kedua_menghitung_terjual_dan_stok_akhir(): void
    {
        $this->record([['product_id' => $this->product->id, 'qty_found' => 0, 'qty_added' => 20]]);

        $visit = $this->record([['product_id' => $this->product->id, 'qty_found' => 8, 'qty_added' => 10]]);
        $item = $visit->items->first();

        $this->assertSame(20, $item->qty_before);
        $this->assertSame(12, $item->qty_sold);
        $this->assertSame(18, $item->qty_left);
    }

    public function test_stok_akhir_jadi_baseline_kunjungan_berikutnya(): void
    {
        $this->record([['product_id' => $this->product->id, 'qty_found' => 0, 'qty_added' => 20]]);
        $this->record([['product_id' => $this->product->id, 'qty_found' => 8, 'qty_added' => 10]]);

        $visit = $this->record([['product_id' => $this->product->id, 'qty_found' => 5, 'qty_added' => 0]]);
        $item = $visit->items->first();

        $this->assertSame(18, $item->qty_before);
        $this->assertSame(13, $item->qty_sold);
        $this->assertSame(5, $item->qty_left);
    }

    public function test_sisa_lebih_besar_dari_titipan_ditolak(): void
    {
        $this->record([['product_id' => $this->product->id, 'qty_found' => 0, 'qty_added' => 20]]);

        $this->expectException(ValidationException::class);
        $this->record([['product_id' => $this->product->id, 'qty_found' => 25, 'qty_added' => 0]]);
    }

    public function test_produk_dengan_sisa_tidak_boleh_dilewat(): void
    {
        $other = Product::create(['name' => 'Kacang', 'unit' => 'bungkus']);

        $this->record([['product_id' => $this->product->id, 'qty_found' => 0, 'qty_added' => 20]]);

        $this->expectException(ValidationException::class);
        $this->record([['product_id' => $other->id, 'qty_found' => 0, 'qty_added' => 5]]);
    }

    public function test_kunjungan_kosong_ditolak(): void
    {
        $this->expectException(ValidationException::class);
        $this->record([['product_id' => $this->product->id, 'qty_found' => 0, 'qty_added' => 0]]);
    }

    public function test_sales_tidak_bisa_membuka_panel_admin(): void
    {
        $this->actingAs($this->sales)->get('/admin')->assertForbidden();
    }

    public function test_admin_bisa_membuka_panel_admin(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'password', 'role' => 'admin',
        ]);

        $this->actingAs($admin)->get('/admin')->assertOk();
    }
}

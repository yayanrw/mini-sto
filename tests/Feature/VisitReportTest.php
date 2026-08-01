<?php

namespace Tests\Feature;

use App\Actions\RecordVisit;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Reports\VisitReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_dan_totalnya_konsisten(): void
    {
        $sales = User::create([
            'name' => 'Budi', 'email' => 'budi@test.local', 'password' => 'password', 'role' => 'sales',
        ]);
        $store = Store::create(['name' => 'Toko Maju', 'created_by' => $sales->id]);
        $product = Product::create(['name' => 'Keripik', 'unit' => 'bungkus']);

        $record = app(RecordVisit::class);
        $record($store, $sales, [['product_id' => $product->id, 'qty_found' => 0, 'qty_added' => 20]]);
        $record($store, $sales, [['product_id' => $product->id, 'qty_found' => 8, 'qty_added' => 10]]);

        $filters = ['from' => now()->subDay()->toDateString(), 'to' => now()->toDateString()];

        // Query baris: tanggal harus datang sebagai Carbon, bukan string.
        $rows = VisitReport::query($filters)->get();
        $this->assertCount(2, $rows);
        $this->assertNotNull($rows->first()->visited_at->format('Y-m-d'));

        // Totals dijalankan di MySQL dengan ONLY_FULL_GROUP_BY aktif.
        $totals = VisitReport::totals($filters);
        $this->assertSame(12, (int) $totals->sold);
        $this->assertSame(30, (int) $totals->added);
        $this->assertSame(2, (int) $totals->visit_count);
    }

    public function test_halaman_report_admin_terbuka(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'password', 'role' => 'admin',
        ]);

        $this->actingAs($admin)->get('/admin/report')->assertOk();
        $this->actingAs($admin)->get('/admin/report/csv')->assertOk();
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Performance;
use App\Models\Store;
use App\Models\User;
use App\Reports\VisitReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\BuildsAreaFixtures;
use Tests\TestCase;

class AreaReportsTest extends TestCase
{
    use BuildsAreaFixtures, RefreshDatabase;

    private User $koor;

    private User $salesA;

    private User $salesB;

    private Store $storeA;

    private Store $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->koor = $this->user('admin', 'Kab. Malang');
        $this->salesA = $this->user('sales', 'Kab. Malang');
        $this->salesB = $this->user('sales', 'Kota Kediri');
        $this->storeA = $this->store('Kab. Malang', $this->salesA, 'Toko Dalam');
        $this->storeB = $this->store('Kota Kediri', $this->salesB, 'Toko Luar');
        $this->storeA->update(['lat' => -7.9, 'lng' => 112.6]);
        $this->storeB->update(['lat' => -7.8, 'lng' => 112.0]);

        $this->visit($this->storeA, $this->salesA, added: 10);
        $this->visit($this->storeB, $this->salesB, added: 99);
    }

    private function filters(): array
    {
        return ['from' => now()->subDay()->toDateString(), 'to' => now()->toDateString()];
    }

    public function test_visit_report_query_dan_totals_ter_scope(): void
    {
        $this->assertCount(1, VisitReport::query($this->filters(), $this->koor)->get());
        $this->assertSame(10, (int) VisitReport::totals($this->filters(), $this->koor)->added);

        $super = $this->user('superadmin');
        $this->assertSame(109, (int) VisitReport::totals($this->filters(), $super)->added);
        // Tanpa viewer = perilaku lama (dipakai tes lama).
        $this->assertSame(109, (int) VisitReport::totals($this->filters())->added);
    }

    public function test_filter_area_untuk_superadmin(): void
    {
        $totals = VisitReport::totals([...$this->filters(), 'area' => 'Kota Kediri'], $this->user('superadmin'));

        $this->assertSame(99, (int) $totals->added);
    }

    public function test_koordinator_tanpa_area_laporan_kosong(): void
    {
        $nullKoor = $this->user('admin');

        $this->assertCount(0, VisitReport::query($this->filters(), $nullKoor)->get());
        $this->assertSame(0, (int) VisitReport::totals($this->filters(), $nullKoor)->added);
    }

    public function test_kunjungan_toko_terhapus_tetap_terhitung(): void
    {
        $this->storeA->delete();

        $this->assertSame(10, (int) VisitReport::totals($this->filters(), $this->koor)->added);
    }

    public function test_csv_hanya_data_area_koordinator(): void
    {
        $csv = $this->actingAs($this->koor)->get(route('admin.reports.csv'))->streamedContent();

        $this->assertStringContainsString('Toko Dalam', $csv);
        $this->assertStringNotContainsString('Toko Luar', $csv);
    }

    public function test_dashboard_ter_scope(): void
    {
        Livewire::actingAs($this->koor)->test(Dashboard::class)
            ->assertViewHas('totalStores', 1)
            ->assertViewHas('visitCount', 1)
            ->assertViewHas('added', 10);
    }

    public function test_peta_ter_scope(): void
    {
        // Marker dirender lewat @push('scripts') di layout, jadi butuh request penuh.
        $this->actingAs($this->koor)->get(route('admin.map'))
            ->assertOk()
            ->assertSee('Toko Dalam')
            ->assertDontSee('Toko Luar');
    }

    public function test_performa_ter_scope(): void
    {
        Livewire::actingAs($this->koor)->test(Performance::class)
            ->assertSee($this->salesA->name)
            ->assertDontSee($this->salesB->name);
    }

    public function test_halaman_report_dropdown_ter_scope(): void
    {
        $this->actingAs($this->koor)->get(route('admin.reports'))
            ->assertOk()
            ->assertSee('Toko Dalam')
            ->assertDontSee('Toko Luar');
    }
}

<?php

namespace Tests\Feature;

use App\Reports\AreaRollup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsAreaFixtures;
use Tests\TestCase;

class AreaRollupTest extends TestCase
{
    use BuildsAreaFixtures, RefreshDatabase;

    private function range(): array
    {
        return [now()->startOfMonth(), now()->endOfDay()];
    }

    public function test_angka_per_area_koordinator_dan_sales(): void
    {
        $koor = $this->user('admin', 'Kab. Malang');
        $sales = $this->user('sales', 'Kab. Malang');
        $storeA = $this->store('Kab. Malang', $sales);
        // Dua kunjungan (bisa dalam detik yang sama): sisa akhir harus 18, bukan 10 + 18.
        $this->visit($storeA, $sales, found: 0, added: 20);
        $this->visit($storeA, $sales, found: 8, added: 10);

        $other = $this->user('sales', 'Kota Kediri');
        $this->visit($this->store('Kota Kediri', $other), $other, added: 5);

        $rows = AreaRollup::rows($this->range())->keyBy('area');

        $malang = $rows['Kab. Malang'];
        $this->assertSame([$koor->id], $malang['koordinators']->pluck('id')->all());
        $this->assertSame(1, $malang['stores']);
        $this->assertSame(2, $malang['visits']);
        $this->assertSame(12, $malang['sold']);
        $this->assertSame(18, $malang['remaining']);

        $row = $malang['sales']->first();
        $this->assertSame($sales->id, $row['user']->id);
        $this->assertSame([1, 2, 12, 18], [$row['stores'], $row['visits'], $row['sold'], $row['remaining']]);

        $kediri = $rows['Kota Kediri'];
        $this->assertSame([1, 1, 0, 5], [$kediri['stores'], $kediri['visits'], $kediri['sold'], $kediri['remaining']]);
    }

    public function test_area_null_muncul_di_akhir(): void
    {
        $this->store(null);
        $this->store('Kab. Malang');

        $areas = AreaRollup::rows($this->range())->pluck('area')->all();

        $this->assertSame(['Kab. Malang', null], $areas);
    }

    public function test_halaman_hanya_superadmin(): void
    {
        $this->actingAs($this->user('admin', 'Kab. Malang'))->get(route('admin.areas'))->assertForbidden();

        $this->actingAs($this->user('superadmin'))->get(route('admin.areas'))
            ->assertOk()
            ->assertSee('Rollup Area');
    }

    public function test_halaman_menampilkan_area_dan_tanpa_area(): void
    {
        $this->store('Kab. Malang');
        $this->store(null);

        $this->actingAs($this->user('superadmin'))->get(route('admin.areas'))
            ->assertSee('Kab. Malang')
            ->assertSee('Tanpa area');
    }
}

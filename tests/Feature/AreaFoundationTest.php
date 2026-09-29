<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Support\Areas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsAreaFixtures;
use Tests\TestCase;

class AreaFoundationTest extends TestCase
{
    use BuildsAreaFixtures, RefreshDatabase;

    public function test_daftar_area_38_unik_dan_membedakan_kab_dan_kota(): void
    {
        $all = Areas::all();

        $this->assertCount(38, $all);
        $this->assertCount(38, array_unique($all));
        $this->assertContains('Kab. Malang', $all);
        $this->assertContains('Kota Malang', $all);
    }

    public function test_role_label(): void
    {
        $this->assertSame('Koordinator Area', $this->user('admin')->roleLabel());
        $this->assertSame('Sales', $this->user('sales')->roleLabel());
        $this->assertSame('Superadmin', $this->user('superadmin')->roleLabel());
    }

    public function test_can_access_area(): void
    {
        $koor = $this->user('admin', 'Kab. Malang');

        $this->assertTrue($koor->canAccessArea('Kab. Malang'));
        $this->assertFalse($koor->canAccessArea('Kota Malang'));
        $this->assertFalse($koor->canAccessArea(null));
        $this->assertFalse($this->user('admin')->canAccessArea(null), 'koordinator tanpa area tidak boleh apa pun');
        $this->assertFalse($this->user('sales', 'Kab. Malang')->canAccessArea('Kab. Malang'));
        $this->assertTrue($this->user('superadmin')->canAccessArea(null));
    }

    public function test_store_visible_to(): void
    {
        $this->store('Kab. Malang', null, 'A');
        $this->store('Kota Kediri', null, 'B');
        $this->store(null, null, 'N');

        $names = fn (User $u) => Store::visibleTo($u)->orderBy('name')->pluck('name')->all();

        $this->assertSame(['A'], $names($this->user('admin', 'Kab. Malang')));
        // Koordinator tanpa area: kosong, BUKAN toko berarea null.
        $this->assertSame([], $names($this->user('admin')));
        $this->assertSame(['A', 'B', 'N'], $names($this->user('superadmin')));
        $this->assertSame([], $names($this->user('sales', 'Kab. Malang')));
    }

    public function test_user_visible_to_hanya_sales_di_area_koordinator(): void
    {
        $inArea = $this->user('sales', 'Kab. Malang');
        $this->user('sales', 'Kota Kediri');
        $this->user('admin', 'Kab. Malang'); // koordinator lain di area sama: bukan sales
        $this->user('sales'); // tanpa area

        $ids = User::visibleTo($this->user('admin', 'Kab. Malang'))->pluck('id')->all();

        $this->assertSame([$inArea->id], $ids);
        $this->assertSame([], User::visibleTo($this->user('admin'))->pluck('id')->all());
    }

    public function test_visible_ids_menyertakan_toko_yang_dihapus(): void
    {
        $store = $this->store('Kab. Malang');
        $store->delete();

        $ids = Store::visibleIds($this->user('admin', 'Kab. Malang'))->pluck('id')->all();

        $this->assertSame([$store->id], $ids);
    }

    public function test_area_suffix(): void
    {
        $this->assertSame(' · Kab. Malang', $this->user('admin', 'Kab. Malang')->areaSuffix());
        $this->assertSame('', $this->user('admin')->areaSuffix());
        $this->assertSame('', $this->user('superadmin', 'Kab. Malang')->areaSuffix());
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Admin\Sales as AdminSales;
use App\Livewire\Admin\Stores;
use App\Livewire\Sales\StoreCreate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Concerns\BuildsAreaFixtures;
use Tests\TestCase;

class AreaCopyTest extends TestCase
{
    use BuildsAreaFixtures, RefreshDatabase;

    public function test_sales_membuat_toko_area_disalin_dari_sales(): void
    {
        Storage::fake('public');
        $sales = $this->user('sales', 'Kab. Malang');

        // store => null eksplisit: tanpa itu Livewire::test menyuntik Store kosong, bukan null.
        Livewire::actingAs($sales)->test(StoreCreate::class, ['store' => null])
            ->set('name', 'Toko Baru')
            ->set('photo', UploadedFile::fake()->image('a.jpg'))
            ->call('setLocation', -7.9, 112.6)
            ->call('save');

        $this->assertDatabaseHas('stores', ['name' => 'Toko Baru', 'area' => 'Kab. Malang', 'created_by' => $sales->id]);
    }

    public function test_admin_membuat_toko_area_ikut_sales_yang_dipilih(): void
    {
        $sales = $this->user('sales', 'Kota Kediri');

        Livewire::actingAs($this->user('superadmin'))->test(StoreCreate::class, ['store' => null])
            ->set('name', 'Toko Admin')
            ->set('assignedTo', $sales->id)
            ->call('save');

        $this->assertDatabaseHas('stores', ['name' => 'Toko Admin', 'area' => 'Kota Kediri']);
    }

    public function test_pindah_toko_ke_sales_beda_area_area_toko_ikut_pindah(): void
    {
        $from = $this->user('sales', 'Kab. Malang');
        $to = $this->user('sales', 'Kota Kediri');
        $store = $this->store('Kab. Malang', $from);

        Livewire::actingAs($this->user('superadmin'))->test(Stores::class)
            ->call('startMove', $store->id)
            ->set('moveToId', $to->id)
            ->call('moveStore');

        $this->assertSame('Kota Kediri', $store->fresh()->area);
        $this->assertSame($to->id, $store->fresh()->created_by);
    }

    public function test_pindah_ke_sales_tanpa_area_membuat_area_toko_null(): void
    {
        $from = $this->user('sales', 'Kab. Malang');
        $to = $this->user('sales');
        $store = $this->store('Kab. Malang', $from);

        Livewire::actingAs($this->user('superadmin'))->test(Stores::class)
            ->call('startMove', $store->id)
            ->set('moveToId', $to->id)
            ->call('moveStore');

        $this->assertNull($store->fresh()->area);
    }

    public function test_pindah_massal_menyalin_area_sales_tujuan(): void
    {
        $from = $this->user('sales', 'Kab. Malang');
        $to = $this->user('sales', 'Kota Kediri');
        $a = $this->store('Kab. Malang', $from);
        $b = $this->store('Kab. Malang', $from);

        Livewire::actingAs($this->user('superadmin'))->test(AdminSales::class)
            ->call('startMove', $from->id)
            ->set('moveToId', $to->id)
            ->call('moveStores');

        $this->assertSame('Kota Kediri', $a->fresh()->area);
        $this->assertSame('Kota Kediri', $b->fresh()->area);
    }

    public function test_ubah_area_sales_tidak_mengubah_area_toko_lama(): void
    {
        $sales = $this->user('sales', 'Kab. Malang');
        $store = $this->store('Kab. Malang', $sales);

        $sales->update(['area' => 'Kota Kediri']);

        $this->assertSame('Kab. Malang', $store->fresh()->area);
    }

    public function test_edit_toko_tanpa_ganti_sales_tidak_mengubah_area(): void
    {
        $sales = $this->user('sales', 'Kab. Malang');
        $store = $this->store('Kab. Malang', $sales);
        $sales->update(['area' => 'Kota Kediri']); // area sales berubah setelah toko dibuat

        Livewire::actingAs($this->user('superadmin'))->test(StoreCreate::class, ['store' => $store])
            ->set('name', 'Nama Baru')
            ->call('save');

        $this->assertSame('Kab. Malang', $store->fresh()->area);
        $this->assertSame('Nama Baru', $store->fresh()->name);
    }
}

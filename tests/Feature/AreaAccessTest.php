<?php

namespace Tests\Feature;

use App\Livewire\Admin\Sales as AdminSales;
use App\Livewire\Admin\Stores;
use App\Livewire\Sales\StoreCreate;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\BuildsAreaFixtures;
use Tests\TestCase;

class AreaAccessTest extends TestCase
{
    use BuildsAreaFixtures, RefreshDatabase;

    private User $koor;

    private User $salesA;

    private User $salesB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->koor = $this->user('admin', 'Kab. Malang');
        $this->salesA = $this->user('sales', 'Kab. Malang');
        $this->salesB = $this->user('sales', 'Kota Kediri');
    }

    public function test_daftar_toko_hanya_area_koordinator(): void
    {
        $this->store('Kab. Malang', $this->salesA, 'Toko Dalam');
        $this->store('Kota Kediri', $this->salesB, 'Toko Luar');

        Livewire::actingAs($this->koor)->test(Stores::class)
            ->assertSee('Toko Dalam')
            ->assertDontSee('Toko Luar');
    }

    public function test_koordinator_tanpa_area_tidak_melihat_toko_apa_pun(): void
    {
        $this->store(null, null, 'Toko Tanpa Area');

        Livewire::actingAs($this->user('admin'))->test(Stores::class)
            ->assertDontSee('Toko Tanpa Area');
    }

    public function test_aksi_toko_lintas_area_403(): void
    {
        $luar = $this->store('Kota Kediri', $this->salesB);
        $luar->delete();
        $luarAktif = $this->store('Kota Kediri', $this->salesB);

        // Instance baru per aksi: komponen tidak bisa dipakai lagi setelah respons 403.
        $component = fn () => Livewire::actingAs($this->koor)->test(Stores::class);

        $component()->call('edit', $luarAktif->id)->assertForbidden();
        $component()->call('delete', $luarAktif->id)->assertForbidden();
        $component()->call('restore', $luar->id)->assertForbidden();

        $this->assertNull($luarAktif->fresh()->deleted_at);
    }

    public function test_simpan_toko_dengan_editing_id_dimanipulasi_403(): void
    {
        $luar = $this->store('Kota Kediri', $this->salesB);

        Livewire::actingAs($this->koor)->test(Stores::class)
            ->set('editingId', $luar->id)
            ->set('name', 'Dibajak')
            ->call('save')
            ->assertForbidden();

        $this->assertNotSame('Dibajak', $luar->fresh()->name);
    }

    public function test_pindah_toko_ke_sales_area_lain_ditolak(): void
    {
        $dalam = $this->store('Kab. Malang', $this->salesA);

        // Target di luar area tidak ditemukan (Livewire::test melempar exception-nya, bukan respons 404).
        try {
            Livewire::actingAs($this->koor)->test(Stores::class)
                ->call('startMove', $dalam->id)
                ->set('moveToId', $this->salesB->id)
                ->call('moveStore');

            $this->fail('Pemindahan ke sales area lain seharusnya ditolak.');
        } catch (ModelNotFoundException) {
        }

        $this->assertSame($this->salesA->id, $dalam->fresh()->created_by);
    }

    public function test_daftar_sales_dan_aksi_lintas_area(): void
    {
        Livewire::actingAs($this->koor)->test(AdminSales::class)
            ->assertSee($this->salesA->name)
            ->assertDontSee($this->salesB->name)
            ->call('edit', $this->salesB->id)
            ->assertForbidden();
    }

    public function test_simpan_sales_dengan_editing_id_dimanipulasi_403(): void
    {
        Livewire::actingAs($this->koor)->test(AdminSales::class)
            ->set('editingId', $this->salesB->id)
            ->set('name', 'Dibajak')
            ->set('email', $this->salesB->email)
            ->call('save')
            ->assertForbidden();
    }

    public function test_koordinator_membuat_sales_area_dipaksa_area_sendiri(): void
    {
        Livewire::actingAs($this->koor)->test(AdminSales::class)
            ->set('name', 'Sales Baru')
            ->set('email', 'baru@test.local')
            ->set('password', 'password123')
            ->set('area', 'Kota Kediri') // dicoba dibelokkan; harus diabaikan
            ->call('save');

        $this->assertDatabaseHas('users', ['email' => 'baru@test.local', 'role' => 'sales', 'area' => 'Kab. Malang']);
    }

    public function test_koordinator_tanpa_area_tidak_bisa_membuat_sales(): void
    {
        Livewire::actingAs($this->user('admin'))->test(AdminSales::class)
            ->set('name', 'Sales Baru')
            ->set('email', 'baru@test.local')
            ->set('password', 'password123')
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'baru@test.local']);
    }

    public function test_pindah_massal_ke_sales_area_lain_ditolak(): void
    {
        $store = $this->store('Kab. Malang', $this->salesA);

        try {
            Livewire::actingAs($this->koor)->test(AdminSales::class)
                ->call('startMove', $this->salesA->id)
                ->set('moveToId', $this->salesB->id)
                ->call('moveStores');

            $this->fail('Pemindahan massal ke sales area lain seharusnya ditolak.');
        } catch (ModelNotFoundException) {
        }

        $this->assertSame($this->salesA->id, $store->fresh()->created_by);
    }

    public function test_store_show_lintas_area_403(): void
    {
        $luar = $this->store('Kota Kediri', $this->salesB);
        $dalam = $this->store('Kab. Malang', $this->salesA);

        $this->actingAs($this->koor)->get(route('admin.stores.show', $luar))->assertForbidden();
        $this->actingAs($this->koor)->get(route('admin.stores.show', $dalam))->assertOk();
    }

    public function test_store_create_edit_lintas_area_403_dan_assign_ke_area_lain_403(): void
    {
        $luar = $this->store('Kota Kediri', $this->salesB);
        $dalam = $this->store('Kab. Malang', $this->salesA);

        $this->actingAs($this->koor)->get(route('stores.edit', $luar))->assertForbidden();
        $this->actingAs($this->koor)->get(route('stores.edit', $dalam))->assertOk();

        Livewire::actingAs($this->koor)->test(StoreCreate::class, ['store' => null])
            ->set('name', 'Toko X')
            ->set('assignedTo', $this->salesB->id)
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseMissing('stores', ['name' => 'Toko X']);
    }

    public function test_visit_form_lintas_area_403(): void
    {
        $luar = $this->store('Kota Kediri', $this->salesB);
        $dalam = $this->store('Kab. Malang', $this->salesA);

        $this->actingAs($this->koor)->get(route('visits.create', $luar))->assertForbidden();
        $this->actingAs($this->koor)->get(route('visits.create', $dalam))->assertOk();
        // Sales tetap hanya boleh toko miliknya sendiri.
        $this->actingAs($this->salesA)->get(route('visits.create', $dalam))->assertOk();
        $this->actingAs($this->salesA)->get(route('visits.create', $luar))->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Admin\Sales as AdminSales;
use App\Livewire\Admin\Stores;
use App\Livewire\Admin\Users;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Concerns\BuildsAreaFixtures;
use Tests\TestCase;

class AreaUiTest extends TestCase
{
    use BuildsAreaFixtures, RefreshDatabase;

    public function test_users_simpan_area_valid_dan_tolak_di_luar_daftar(): void
    {
        $super = $this->user('superadmin');

        Livewire::actingAs($super)->test(Users::class)
            ->set('name', 'Koor')->set('email', 'koor@test.local')->set('role', 'admin')
            ->set('password', 'password123')->set('area', 'Atlantis')
            ->call('save')
            ->assertHasErrors('area');

        Livewire::actingAs($super)->test(Users::class)
            ->set('name', 'Koor')->set('email', 'koor@test.local')->set('role', 'admin')
            ->set('password', 'password123')->set('area', 'Kab. Malang')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'koor@test.local', 'area' => 'Kab. Malang']);
    }

    public function test_users_area_kosong_disimpan_null(): void
    {
        Livewire::actingAs($this->user('superadmin'))->test(Users::class)
            ->set('name', 'X')->set('email', 'x@test.local')->set('role', 'sales')
            ->set('password', 'password123')->set('area', '')
            ->call('save');

        $this->assertDatabaseHas('users', ['email' => 'x@test.local', 'area' => null]);
    }

    public function test_superadmin_sales_area_di_luar_daftar_ditolak(): void
    {
        Livewire::actingAs($this->user('superadmin'))->test(AdminSales::class)
            ->set('name', 'S')->set('email', 's@test.local')->set('password', 'password123')
            ->set('area', 'Atlantis')
            ->call('save')
            ->assertHasErrors('area');
    }

    public function test_label_role_dan_area_tampil_di_pengguna(): void
    {
        $this->user('admin', 'Kab. Malang');

        Livewire::actingAs($this->user('superadmin'))->test(Users::class)
            ->assertSee('Koordinator Area')
            ->assertSee('Kab. Malang');
    }

    public function test_toko_tanpa_area_dan_judul_koordinator(): void
    {
        $this->store(null, null, 'Toko Yatim');
        $koor = $this->user('admin', 'Kab. Malang');
        $this->store('Kab. Malang', null, 'Toko Ada');

        Livewire::actingAs($this->user('superadmin'))->test(Stores::class)
            ->assertSee('Tanpa area');

        Livewire::actingAs($koor)->test(Stores::class)
            ->assertSee('Toko · Kab. Malang');
    }

    public function test_filter_area_hanya_untuk_superadmin(): void
    {
        $this->store('Kab. Malang', null, 'Toko Malang');
        $this->store('Kota Kediri', null, 'Toko Kediri');

        Livewire::actingAs($this->user('superadmin'))->test(Stores::class)
            ->set('area', 'Kota Kediri')
            ->assertSee('Toko Kediri')
            ->assertDontSee('Toko Malang');

        // Koordinator: nilai area dari client tidak melebarkan hasil.
        Livewire::actingAs($this->user('admin', 'Kab. Malang'))->test(Stores::class)
            ->set('area', 'Kota Kediri')
            ->assertSee('Toko Malang')
            ->assertDontSee('Toko Kediri');
    }

    public function test_peta_filter_area_lewat_url(): void
    {
        $this->store('Kab. Malang', null, 'Toko Malang')->update(['lat' => -7.9, 'lng' => 112.6]);
        $this->store('Kota Kediri', null, 'Toko Kediri')->update(['lat' => -7.8, 'lng' => 112.0]);

        $this->actingAs($this->user('superadmin'))->get(route('admin.map', ['area' => 'Kota Kediri']))
            ->assertSee('Toko Kediri')
            ->assertDontSee('Toko Malang');
    }
}

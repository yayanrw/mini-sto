<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsAreaFixtures;
use Tests\TestCase;

class AreaRoutesTest extends TestCase
{
    use BuildsAreaFixtures, RefreshDatabase;

    public function test_koordinator_403_di_produk_dan_pengguna(): void
    {
        $koor = $this->user('admin', 'Kab. Malang');

        $this->actingAs($koor)->get('/admin/produk')->assertForbidden();
        $this->actingAs($koor)->get('/admin/pengguna')->assertForbidden();
    }

    public function test_superadmin_boleh_produk_dan_pengguna(): void
    {
        $super = $this->user('superadmin');

        $this->actingAs($super)->get('/admin/produk')->assertOk();
        $this->actingAs($super)->get('/admin/pengguna')->assertOk();
    }

    public function test_nav_koordinator_tanpa_produk_pengguna_area(): void
    {
        $html = $this->actingAs($this->user('admin', 'Kab. Malang'))->get(route('admin.dashboard'))->getContent();

        $this->assertStringNotContainsString(route('admin.products'), $html);
        $this->assertStringNotContainsString(route('admin.users'), $html);
        $this->assertStringNotContainsString(route('admin.areas'), $html);
    }

    public function test_nav_superadmin_memuat_produk_dan_area(): void
    {
        $html = $this->actingAs($this->user('superadmin'))->get(route('admin.dashboard'))->getContent();

        $this->assertStringContainsString(route('admin.products'), $html);
        $this->assertStringContainsString(route('admin.areas'), $html);
    }
}

<?php

namespace Tests\Feature\Concerns;

use App\Actions\RecordVisit;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;

trait BuildsAreaFixtures
{
    private int $fixtureSeq = 0;

    protected function user(string $role, ?string $area = null): User
    {
        $n = ++$this->fixtureSeq;

        return User::create([
            'name' => ucfirst($role)." {$n}",
            'email' => "{$role}{$n}@test.local",
            'password' => 'password',
            'role' => $role,
            'area' => $area,
        ]);
    }

    protected function store(?string $area, ?User $creator = null, string $name = 'Toko'): Store
    {
        return Store::create([
            'name' => $name,
            'area' => $area,
            'created_by' => $creator?->id,
        ]);
    }

    protected function product(): Product
    {
        return Product::firstOrCreate(['sku' => 'KRP-1'], ['name' => 'Keripik', 'unit' => 'bungkus']);
    }

    /** Catat kunjungan lewat RecordVisit (satu-satunya jalur tulis yang sah). */
    protected function visit(Store $store, User $by, int $found = 0, int $added = 10): Visit
    {
        return app(RecordVisit::class)($store, $by, [
            ['product_id' => $this->product()->id, 'qty_found' => $found, 'qty_added' => $added],
        ]);
    }
}

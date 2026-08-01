<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Satu-satunya jalan menulis kunjungan. Semua aturan hitung stok titipan
 * ada di sini supaya tidak tersebar ke Livewire component / controller.
 */
class RecordVisit
{
    /**
     * @param  array<int, array{product_id: int|string, qty_found: int|string|null, qty_added: int|string|null}>  $lines
     * @param  array{lat: float|null, lng: float|null}|null  $gps
     */
    public function __invoke(Store $store, User $user, array $lines, ?array $gps = null, ?string $note = null): Visit
    {
        return DB::transaction(function () use ($store, $user, $lines, $gps, $note) {
            // Kunci baris toko: dua sales tidak boleh menutup baseline yang sama bersamaan.
            $store = Store::query()->lockForUpdate()->findOrFail($store->id);
            $baseline = $store->currentStock();

            $rows = $this->normalize($lines);
            $this->assertNothingLeftOut($baseline, $rows);

            $visit = Visit::create([
                'store_id' => $store->id,
                'user_id' => $user->id,
                'visited_at' => now(),
                'lat' => $gps['lat'] ?? null,
                'lng' => $gps['lng'] ?? null,
                'note' => $note,
            ]);

            $written = 0;

            foreach ($rows as $productId => $row) {
                $before = (int) ($baseline[$productId] ?? 0);
                $found = $row['qty_found'];
                $added = $row['qty_added'];

                if ($found > $before) {
                    throw ValidationException::withMessages([
                        "items.{$productId}.qty_found" => sprintf(
                            'Sisa %s (%d) tidak boleh lebih besar dari titipan sebelumnya (%d). Cek ulang hitungan.',
                            Product::find($productId)?->name ?? "produk #{$productId}",
                            $found,
                            $before,
                        ),
                    ]);
                }

                // Baris kosong tanpa riwayat: tidak perlu dicatat.
                if ($before === 0 && $added === 0) {
                    continue;
                }

                $visit->items()->create([
                    'product_id' => $productId,
                    'qty_before' => $before,
                    'qty_found' => $found,
                    'qty_sold' => $before - $found,
                    'qty_added' => $added,
                    'qty_left' => $found + $added,
                ]);

                $written++;
            }

            if ($written === 0) {
                throw ValidationException::withMessages([
                    'items' => 'Kunjungan kosong. Isi minimal satu produk.',
                ]);
            }

            return $visit->load('items');
        });
    }

    /**
     * @return array<int, array{qty_found: int, qty_added: int}> dikunci per product_id
     */
    private function normalize(array $lines): array
    {
        $rows = [];

        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);

            if ($productId <= 0) {
                continue;
            }

            $found = (int) ($line['qty_found'] ?? 0);
            $added = (int) ($line['qty_added'] ?? 0);

            if ($found < 0 || $added < 0) {
                throw ValidationException::withMessages([
                    "items.{$productId}" => 'Jumlah tidak boleh negatif.',
                ]);
            }

            // Produk kembar dalam satu form: gabung, jangan diam-diam menimpa.
            $rows[$productId] = [
                'qty_found' => ($rows[$productId]['qty_found'] ?? 0) + $found,
                'qty_added' => ($rows[$productId]['qty_added'] ?? 0) + $added,
            ];
        }

        return $rows;
    }

    /**
     * Kunjungan terakhir dipakai sebagai baseline kunjungan berikutnya, jadi ia
     * harus jadi snapshot lengkap: produk yang masih punya sisa wajib dihitung.
     */
    private function assertNothingLeftOut(\Illuminate\Support\Collection $baseline, array $rows): void
    {
        $missing = $baseline
            ->filter(fn ($qty) => $qty > 0)
            ->keys()
            ->reject(fn ($productId) => array_key_exists((int) $productId, $rows));

        if ($missing->isNotEmpty()) {
            $names = Product::whereIn('id', $missing)->pluck('name')->implode(', ');

            throw ValidationException::withMessages([
                'items' => "Produk masih punya sisa dan wajib dihitung: {$names}.",
            ]);
        }
    }
}

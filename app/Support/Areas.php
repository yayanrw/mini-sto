<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

/**
 * Daftar area (kabupaten/kota Jawa Timur). Sengaja hardcode, bukan tabel:
 * cukup untuk sekarang, pindah ke tabel kalau superadmin perlu mengelolanya.
 */
class Areas
{
    private const KABUPATEN = [
        'Bangkalan', 'Banyuwangi', 'Blitar', 'Bojonegoro', 'Bondowoso', 'Gresik', 'Jember', 'Jombang',
        'Kediri', 'Lamongan', 'Lumajang', 'Madiun', 'Magetan', 'Malang', 'Mojokerto', 'Nganjuk', 'Ngawi',
        'Pacitan', 'Pamekasan', 'Pasuruan', 'Ponorogo', 'Probolinggo', 'Sampang', 'Sidoarjo', 'Situbondo',
        'Sumenep', 'Trenggalek', 'Tuban', 'Tulungagung',
    ];

    private const KOTA = [
        'Batu', 'Blitar', 'Kediri', 'Madiun', 'Malang', 'Mojokerto', 'Pasuruan', 'Probolinggo', 'Surabaya',
    ];

    /** @return array<int, string> */
    public static function all(): array
    {
        // Awalan Kab./Kota membedakan area yang namanya sama (mis. Malang).
        $all = [
            ...array_map(fn (string $n) => "Kab. {$n}", self::KABUPATEN),
            ...array_map(fn (string $n) => "Kota {$n}", self::KOTA),
        ];
        sort($all);

        return $all;
    }

    public static function rule(): In
    {
        return Rule::in(self::all());
    }
}

<?php

namespace App\Http\Controllers;

use App\Reports\VisitReport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportCsvController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $filters = $request->only(['from', 'to', 'user_id', 'product_id', 'store_id']);
        $filename = 'report-kunjungan-'.now()->format('Ymd-Hi').'.csv';

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');

            // BOM supaya Excel Windows membaca UTF-8 dengan benar.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tanggal', 'Toko', 'Sales', 'Produk', 'Satuan', 'Titipan awal', 'Sisa', 'Terjual', 'Tambah', 'Stok akhir']);

            // chunkById, bukan chunk: urutan tanggal tidak unik sehingga paging
            // biasa bisa melewati baris.
            VisitReport::query($filters)->reorder()->chunkById(500, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->visited_at->format('Y-m-d H:i'),
                        $row->store_name,
                        $row->sales_name,
                        $row->product_name,
                        $row->product_unit,
                        $row->qty_before,
                        $row->qty_found,
                        $row->qty_sold,
                        $row->qty_added,
                        $row->qty_left,
                    ]);
                }
            }, 'visit_items.id', 'id');

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

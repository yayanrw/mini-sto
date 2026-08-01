<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportCsvController;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\MapView;
use App\Livewire\Admin\Performance;
use App\Livewire\Admin\Products;
use App\Livewire\Admin\Reports;
use App\Livewire\Admin\StoreShow;
use App\Livewire\Admin\Stores as AdminStores;
use App\Livewire\Admin\Users;
use App\Livewire\Sales\Profile;
use App\Livewire\Sales\StoreCreate;
use App\Livewire\Sales\StoreList;
use App\Livewire\Sales\VisitForm;
use App\Livewire\Sales\VisitHistory;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'show'])->name('login');
    Route::post('login', [AuthController::class, 'store'])->middleware('throttle:6,1');
});

Route::post('logout', [AuthController::class, 'destroy'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/', StoreList::class)->name('stores.index');
    Route::get('toko/baru', StoreCreate::class)->name('stores.create');
    Route::get('toko/{store}/ubah', StoreCreate::class)->name('stores.edit');
    Route::get('toko/{store}/kunjungan', VisitForm::class)->name('visits.create');
    Route::get('kunjungan', VisitHistory::class)->name('visits.index');
    Route::get('profil', Profile::class)->name('profile');
});

Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('peta', MapView::class)->name('map');
    Route::get('performa', Performance::class)->name('performance');
    Route::get('report', Reports::class)->name('reports');
    Route::get('report/csv', ReportCsvController::class)->name('reports.csv');
    Route::get('toko', AdminStores::class)->name('stores');
    Route::get('toko/{store}', StoreShow::class)->name('stores.show');
    Route::get('produk', Products::class)->name('products');
    Route::get('pengguna', Users::class)->name('users');
});

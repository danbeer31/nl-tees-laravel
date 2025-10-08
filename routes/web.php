<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\SupplierCatalogController;

Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/product/{product:slug}', [ShopController::class, 'show'])->name('product.show');
// --- new: AJAX color data endpoint ---
Route::post('/product/color-data', [ShopController::class, 'colorData'])->name('product.colorData');


// Admin (we’ll protect with auth later)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/catalog/sanmar',        [SupplierCatalogController::class, 'sanmarIndex'])->name('sanmar.index');
    Route::post('/catalog/sanmar/search',[SupplierCatalogController::class, 'sanmarSearch'])->name('sanmar.search');
    Route::post('/catalog/sanmar/import',[SupplierCatalogController::class, 'sanmarImport'])->name('sanmar.import');

    Route::get('/catalog/ss',            [SupplierCatalogController::class, 'ssIndex'])->name('ss.index');
    Route::post('/catalog/ss/import',    [SupplierCatalogController::class, 'ssImport'])->name('ss.import');
});

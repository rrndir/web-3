<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\LokasiController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\Backend\OrderController as BackendOrderController;

Route::get('/', function () {
    // return view('welcome');
    return redirect()->route('beranda');
});
Route::get('backend/beranda', [BerandaController::class, 'berandaBackend'])->name('backend.beranda')->middleware('auth');

Route::get('backend/login', [LoginController::class, 'loginBackend'])->name('backend.login');
Route::post('backend/login', [LoginController::class, 'authenticateBackend'])->name('backend.login');
Route::post('backend/logout', [LoginController::class, 'logoutBackend'])->name('backend.logout');
Route::middleware(['auth', 'is.admin'])->prefix('backend')->name('backend.')->group(function () {
    Route::get('orders', [BackendOrderController::class, 'index'])->name('order.index');
    Route::get('orders/{id}', [BackendOrderController::class, 'show'])->name('order.show');
    Route::post('orders/{id}/shipping-options', [BackendOrderController::class, 'shippingOptions'])->name('order.shipping-options');
    Route::put('orders/{id}/shipping', [BackendOrderController::class, 'updateShipping'])->name('order.shipping');
    Route::put('orders/{id}/payment', [BackendOrderController::class, 'reviewPayment'])->name('order.payment');
    Route::put('orders/{id}/ship', [BackendOrderController::class, 'ship'])->name('order.ship');
    Route::put('orders/{id}/digital-delivery', [BackendOrderController::class, 'deliverDigital'])->name('order.digital-delivery');
    Route::put('orders/{id}/complete', [BackendOrderController::class, 'complete'])->name('order.complete');
    Route::get('orders/{id}/payment-proof', [BackendOrderController::class, 'downloadPaymentProof'])->name('order.payment-proof');
});

// Route untuk User
// Route::resource('backend/user', UserController::class)->middleware('auth');
Route::resource('backend/user', UserController::class, ['as' => 'backend'])->middleware('auth');
// Route untuk laporan user
Route::get('backend/laporan/formuser', [UserController::class, 'formUser'])->name('backend.laporan.formuser')->middleware('auth');
Route::post('backend/laporan/cetakuser', [UserController::class, 'cetakUser'])->name('backend.laporan.cetakuser')->middleware('auth');

// Route untuk Customer
Route::get('/customer/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login');
Route::post('/customer/login', [CustomerAuthController::class, 'login'])->name('customer.login.submit');
Route::get('/customer/register', [CustomerAuthController::class, 'showRegistration'])->name('customer.register');
Route::post('/customer/register', [CustomerAuthController::class, 'register'])->name('customer.register.submit');
Route::resource('backend/customer', CustomerController::class, ['as' => 'backend'])->middleware('auth');
// Route untuk menampilkan halaman akun customer 
Route::get('/customer/akun/{id}', [CustomerController::class, 'akun'])->name('customer.akun')->middleware('is.customer'); 
Route::put('/customer/akun/{id}/update', [CustomerController::class, 'updateAkun'])->name('customer.akun.update')->middleware('is.customer');


// Route untuk Kategori
Route::resource('backend/kategori', KategoriController::class, ['as' => 'backend'])->middleware('auth');

// Route untuk Produk
Route::get('/produk/detail/{id}', [ProdukController::class, 'detail'])->name('produk.detail');
Route::get('/produk/kategori/{id}', [ProdukController::class, 'produkKategori'])->name('produk.kategori');
Route::get('/produk/all', [ProdukController::class, 'produkAll'])->name('produk.all');  
Route::resource('backend/produk', ProdukController::class, ['as' => 'backend'])->middleware('auth'); 

// Route untuk lokasi kami
Route::get('/lokasi', [LokasiController::class, 'index'])->name('lokasi');

// Group route untuk customer
Route::middleware('is.customer')->group(function () {
    // Route untuk menambahkan produk ke keranjang
    Route::post('add-to-cart/{id}', [OrderController::class, 'addToCart'])->name('order.addToCart');

    // Route untuk keranjang belanja
    Route::get('cart', [OrderController::class, 'viewCart'])->name('order.cart');
    Route::get('checkout', [OrderController::class, 'checkout'])->name('order.checkout');
    Route::post('checkout', [OrderController::class, 'placeOrder'])->name('order.place');
    Route::get('checkout/shipping-destinations', [OrderController::class, 'shippingDestinations'])->name('order.shipping-destinations');
    Route::post('checkout/shipping-options', [OrderController::class, 'checkoutShippingOptions'])->name('order.checkout-shipping-options');
    Route::get('orders', [OrderController::class, 'orders'])->name('order.history');
    Route::get('orders/{id}', [OrderController::class, 'showOrder'])->name('order.show');
    Route::post('orders/{id}/payment-proof', [OrderController::class, 'uploadPaymentProof'])->name('order.payment-proof.upload');
    Route::put('cart/update/{id}', [OrderController::class, 'updateCart'])->name('order.updateCart');
    Route::delete('cart/remove/{id}', [OrderController::class, 'removeFromCart'])->name('order.removeFromCart');
});


// Route untuk menambahkan foto
Route::post('foto-produk/store', [ProdukController::class, 'storeFoto'])->name('backend.foto_produk.store')->middleware('auth');
// Route untuk menghapus foto
Route::delete('foto-produk/{id}', [ProdukController::class, 'destroyFoto'])->name('backend.foto_produk.destroy')->middleware('auth');
// Route untuk laporan produk
Route::get('backend/laporan/formproduk', [ProdukController::class, 'formProduk'])->name('backend.laporan.formproduk')->middleware('auth');
Route::post('backend/laporan/cetakproduk', [ProdukController::class, 'cetakProduk'])->name('backend.laporan.cetakproduk')->middleware('auth');

// Frontend 
Route::get('/beranda', [BerandaController::class, 'index'])->name('beranda');

//API Google 
Route::get('/auth/redirect', [CustomerController::class, 'redirect'])->name('auth.redirect'); 
Route::get('/auth/google/callback', [CustomerController::class, 'callback'])->name('auth.callback'); 
// Logout 
Route::post('/logout', [CustomerController::class, 'logout'])->name('logout');
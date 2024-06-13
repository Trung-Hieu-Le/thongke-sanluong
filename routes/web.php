<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SanLuongKhuVucController;
use App\Http\Controllers\SanLuongTramController;
use App\Http\Controllers\SanLuongTinhController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SanLuongTramFilterController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/login', [UserController::class, 'indexKhuVuc']);
Route::post('/action-login', [UserController::class, 'indexKhuVuc']);

Route::get('/', [SanLuongKhuVucController::class, 'indexKhuVuc']);
Route::get('/thongke', [SanLuongKhuVucController::class, 'indexKhuVuc']);
Route::get('/thongke/all', [SanLuongKhuVucController::class, 'thongKeKhuVuc']);
Route::get('/thongke/tongquat', [SanLuongKhuVucController::class, 'thongKeKhuVucTongQuat']);

Route::get('/thongke/khuvuc', [SanLuongTinhController::class, 'indexTinh']);
Route::get('/thongke/khuvuc/all', [SanLuongTinhController::class, 'thongKeTinh']);
Route::get('/thongke/khuvuc/tongquat', [SanLuongTinhController::class, 'thongKeTinhTongQuat']);

Route::get('/thongke/tinh/{ma_tinh}', [SanLuongTramController::class, 'indexTram']);
Route::get('/thongke/tinh/{ma_tinh}/all', [SanLuongTramController::class, 'thongKeTram']);
Route::get('/thongke/tinh/{ma_tinh}/tongquat', [SanLuongTramController::class, 'thongKeTramTongQuat']);

Route::get('/thongke/filter', [SanLuongTramFilterController::class, 'indexTramFilter'])->name('thongke_tram_filter');
Route::post('/thongke/filter', [SanLuongTramFilterController::class, 'indexTramFilter']);
Route::get('/thongke/filter/get-day', [SanLuongTramFilterController::class, 'getDayTramFilter']);

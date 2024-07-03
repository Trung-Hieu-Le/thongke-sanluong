<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SanLuongKhuVucController;
use App\Http\Controllers\SanLuongTramController;
use App\Http\Controllers\SanLuongTinhController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SanLuongTramFilterController;
use App\Http\Controllers\KpiQuyController;


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
Route::get('/login', [UserController::class, 'viewLogin']);
Route::post('/action-login', [UserController::class, 'actionLogin']);
Route::get('/logout', [UserController::class, 'actionLogout']);

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

Route::get('/thongke/filter', [SanLuongTramFilterController::class, 'indexTramFilter'])->name('tram.filter');
Route::get('/viewsanluong/{ma_tram}', [SanLuongTramController::class, 'viewSanLuongTram']);
Route::get('/viewhinhanh/{ma_tram}', [SanLuongTramController::class, 'viewHinhAnhTram']);
Route::get('/gethinhanh/{ma_tram}', [SanLuongTramController::class, 'getHinhAnhTram']);

// Route::get('/thongke/filter/get-day', [SanLuongTramFilterController::class, 'getDayTramFilter'])->name('tram.filter.days');

Route::get('/sanluong-khac/index', [SanLuongTramController::class, 'indexSanLuong'])->name('sanluongkhac.index');
Route::get('/sanluong-khac/add', [SanLuongTramController::class, 'addSanLuong'])->name('sanluongkhac.add');
Route::post('/sanluong-khac/handleAdd', [SanLuongTramController::class, 'handleAddSanLuong'])->name('sanluongkhac.handleAdd');
Route::get('/sanluong-khac/edit/{id}', [SanLuongTramController::class, 'editSanLuong'])->name('sanluongkhac.edit');
Route::post('/sanluong-khac/handleEdit', [SanLuongTramController::class, 'handleEditSanLuong'])->name('sanluongkhac.handleEdit');

Route::get('/kpi-quy/index', [KpiQuyController::class, 'indexKpiQuy'])->name('kpiquy.index');
Route::get('/kpi-quy/add', [KpiQuyController::class, 'addKpiQuy'])->name('kpiquy.add');
Route::post('/kpi-quy/handleAdd', [KpiQuyController::class, 'handleAddKpiQuy'])->name('kpiquy.handleAdd');
Route::get('/kpi-quy/edit', [KpiQuyController::class, 'editKpiQuy'])->name('kpiquy.edit');
Route::post('/kpi-quy/handleEdit', [KpiQuyController::class, 'handleEditKpiQuy'])->name('kpiquy.handleEdit');

//TODO: getSanLuongThang_6 (1h update / lần)
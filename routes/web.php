<?php

use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\SanLuongKhuVucController;
// use App\Http\Controllers\SanLuongTramController;
// use App\Http\Controllers\SanLuongTinhController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TinhSanLuongFilterController;
use App\Http\Controllers\KpiQuyController;
use App\Http\Controllers\ThongKeController;
use App\Http\Controllers\SanLuongKhacController;
use App\Http\Controllers\TinhSanLuongController;
use App\Http\Controllers\TableUpdateController;


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

Route::get('/', [ThongKeController::class, 'indexKhuVuc']);
Route::get('/thongke', [ThongKeController::class, 'indexKhuVuc']);
Route::get('/thongke/all', [ThongKeController::class, 'thongKeKhuVuc']);
Route::get('/thongke/xuthe/all', [ThongKeController::class, 'thongKeXuTheKhuVuc']);
Route::get('/thongke/tong-thang-nam', [ThongKeController::class, 'thongKeTongThangVaNam']);
// Route::get('/thongke/tongquat', [ThongKeController::class, 'thongKeKhuVucTongQuat']);
Route::get('/thongke/linhvuc', [ThongKeController::class, 'indexLinhVuc']);
Route::get('/thongke/linhvuc/all', [ThongKeController::class, 'thongKeLinhVuc']);

Route::get('/table-update/tonghop-sanluong', [TableUpdateController::class, 'updateTableTongHopSanLuong']);

Route::get('/thongke/khuvuc', [ThongKeController::class, 'indexTinh']);
Route::get('/thongke/khuvuc/all', [ThongKeController::class, 'thongKeTinh']);
Route::get('/thongke/khuvuc/tongquat', [ThongKeController::class, 'thongKeTinhTongQuat']);

Route::get('/thongke/tinh/{ma_tinh}', [ThongKeController::class, 'indexTram']);
Route::get('/thongke/tinh/{ma_tinh}/all', [ThongKeController::class, 'thongKeTram']);
Route::get('/thongke/tinh/{ma_tinh}/tongquat', [ThongKeController::class, 'thongKeTramTongQuat']);

Route::get('/thongke/filter', [TinhSanLuongFilterController::class, 'indexTramFilter'])->name('tram.filter');
Route::get('/viewsanluong/{ma_tram}', [TinhSanLuongController::class, 'viewSanLuongTram']);
Route::get('/viewhinhanh/{ma_tram}', [TinhSanLuongController::class, 'viewHinhAnhTram']);
Route::get('/gethinhanh/{ma_tram}', [TinhSanLuongController::class, 'getHinhAnhTram']);

Route::get('chi-tiet-chart', [ThongKeController::class, 'indexChiTietChart']);

// Route::get('/thongke/filter/get-day', [SanLuongTramFilterController::class, 'getDayTramFilter'])->name('tram.filter.days');

Route::get('/sanluong-khac/index', [SanLuongKhacController::class, 'indexSanLuong'])->name('sanluongkhac.index');
Route::get('/sanluong-khac/add', [SanLuongKhacController::class, 'addSanLuong'])->name('sanluongkhac.add');
Route::post('/sanluong-khac/handleAdd', [SanLuongKhacController::class, 'handleAddSanLuong'])->name('sanluongkhac.handleAdd');
Route::get('/sanluong-khac/noidung/{khuVuc}', [SanLuongKhacController::class, 'getNoiDungByKhuVuc']);
Route::get('/sanluong-khac/edit/{id}', [SanLuongKhacController::class, 'editSanLuong'])->name('sanluongkhac.edit');
Route::post('/sanluong-khac/handleEdit', [SanLuongKhacController::class, 'handleEditSanLuong'])->name('sanluongkhac.handleEdit');
Route::get('/sanluong-khac/delete/{id}', [SanLuongKhacController::class, 'deleteSanLuong'])->name('sanluongkhac.delete');

Route::get('/kpi-quy/index', [KpiQuyController::class, 'indexKpiQuy'])->name('kpiquy.index');
Route::get('/kpi-quy/add', [KpiQuyController::class, 'addKpiQuy'])->name('kpiquy.add');
Route::post('/kpi-quy/handleAdd', [KpiQuyController::class, 'handleAddKpiQuy'])->name('kpiquy.handleAdd');
Route::get('/kpi-quy/edit/{id}', [KpiQuyController::class, 'editKpiQuy'])->name('kpiquy.edit');
Route::post('/kpi-quy/handleEdit', [KpiQuyController::class, 'handleEditKpiQuy'])->name('kpiquy.handleEdit');
Route::get('/kpi-quy/delete/{id}', [KpiQuyController::class, 'deleteKpiQuy'])->name('kpiquy.delete');

//TODO: getSanLuongThang_6 (1h update / lần)

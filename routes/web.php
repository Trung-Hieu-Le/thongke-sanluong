<?php

use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\SanLuongKhuVucController;
// use App\Http\Controllers\SanLuongTramController;
// use App\Http\Controllers\SanLuongTinhController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TinhSanLuongFilterController;
use App\Http\Controllers\KpiQuyController;
// use App\Http\Controllers\ThongKeController;
use App\Http\Controllers\SanLuongKhacController;
use App\Http\Controllers\TinhSanLuongController;
use App\Http\Controllers\TableUpdateController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ThongKeTongQuatController;
use App\Http\Controllers\ThongKeKhuVucController;
use App\Http\Controllers\ThongKeLinhVucController;


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

Route::get('/', [ThongKeTongQuatController::class, 'indexKhuVuc']);
Route::get('/thongke', [ThongKeTongQuatController::class, 'indexKhuVuc']);
Route::get('/thongke/all', [ThongKeTongQuatController::class, 'thongKeKhuVuc']);
Route::get('/thongke/xuthe/all', [ThongKeTongQuatController::class, 'thongKeXuTheKhuVuc']);
Route::get('/thongke/tong-thang-nam', [ThongKeTongQuatController::class, 'thongKeTongThangVaNam']);
// Route::get('/thongke/tongquat', [ThongKeController::class, 'thongKeKhuVucTongQuat']);
Route::get('/thongke/linhvuc', [ThongKeLinhVucController::class, 'indexLinhVuc']);
Route::get('/thongke/linhvuc/all', [ThongKeLinhVucController::class, 'thongKeLinhVuc']);

Route::get('/table-update/tonghop-sanluong', [TableUpdateController::class, 'updateTableTongHopSanLuong']);
Route::get('/table-update/tonghop-sanluong-daily', [TableUpdateController::class, 'updateDailyTableTongHopSanLuong']);

Route::get('/thongke/khuvuc', [ThongKeKhuVucController::class, 'indexTinh']);
Route::get('/thongke/khuvuc/all', [ThongKeKhuVucController::class, 'thongKeTinh']);
Route::get('/thongke/khuvuc/tongquat', [ThongKeKhuVucController::class, 'thongKeTinhTongQuat']);

// Route::get('/thongke/tinh/{ma_tinh}', [ThongKeController::class, 'indexTram']);
// Route::get('/thongke/tinh/{ma_tinh}/all', [ThongKeController::class, 'thongKeTram']);
// Route::get('/thongke/tinh/{ma_tinh}/tongquat', [ThongKeController::class, 'thongKeTramTongQuat']);

Route::get('/thongke/filter', [TinhSanLuongFilterController::class, 'indexTramFilter'])->name('tram.filter');
Route::get('/viewsanluong/{ma_tram}', [TinhSanLuongController::class, 'viewSanLuongTram']);
Route::get('/viewhinhanh/{ma_tram}', [TinhSanLuongController::class, 'viewHinhAnhTram']);
Route::get('/gethinhanh/{ma_tram}', [TinhSanLuongController::class, 'getHinhAnhTram']);

Route::get('chi-tiet-chart', [ThongKeLinhVucController::class, 'indexChiTietChart']);

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

Route::get('/chat/search', [ChatController::class, 'search'])->name('chat.search');
Route::get('/chat/loadChat/{userId}', [ChatController::class, 'loadChat'])->name('chat.load');
Route::post('/chat/sendMessage', [ChatController::class, 'sendMessage'])->name('chat.send.message');
Route::get('/chat/deleteMessage/{id}', [ChatController::class, 'deleteMessage'])->name('chat.message.delete');
Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::post('/mark-messages-as-seen/{userId}', [ChatController::class, 'markMessagesAsSeen']);
Route::get('/get-unread-message-count/{userId}', [ChatController::class, 'getUnreadMessageCount']);
Route::get('/chat/checkUnreadMessages', [ChatController::class, 'checkUnreadMessages']);
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TinhSanLuongFilterController extends Controller
{
    public function indexTramFilter(Request $request)
{
    if (!$request->session()->has('username')) {
        return redirect('login');
    }

    // Lấy thông tin ngày
    $daysString = $request->input('days', date('d-m-Y'));
    $days = [];
    if (!empty($daysString)) {
        $days = explode(',', $daysString);
        $days = array_map(function ($day) {
            return str_replace('-', '', $day);
        }, $days);
    }

    $searchMaTram = $request->input('searchMaTram', '');
    $searchHopDong = $request->input('searchHopDong', '');
    $searchKhuVuc = $request->input('searchKhuVuc', '');

    // Biến điều kiện
    $dayCondition = count($days) > 0 ? "AND SanLuong_Ngay IN (" . implode(',', $days) . ")" : "";
    $searchCondition = !empty($searchMaTram) ? "AND SanLuong_Tram LIKE '%$searchMaTram%'" : "";
    $searchCondition2 = !empty($searchMaTram) ? "AND ThaoLap_MaTram LIKE '%$searchMaTram%'" : "";
    $searchConditionHopDong = !empty($searchHopDong) ? "AND tbl_hopdong.HopDong_SoHopDong LIKE '%$searchHopDong%'" : "";
    $searchConditionKhuVuc = !empty($searchKhuVuc) ? "AND tbl_tinh.ten_khu_vuc LIKE '%$searchKhuVuc%'" : "";
    $thaoLapDayCondition = count($days) > 0 ? "AND DATE_FORMAT(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), '%d%m%Y') IN (" . implode(',', $days) . ")" : "";

    $perPage = 100; // Number of items per page

    // Truy vấn dữ liệu chi tiết từ ba bảng
    $query = DB::table(DB::raw("
        (
            SELECT 
                LEFT(SanLuong_Tram, 3) as ma_tinh,
                SanLuong_Tram,
                tbl_hopdong.HopDong_SoHopDong,
                SUM(SanLuong_Gia) as SanLuong_Gia,
                tbl_tinh.ten_khu_vuc
            FROM tbl_sanluong
            LEFT JOIN tbl_tinh ON LEFT(tbl_sanluong.SanLuong_Tram, 3) = tbl_tinh.ma_tinh
            LEFT JOIN tbl_hopdong ON tbl_sanluong.HopDong_Id = tbl_hopdong.HopDong_Id
            WHERE tbl_sanluong.ten_hinh_anh_da_xong IS NOT NULL
            AND tbl_sanluong.ten_hinh_anh_da_xong <> ''
            $dayCondition
            $searchCondition
            $searchConditionHopDong
            $searchConditionKhuVuc
            GROUP BY SanLuong_Tram, tbl_tinh.ten_khu_vuc, tbl_hopdong.HopDong_SoHopDong
            
            UNION ALL
            
            SELECT 
                LEFT(ThaoLap_MaTram, 3) as ma_tinh,
                ThaoLap_MaTram as SanLuong_Tram,
                tbl_hopdong.HopDong_SoHopDong,
                SUM(ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon) as SanLuong_Gia,
                tbl_tinh.ten_khu_vuc
            FROM tbl_sanluong_thaolap
            LEFT JOIN tbl_tinh ON LEFT(tbl_sanluong_thaolap.ThaoLap_MaTram, 3) = tbl_tinh.ma_tinh
            LEFT JOIN tbl_hopdong ON tbl_sanluong_thaolap.HopDong_Id = tbl_hopdong.HopDong_Id
            WHERE 1
            $thaoLapDayCondition
            $searchCondition2
            $searchConditionHopDong
            $searchConditionKhuVuc
            GROUP BY ThaoLap_MaTram, tbl_tinh.ten_khu_vuc, tbl_hopdong.HopDong_SoHopDong
        ) as combined
    "))
    ->select('ma_tinh', 'SanLuong_Tram','HopDong_SoHopDong', DB::raw('SUM(SanLuong_Gia) as SanLuong_Gia'), 'ten_khu_vuc')
    ->groupBy('ma_tinh', 'SanLuong_Tram', 'ten_khu_vuc','HopDong_SoHopDong')
    ->orderBy('SanLuong_Tram', 'asc');

    // Use paginator
    $pagedData = $query->simplePaginate($perPage);

    // Truy vấn dữ liệu khu vực
    $khuVucQuery = DB::table(DB::raw("
        (
            SELECT 
                tbl_tinh.ten_khu_vuc,
                COUNT(DISTINCT SanLuong_Tram) as so_tram,
                SUM(SanLuong_Gia) as tong_san_luong
            FROM tbl_sanluong
            LEFT JOIN tbl_tinh ON LEFT(tbl_sanluong.SanLuong_Tram, 3) = tbl_tinh.ma_tinh
            LEFT JOIN tbl_hopdong ON tbl_sanluong.HopDong_Id = tbl_hopdong.HopDong_Id
            WHERE tbl_sanluong.ten_hinh_anh_da_xong IS NOT NULL
            AND tbl_sanluong.ten_hinh_anh_da_xong <> ''
            $dayCondition
            $searchCondition
            $searchConditionHopDong
            $searchConditionKhuVuc
            GROUP BY tbl_tinh.ten_khu_vuc
            
            UNION ALL
            
            SELECT 
                tbl_tinh.ten_khu_vuc,
                COUNT(DISTINCT ThaoLap_MaTram) as so_tram,
                SUM(ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon) as tong_san_luong
            FROM tbl_sanluong_thaolap
            LEFT JOIN tbl_tinh ON LEFT(tbl_sanluong_thaolap.ThaoLap_MaTram, 3) = tbl_tinh.ma_tinh
            LEFT JOIN tbl_hopdong_congviec ON tbl_sanluong_thaolap.HopDong_Id = tbl_hopdong_congviec.HopDong_Id
            LEFT JOIN tbl_hopdong ON tbl_sanluong_thaolap.HopDong_Id = tbl_hopdong.HopDong_Id
            WHERE 1
            $thaoLapDayCondition
            $searchCondition2
            $searchConditionHopDong
            $searchConditionKhuVuc
            GROUP BY tbl_tinh.ten_khu_vuc
        ) as combined
    "))
    ->select('ten_khu_vuc', DB::raw('SUM(so_tram) as so_tram'), DB::raw('SUM(tong_san_luong) as tong_san_luong'))
    ->groupBy('ten_khu_vuc');

    $hopdongs = DB::table('tbl_hopdong')
        ->select('HopDong_Id', 'HopDong_SoHopDong', 'HopDong_TenHopDong')
        ->get()->keyBy('HopDong_Id')->toArray();

    $khuVucData = $khuVucQuery->get();

    return view('thong_ke.thongke_tram_filter', compact('pagedData', 'khuVucData', 'days', 'searchMaTram','searchHopDong','searchKhuVuc','hopdongs'));
}



}

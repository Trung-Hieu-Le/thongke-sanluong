<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SanLuongKhuVucController extends Controller
{
    //TODO: cac trang thong ke chi cho phep user permission 2, 3 xem
    public function indexKhuVuc(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        return view('thongke_tongquat');
    }
    public function thongKeKhuVuc(Request $request)
{
    $timeFormat = $request->input('time_format');
    $currentYear = $request->input('nam', date('Y'));
    $currentMonth = $request->input('thang', date('n'));
    $currentQuarter = ceil($currentMonth / 3);

    // Lấy danh sách tất cả các khu vực
    $khuVucs = DB::table('tbl_tinh')
        ->distinct()
        ->orderBy('ten_khu_vuc')
        ->pluck('ten_khu_vuc');
    $results = [];

    // Lấy năm và quý hiện tại

    foreach ($khuVucs as $khuVuc) {
        // Lấy danh sách các tỉnh thuộc khu vực
        $maTinhs = DB::table('tbl_tinh')
            ->where('ten_khu_vuc', $khuVuc)
            ->pluck('ma_tinh');

        // Lấy KPI quý hiện tại từ bảng tbl_kpi_quy
        $kpi_quy = DB::table('tbl_kpi_quy')
            ->where('ten_khu_vuc', $khuVuc)
            ->where('year', $currentYear)
            ->pluck('kpi_quy', 'quarter')
            ->toArray();

        // Tính KPI năm
        $kpi_nam = array_sum($kpi_quy);

        // Tính KPI tháng từ các quý
        $kpi_thang = [];
        foreach ($kpi_quy as $quy => $gia_tri) {
            for ($i = 1; $i <= 3; $i++) {
                $kpi_thang[($quy - 1) * 3 + $i] = $gia_tri / 3;
            }
        }

        // Tạo điều kiện thời gian cho truy vấn
        switch ($timeFormat) {
            case 'ngay':
                $whereClause = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') = CURRENT_DATE()";
                $kpi = null; // KPI ngày không xác định trong ví dụ
                break;
            case 'tuan':
                $whereClause = "WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = WEEK(CURRENT_DATE())";
                $kpi = null; // KPI tuần không xác định trong ví dụ
                break;
            case 'thang':
                $whereClause = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentMonth";
                $kpi = isset($kpi_thang[$currentMonth]) ? $kpi_thang[$currentMonth] : null;
                break;
            case 'quy':
                $whereClause = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentQuarter";
                $kpi = isset($kpi_quy[$currentQuarter]) ? $kpi_quy[$currentQuarter] : null;
                break;
            case 'nam':
                $whereClause = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear";
                $kpi = $kpi_nam;
                break;
            default:
                // Thời gian không hợp lệ
                return response()->json(['error' => 'Thời gian không hợp lệ']);
        }

        // Tính tổng sản lượng cho khu vực
        $total = DB::table('tbl_sanluong')
            ->where(function($query) use ($maTinhs) {
                foreach ($maTinhs as $maTinh) {
                    $query->orWhere('SanLuong_Tram', 'LIKE', "$maTinh%");
                }
            })
            ->whereRaw($whereClause)
            ->sum(DB::raw('CAST(SanLuong_Gia AS DECIMAL(10, 2))'));

            $results[] = [
                'ten_khu_vuc' => $khuVuc,
                'total' => round($total / 1e9, 2),
                'kpi' => $kpi !== null ? round($kpi, 2) : 0
            ];
    }

    return response()->json($results);
}


}

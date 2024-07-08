<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SanLuongTinhController extends Controller
{
    public function indexTinh(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        $khuVucList = DB::table('tbl_tinh')
            ->distinct()
            ->select('ten_khu_vuc')
            ->orderBy('ten_khu_vuc')
            ->get()->toArray();
        return view('thong_ke.thongke_tinh', compact('khuVucList'));
    }

    public function thongKeTinh(Request $request)
{
    $khuVuc = $request->input('khu_vuc');
    $ngayChon = $request->input('ngay');

    if (is_null($ngayChon) || $ngayChon === '') {
        $ngayChon = date('Y-m-d');
    }

    $maTinhs = DB::table('tbl_tinh')
        ->where('ten_khu_vuc', $khuVuc)
        ->pluck('ma_tinh');

    $results = [];
    foreach ($maTinhs as $maTinh) {
        $query = "
            SELECT
                SUM(CASE WHEN DATE(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = DATE('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as ngay,
                SUM(CASE WHEN WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = WEEK('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as tuan,
                SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = MONTH('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as thang,
                SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = QUARTER('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as quy,
                SUM(CASE WHEN YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = YEAR('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as nam
            FROM tbl_sanluong
            WHERE SanLuong_Tram LIKE '$maTinh%'
            AND ten_hinh_anh_da_xong NOT LIKE ''
            UNION ALL
            SELECT
                SUM(CASE WHEN DATE(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = DATE('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as ngay,
                SUM(CASE WHEN WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = WEEK('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as tuan,
                SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = MONTH('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as thang,
                SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = QUARTER('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as quy,
                SUM(CASE WHEN YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = YEAR('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as nam
            FROM tbl_sanluong_khac
            WHERE SanLuong_Tram LIKE '$maTinh%'
            UNION ALL
            SELECT
                SUM(CASE WHEN DATE(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = DATE('$ngayChon') THEN ThaoLap_SanLuong ELSE 0 END) as ngay,
                SUM(CASE WHEN WEEK(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = WEEK('$ngayChon') THEN ThaoLap_SanLuong ELSE 0 END) as tuan,
                SUM(CASE WHEN MONTH(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = MONTH('$ngayChon') THEN ThaoLap_SanLuong ELSE 0 END) as thang,
                SUM(CASE WHEN QUARTER(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = QUARTER('$ngayChon') THEN ThaoLap_SanLuong ELSE 0 END) as quy,
                SUM(CASE WHEN YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = YEAR('$ngayChon') THEN ThaoLap_SanLuong ELSE 0 END) as nam
            FROM tbl_sanluong_thaolap
            WHERE ThaoLap_MaTram LIKE '$maTinh%'
        ";

        $totals = DB::select($query);

        $combinedTotals = (object) [
            'ngay' => 0,
            'tuan' => 0,
            'thang' => 0,
            'quy' => 0,
            'nam' => 0
        ];

        foreach ($totals as $total) {
            $combinedTotals->ngay += $total->ngay;
            $combinedTotals->tuan += $total->tuan;
            $combinedTotals->thang += $total->thang;
            $combinedTotals->quy += $total->quy;
            $combinedTotals->nam += $total->nam;
        }

        $results[] = [
            'ma_tinh' => $maTinh,
            'totals' => [
                'ngay' => round($combinedTotals->ngay, 4),
                'tuan' => round($combinedTotals->tuan, 4),
                'thang' => round($combinedTotals->thang, 4),
                'quy' => round($combinedTotals->quy, 4),
                'nam' => round($combinedTotals->nam, 4)
            ]
        ];
    }

    return response()->json($results);
}

    //TODO: bảng thống kê theo năm chọn
    public function thongKeTinhTongQuat(Request $request)
{
    $khuVuc = $request->input('khu_vuc');
    $currentYear = date('Y');

    $maTinhs = DB::table('tbl_tinh')
        ->where('ten_khu_vuc', $khuVuc)
        ->pluck('ma_tinh');

    $results = [];

    foreach ($maTinhs as $maTinh) {
        // Truy vấn SQL để lấy tất cả dữ liệu cần thiết trong một lần từ 3 bảng
        $query = "
            SELECT
                SUM(SanLuong_Gia) as total_nam,
                SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 1 THEN SanLuong_Gia ELSE 0 END) as total_quy_1,
                SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 2 THEN SanLuong_Gia ELSE 0 END) as total_quy_2,
                SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 3 THEN SanLuong_Gia ELSE 0 END) as total_quy_3,
                SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 4 THEN SanLuong_Gia ELSE 0 END) as total_quy_4,
                SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 1 THEN SanLuong_Gia ELSE 0 END) as total_thang_1,
                SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 2 THEN SanLuong_Gia ELSE 0 END) as total_thang_2,
                SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 3 THEN SanLuong_Gia ELSE 0 END) as total_thang_3,
                SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 4 THEN SanLuong_Gia ELSE 0 END) as total_thang_4,
                SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 5 THEN SanLuong_Gia ELSE 0 END) as total_thang_5,
                SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 6 THEN SanLuong_Gia ELSE 0 END) as total_thang_6,
                SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 7 THEN SanLuong_Gia ELSE 0 END) as total_thang_7,
                SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 8 THEN SanLuong_Gia ELSE 0 END) as total_thang_8,
                SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 9 THEN SanLuong_Gia ELSE 0 END) as total_thang_9,
                SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 10 THEN SanLuong_Gia ELSE 0 END) as total_thang_10,
                SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 11 THEN SanLuong_Gia ELSE 0 END) as total_thang_11,
                SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 12 THEN SanLuong_Gia ELSE 0 END) as total_thang_12
            FROM (
                SELECT
                    SanLuong_Gia,
                    SanLuong_Ngay
                FROM tbl_sanluong
                WHERE SanLuong_Tram LIKE ? AND YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = ?
                AND ten_hinh_anh_da_xong NOT LIKE ''
                UNION ALL
                SELECT
                    SanLuong_Gia,
                    SanLuong_Ngay
                FROM tbl_sanluong_khac
                WHERE SanLuong_Tram LIKE ? AND YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = ?
                UNION ALL
                SELECT
                    ThaoLap_SanLuong as SanLuong_Gia,
                    ThaoLap_Ngay as SanLuong_Ngay
                FROM tbl_sanluong_thaolap
                WHERE ThaoLap_MaTram LIKE ? AND YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = ?
            ) as subquery
        ";

        $bindings = [
            "$maTinh%", $currentYear,
            "$maTinh%", $currentYear,
            "$maTinh%", $currentYear
        ];

        $data = DB::select($query, $bindings);

        foreach ($data as $row) {
            $results[] = [
                'ma_tinh' => $maTinh,
                'tong_san_luong' => [
                    'nam' => round($row->total_nam, 4),
                    'quy_1' => round($row->total_quy_1, 4),
                    'quy_2' => round($row->total_quy_2, 4),
                    'quy_3' => round($row->total_quy_3, 4),
                    'quy_4' => round($row->total_quy_4, 4),
                    'thang_1' => round($row->total_thang_1, 4),
                    'thang_2' => round($row->total_thang_2, 4),
                    'thang_3' => round($row->total_thang_3, 4),
                    'thang_4' => round($row->total_thang_4, 4),
                    'thang_5' => round($row->total_thang_5, 4),
                    'thang_6' => round($row->total_thang_6, 4),
                    'thang_7' => round($row->total_thang_7, 4),
                    'thang_8' => round($row->total_thang_8, 4),
                    'thang_9' => round($row->total_thang_9, 4),
                    'thang_10' => round($row->total_thang_10, 4),
                    'thang_11' => round($row->total_thang_11, 4),
                    'thang_12' => round($row->total_thang_12, 4),
                ],
            ];
        }
    }

    return response()->json($results);
}

}
 
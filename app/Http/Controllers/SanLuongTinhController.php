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


    public function thongKeTinhTongQuat(Request $request)
    {
        $khuVuc = $request->input('khu_vuc');

        $maTinhs = DB::table('tbl_tinh')
            ->where('ten_khu_vuc', $khuVuc)
            ->pluck('ma_tinh');

        $results = [];
        $currentYear = date('Y');
        foreach ($maTinhs as $maTinh) {

            // Truy vấn SQL để lấy tất cả dữ liệu cần thiết trong một lần
            $data = DB::table('tbl_sanluong')
                ->select(
                    DB::raw('SUM(SanLuong_Gia) as total_nam'),
                    DB::raw('SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) = 1 THEN SanLuong_Gia ELSE 0 END) as total_quy_1'),
                    DB::raw('SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) = 2 THEN SanLuong_Gia ELSE 0 END) as total_quy_2'),
                    DB::raw('SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) = 3 THEN SanLuong_Gia ELSE 0 END) as total_quy_3'),
                    DB::raw('SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) = 4 THEN SanLuong_Gia ELSE 0 END) as total_quy_4'),
                    DB::raw('SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) = 1 THEN SanLuong_Gia ELSE 0 END) as total_thang_1'),
                    DB::raw('SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) = 2 THEN SanLuong_Gia ELSE 0 END) as total_thang_2'),
                    DB::raw('SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) = 3 THEN SanLuong_Gia ELSE 0 END) as total_thang_3'),
                    DB::raw('SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) = 4 THEN SanLuong_Gia ELSE 0 END) as total_thang_4'),
                    DB::raw('SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) = 5 THEN SanLuong_Gia ELSE 0 END) as total_thang_5'),
                    DB::raw('SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) = 6 THEN SanLuong_Gia ELSE 0 END) as total_thang_6'),
                    DB::raw('SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) = 7 THEN SanLuong_Gia ELSE 0 END) as total_thang_7'),
                    DB::raw('SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) = 8 THEN SanLuong_Gia ELSE 0 END) as total_thang_8'),
                    DB::raw('SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) = 9 THEN SanLuong_Gia ELSE 0 END) as total_thang_9'),
                    DB::raw('SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) = 10 THEN SanLuong_Gia ELSE 0 END) as total_thang_10'),
                    DB::raw('SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) = 11 THEN SanLuong_Gia ELSE 0 END) as total_thang_11'),
                    DB::raw('SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) = 12 THEN SanLuong_Gia ELSE 0 END) as total_thang_12')
                )
                ->where('SanLuong_Tram', 'LIKE', "$maTinh%")
                ->whereRaw('YEAR(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) = ?', [$currentYear])
                ->get();

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
 
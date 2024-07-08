<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ThongKeController extends Controller
{
    public function indexKhuVuc(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('/login');
        }
        return view('thong_ke.thongke_tongquat');
    }
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
    public function indexTram(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        $maTinhChose = $request->ma_tinh;
        return view('thong_ke.thongke_tram', compact('maTinhChose'));
    }
    
    public function thongKeKhuVuc(Request $request)
    {
        // Lấy tham số từ request
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
            $whereClauseSanLuong = "";
            $whereClauseThaoLap = "";
            switch ($timeFormat) {
                case 'ngay':
                    $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') = CURRENT_DATE()";
                    $whereClauseThaoLap = "STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') = CURRENT_DATE()";
                    $kpi = null; // KPI ngày không xác định trong ví dụ
                    break;
                case 'tuan':
                    $whereClauseSanLuong = "WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = WEEK(CURRENT_DATE())";
                    $whereClauseThaoLap = "WEEK(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = WEEK(CURRENT_DATE())";
                    $kpi = null; // KPI tuần không xác định trong ví dụ
                    break;
                case 'thang':
                    $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentMonth";
                    $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear AND MONTH(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentMonth";
                    $kpi = isset($kpi_thang[$currentMonth]) ? $kpi_thang[$currentMonth] : null;
                    break;
                case 'quy':
                    $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentQuarter";
                    $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear AND QUARTER(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentQuarter";
                    $kpi = isset($kpi_quy[$currentQuarter]) ? $kpi_quy[$currentQuarter] : null;
                    break;
                case 'nam':
                    $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear";
                    $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear";
                    $kpi = $kpi_nam;
                    break;
                default:
                    // Thời gian không hợp lệ
                    return response()->json(['error' => 'Thời gian không hợp lệ']);
            }

            // Tính tổng sản lượng cho khu vực từ cả 3 bảng
            $total = DB::table(DB::raw("(
                SELECT SanLuong_Tram, STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') as SanLuong_Ngay, SanLuong_Gia FROM tbl_sanluong
                WHERE $whereClauseSanLuong
                AND ten_hinh_anh_da_xong NOT LIKE ''
                UNION ALL
                SELECT SanLuong_Tram, STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') as SanLuong_Ngay, SanLuong_Gia FROM tbl_sanluong_khac
                WHERE $whereClauseSanLuong
                UNION ALL
                SELECT ThaoLap_MaTram as SanLuong_Tram, STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') as SanLuong_Ngay, ThaoLap_SanLuong as SanLuong_Gia FROM tbl_sanluong_thaolap
                WHERE $whereClauseThaoLap
            ) as combined"))
                ->where(function ($query) use ($maTinhs) {
                    foreach ($maTinhs as $maTinh) {
                        $query->orWhere('SanLuong_Tram', 'LIKE', "$maTinh%");
                    }
                })
                ->sum(DB::raw('CAST(SanLuong_Gia AS DECIMAL(10, 2))'));

            // Thêm kết quả vào mảng
            $results[] = [
                'ten_khu_vuc' => $khuVuc,
                'total' => round($total / 1e9, 2),
                'kpi' => $kpi !== null ? round($kpi, 2) : 0
            ];
        }

        return response()->json($results);
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
    public function thongKeTram(Request $request)
    {
        $maTinh = $request->ma_tinh;
        $ngayChon = $request->input('ngay');
        if (is_null($ngayChon) || $ngayChon === '') {
            $ngayChon = date('Y-m-d');
        }

        $results = DB::table('tbl_sanluong')
            ->select(
                'SanLuong_Tram',
                DB::raw("
                SUM(CASE WHEN DATE(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = DATE('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as ngay,
                SUM(CASE WHEN WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = WEEK('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as tuan,
                SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = MONTH('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as thang,
                SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = QUARTER('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as quy,
                SUM(CASE WHEN YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = YEAR('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as nam
            ")
            )
            ->where('SanLuong_Tram', 'LIKE', "$maTinh%")
            ->groupBy('SanLuong_Tram')
            ->orderBy('SanLuong_Tram')
            ->get();

        return response()->json($results);
    }

    public function thongKeTramTongQuat(Request $request)
    {
        $maTinh = $request->ma_tinh;
        $currentYear = date('Y');

        // Truy vấn SQL để lấy tất cả dữ liệu cần thiết trong một lần từ 3 bảng
        $query = "
        SELECT
            SanLuong_Tram,
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
                SanLuong_Tram,
                SanLuong_Gia,
                SanLuong_Ngay
            FROM tbl_sanluong
            WHERE SanLuong_Tram LIKE ? AND YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = ?
            AND ten_hinh_anh_da_xong NOT LIKE ''
            UNION ALL
            SELECT
                SanLuong_Tram,
                SanLuong_Gia,
                SanLuong_Ngay
            FROM tbl_sanluong_khac
            WHERE SanLuong_Tram LIKE ? AND YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = ?
            UNION ALL
            SELECT
                ThaoLap_MaTram as SanLuong_Tram,
                ThaoLap_SanLuong as SanLuong_Gia,
                ThaoLap_Ngay as SanLuong_Ngay
            FROM tbl_sanluong_thaolap
            WHERE ThaoLap_MaTram LIKE ? AND YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = ?
        ) as subquery
        GROUP BY SanLuong_Tram
        ORDER BY SanLuong_Tram
    ";

        $bindings = [
            "$maTinh%", $currentYear,
            "$maTinh%", $currentYear,
            "$maTinh%", $currentYear
        ];

        $data = DB::select($query, $bindings);

        // Xử lý kết quả
        $results = [];
        foreach ($data as $row) {
            $results[] = [
                'ma_tram' => $row->SanLuong_Tram,
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

        return response()->json($results);
    }
}

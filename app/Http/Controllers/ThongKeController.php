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
    public function indexLinhVuc(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('/login');
        }
        return view('thong_ke.thongke_linhvuc');
    }
    //TODO: sửa sản lượng tháo lắp tính toán
    public function thongKeKhuVuc(Request $request)
{
    // Lấy tham số từ request
    $timeFormat = $request->input('time_format');
    $currentYear = $request->input('nam', date('Y'));
    $currentMonth = $request->input('thang', date('n'));
    $currentQuarter = ceil($currentMonth / 3);

    //TODO: lấy từ tbl_sanluong_khac nữa
    $khuVucs = DB::table('tbl_tinh')
        ->distinct()
        ->orderBy('ten_khu_vuc')
        ->pluck('ten_khu_vuc');

    $results = [];

    foreach ($khuVucs as $khuVuc) {
        $maTinhs = DB::table('tbl_tinh')
            ->where('ten_khu_vuc', $khuVuc)
            ->pluck('ma_tinh')
            ->toArray();

        $kpi_quy = DB::table('tbl_kpi_quy')
            ->where('ten_khu_vuc', $khuVuc)
            ->where('year', $currentYear)
            ->pluck('kpi_quy', 'quarter')
            ->toArray();

        $kpi_nam = array_sum($kpi_quy);

        $kpi_thang = [];
        foreach ($kpi_quy as $quy => $gia_tri) {
            for ($i = 1; $i <= 3; $i++) {
                $kpi_thang[($quy - 1) * 3 + $i] = $gia_tri / 3;
            }
        }

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
                return response()->json(['error' => 'Thời gian không hợp lệ']);
        }

        // Lấy dữ liệu từ bảng tbl_sanluong
        $sanluongData = DB::table('tbl_sanluong')
            ->select('SanLuong_Tram', 'SanLuong_Ngay', 'SanLuong_Gia')
            ->whereRaw($whereClauseSanLuong)
            ->where('ten_hinh_anh_da_xong', '!=', '')
            ->where(function ($query) use ($maTinhs) {
                foreach ($maTinhs as $maTinh) {
                    $query->orWhere('SanLuong_Tram', 'LIKE', "$maTinh%");
                }
            })
            ->get();

        // Lấy dữ liệu từ bảng tbl_sanluong_khac
        $sanluongKhacData = DB::table('tbl_sanluong_khac')
            ->select('SanLuong_Tram', 'SanLuong_Ngay', 'SanLuong_Gia')
            ->whereRaw($whereClauseSanLuong)
            ->where('SanLuong_KhuVuc', $khuVuc)
            ->get();

        // Lấy dữ liệu từ bảng tbl_sanluong_thaolap
        $sanluongThaolapData = DB::table('tbl_sanluong_thaolap')
            ->select(
                'ThaoLap_MaTram as SanLuong_Tram',
                DB::raw("STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') as SanLuong_Ngay"),
                DB::raw("
                    ThaoLap_Anten * DonGia_Anten +
                    ThaoLap_RRU * DonGia_RRU +
                    ThaoLap_TuThietBi * DonGia_TuThietBi +
                    ThaoLap_CapNguon * DonGia_CapNguon as SanLuong_Gia
                ")
            )
            ->whereRaw($whereClauseThaoLap)
            // ->where('tbl_sanluong_thaolap.HopDong_Id', 3)
            ->where(function ($query) use ($maTinhs) {
                foreach ($maTinhs as $maTinh) {
                    $query->orWhere('ThaoLap_MaTram', 'LIKE', "$maTinh%");
                }
            })
            ->get();
            // dd($sanluongThaolapData);

        $combinedData = $sanluongData->merge($sanluongKhacData)->merge($sanluongThaolapData);
        $combinedData = $combinedData->map(function($item) {
            $item->SanLuong_Gia = floatval($item->SanLuong_Gia);
            return $item;
        });
        $total = $combinedData->sum('SanLuong_Gia');

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
                    SUM(CASE WHEN DATE(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = DATE('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as ngay,
                    SUM(CASE WHEN WEEK(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = WEEK('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as tuan,
                    SUM(CASE WHEN MONTH(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = MONTH('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as thang,
                    SUM(CASE WHEN QUARTER(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = QUARTER('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as quy,
                    SUM(CASE WHEN YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = YEAR('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as nam
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
        $currentYear = date('Y');

        $maTinhs = DB::table('tbl_tinh')
            ->where('ten_khu_vuc', $khuVuc)
            ->pluck('ma_tinh');

        $results = [];

        foreach ($maTinhs as $maTinh) {
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
                    ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon as SanLuong_Gia,
                    ThaoLap_Ngay as SanLuong_Ngay
                FROM tbl_sanluong_thaolap
                WHERE ThaoLap_MaTram LIKE ? AND YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = ?
            ) as subquery
        ";

            $bindings = [
                "$maTinh%", $currentYear,
                "$maTinh%", $currentYear,
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

    $query = "
        SELECT
            SanLuong_Tram,
            SUM(CASE WHEN DATE(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = DATE(?) THEN SanLuong_Gia ELSE 0 END) as ngay,
            SUM(CASE WHEN WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = WEEK(?) THEN SanLuong_Gia ELSE 0 END) as tuan,
            SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = MONTH(?) THEN SanLuong_Gia ELSE 0 END) as thang,
            SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = QUARTER(?) THEN SanLuong_Gia ELSE 0 END) as quy,
            SUM(CASE WHEN YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = YEAR(?) THEN SanLuong_Gia ELSE 0 END) as nam
        FROM tbl_sanluong
        WHERE SanLuong_Tram LIKE ?
        AND ten_hinh_anh_da_xong NOT LIKE ''
        GROUP BY SanLuong_Tram
        UNION ALL
        SELECT
            ThaoLap_MaTram as SanLuong_Tram,
            SUM(CASE WHEN DATE(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = DATE(?) THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as ngay,
            SUM(CASE WHEN WEEK(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = WEEK(?) THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as tuan,
            SUM(CASE WHEN MONTH(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = MONTH(?) THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as thang,
            SUM(CASE WHEN QUARTER(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = QUARTER(?) THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as quy,
            SUM(CASE WHEN YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = YEAR(?) THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as nam
        FROM tbl_sanluong_thaolap
        WHERE ThaoLap_MaTram LIKE ?
        GROUP BY SanLuong_Tram
        ORDER BY SanLuong_Tram
    ";

    $bindings = [
        $ngayChon, $ngayChon, $ngayChon, $ngayChon, $ngayChon,
        "$maTinh%", 
        $ngayChon, $ngayChon, $ngayChon, $ngayChon, $ngayChon,
        "$maTinh%"
    ];

    $results = DB::select($query, $bindings);

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
                ThaoLap_MaTram as SanLuong_Tram,
                ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon as SanLuong_Gia,
                ThaoLap_Ngay as SanLuong_Ngay
            FROM tbl_sanluong_thaolap
            WHERE ThaoLap_MaTram LIKE ? AND YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = ?
        ) as subquery
        GROUP BY SanLuong_Tram
        ORDER BY SanLuong_Tram
    ";

        $bindings = [
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
    public function indexChiTietChart(Request $request){
        if (!$request->session()->has('username')) {
            return redirect('/login');
        }
        return view('thong_ke.chitiet_bieudo');
    }

    //TODO: Số liệu? không khớp với thống kê tổng quát
    public function thongKeLinhVuc(Request $request)
{
    $timeFormat = $request->input('time_format');
    $currentYear = $request->input('nam', date('Y'));
    $currentMonth = $request->input('thang', date('n'));
    $currentQuarter = ceil($currentMonth / 3);

    $whereClauseSanLuong = "";
    $whereClauseThaoLap = "";
    switch ($timeFormat) {
        case 'ngay':
            $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') = CURRENT_DATE()";
            $whereClauseThaoLap = "STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') = CURRENT_DATE()";
            break;
        case 'tuan':
            $whereClauseSanLuong = "WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = WEEK(CURRENT_DATE())";
            $whereClauseThaoLap = "WEEK(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = WEEK(CURRENT_DATE())";
            break;
        case 'thang':
            $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentMonth";
            $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear AND MONTH(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentMonth";
            break;
        case 'quy':
            $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentQuarter";
            $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear AND QUARTER(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentQuarter";
            break;
        case 'nam':
            $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear";
            $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear";
            break;
        default:
            return response()->json(['error' => 'Thời gian không hợp lệ']);
    }

    // Lấy dữ liệu từ bảng tbl_sanluong
    $sanluongData = DB::table('tbl_sanluong')
        ->select(DB::raw("IFNULL(CAST(SanLuong_Gia AS UNSIGNED), 0) as SanLuong_Gia"))
        ->whereRaw($whereClauseSanLuong)
        ->where('ten_hinh_anh_da_xong', '<>', '')
        ->get();

    // Lấy dữ liệu từ bảng tbl_sanluong_thaolap
    $sanluongThaolapData = DB::table('tbl_sanluong_thaolap')
        ->select(
            DB::raw("
                IFNULL(
                    ThaoLap_Anten * DonGia_Anten +
                    ThaoLap_RRU * DonGia_RRU +
                    ThaoLap_TuThietBi * DonGia_TuThietBi +
                    ThaoLap_CapNguon * DonGia_CapNguon, 0
                ) as SanLuong_Gia
            ")
        )
        ->whereRaw($whereClauseThaoLap)
        // ->where('tbl_sanluong_thaolap.HopDong_Id', 3)
        ->get();

    // Tổng hợp dữ liệu EC từ tbl_sanluong và tbl_sanluong_thaolap
    $totalEC = $sanluongData->sum('SanLuong_Gia') + $sanluongThaolapData->sum('SanLuong_Gia');

    // Lấy dữ liệu từ bảng tbl_sanluong_khac
    $sanluongKhacData = DB::table('tbl_sanluong_khac')
        ->select('SanLuong_TenHangMuc', DB::raw('SUM(SanLuong_Gia) as total'))
        ->whereRaw($whereClauseSanLuong)
        ->groupBy('SanLuong_TenHangMuc')
        ->get();

    // Tạo mảng kết quả
    $results = [];

    // Thêm EC vào mảng kết quả
    $results[] = [
        'ten_linh_vuc' => 'EC',
        'total' => round($totalEC / 1e9, 2)
        
    ];

    // Thêm các lĩnh vực khác vào mảng kết quả
    foreach ($sanluongKhacData as $row) {
        $results[] = [
            'ten_linh_vuc' => $row->SanLuong_TenHangMuc,
            'total' => round($row->total / 1e9, 2)
        ];
    }

    return response()->json($results);
}

}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateInterval, DateTime, DatePeriod;
use Illuminate\Support\Carbon;

class ThongKeKhuVucController extends Controller
{
    public function indexTinh(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        $role = session('role');
        $userId = session('userid');
        $userKhuVuc = null;

        $khuVucListQuery = DB::table('tbl_tinh')
            ->distinct()
            // ->where('ten_khu_vuc', $userKhuVuc)
            ->select('ten_khu_vuc')
            ->whereIn('ten_khu_vuc', ['TTKV1', 'TTKV2', 'TTKV3', 'TTGPHTVT'])
            ->orderBy('ten_khu_vuc');
        if ($role != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', $userId)
                ->value('user_khuvuc');
            $khuVucListQuery->where('ten_khu_vuc', $userKhuVuc);
        }
        $khuVucList = $khuVucListQuery->get()->toArray();
        return view('thong_ke.thongke_tinh', compact('khuVucList'));
    }


    public function getMonthsInRange($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $months = [];

        while ($start <= $end) {
            $monthStart = $start->copy()->startOfMonth();
            $monthEnd = $start->copy()->endOfMonth();

            if ($monthStart < new DateTime($startDate)) {
                $monthStart = new DateTime($startDate);
            }

            if ($monthEnd > new DateTime($endDate)) {
                $monthEnd = new DateTime($endDate);
            }

            $months[] = [
                'start' => $monthStart->format('Y-m-d'),
                'end' => $monthEnd->format('Y-m-d'),
            ];

            $start->modify('first day of next month');
        }

        return $months;
    }
    //TODO: Lọc hợp đồng, đối tác, lĩnh vực???
    private function getTotalSanLuongWithMaTram($maTinh, $startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $startMonth = $start->month;
        $startYear = $start->year;
        $endMonth = $end->month;
        $endYear = $end->year;

        $totals = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $startMonthLoop = ($year == $startYear) ? $startMonth : 1;
            $endMonthLoop = ($year == $endYear) ? $endMonth : 12;

            for (
                $month = $startMonthLoop;
                $month <= $endMonthLoop;
                $month++
            ) {
                $formattedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);

                $query = DB::table('tbl_tonghop_sanluong')
                    ->select('SanLuong_Ngay_01', 'SanLuong_Ngay_02', 'SanLuong_Ngay_03', 'SanLuong_Ngay_04', 'SanLuong_Ngay_05', 'SanLuong_Ngay_06', 'SanLuong_Ngay_07', 'SanLuong_Ngay_08', 'SanLuong_Ngay_09', 'SanLuong_Ngay_10', 'SanLuong_Ngay_11', 'SanLuong_Ngay_12', 'SanLuong_Ngay_13', 'SanLuong_Ngay_14', 'SanLuong_Ngay_15', 'SanLuong_Ngay_16', 'SanLuong_Ngay_17', 'SanLuong_Ngay_18', 'SanLuong_Ngay_19', 'SanLuong_Ngay_20', 'SanLuong_Ngay_21', 'SanLuong_Ngay_22', 'SanLuong_Ngay_23', 'SanLuong_Ngay_24', 'SanLuong_Ngay_25', 'SanLuong_Ngay_26', 'SanLuong_Ngay_27', 'SanLuong_Ngay_28', 'SanLuong_Ngay_29', 'SanLuong_Ngay_30', 'SanLuong_Ngay_31')
                    ->where('ma_tinh', $maTinh)
                    ->where('year', $year)
                    ->where('month', $formattedMonth)
                    ->get();

                if (
                    $year == $startYear && $month == $startMonth
                ) {
                    $startDay = $start->day;
                } else {
                    $startDay = 1;
                }

                if ($year == $endYear && $month == $endMonth) {
                    $endDay = $end->day;
                } else {
                    $endDay = Carbon::createFromDate($year, $month)->daysInMonth;
                }

                foreach ($query as $data) {
                    for ($day = $startDay; $day <= $endDay; $day++) {
                        $dayColumn = "SanLuong_Ngay_" . str_pad($day, 2, '0', STR_PAD_LEFT);
                        if (isset($data->$dayColumn)) {
                            $totals[] += floatval($data->$dayColumn);
                        }
                    }
                }
            }
        }
        return $totals;
    }
    public function thongKeTinh(Request $request)
    {
        // $khuVuc = $request->input('khu_vuc');
        $ngayChon = $request->input('ngay_chon', date('Y-m-d'));
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $linhVuc = $request->input('linh_vuc');

        // if (is_null($ngayChon) || $ngayChon === '') {
        //     $ngayChon = date('Y-m-d');
        // }

        $role = session('role');
        $userId = session('userid');
        $userKhuVuc = null;
        if ($role != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', $userId)
                ->value('user_khuvuc');
        }
        $khuVuc = $role == 3 ? $request->input('khu_vuc') : $userKhuVuc;
        $maTinhs = DB::table('tbl_tinh')
            ->where('ten_khu_vuc', $khuVuc)
            ->whereIn('ten_khu_vuc', ['TTKV1', 'TTKV2', 'TTKV3', 'TTGPHTVT'])
            ->pluck('ma_tinh');
        $results = [];
        if ($role != 3 && $request->input('khu_vuc') != $userKhuVuc) {
            return response()->json($results);
        }
        if (!empty($linhVuc) && $linhVuc != "EC") {
            return response()->json($results);
        }
        foreach ($maTinhs as $maTinh) {
            if (!empty($startDate) && !empty($endDate)) {
                $totals = $this->getTotalSanLuongWithMaTram($maTinh, $startDate, $endDate);
            } else {
                $day = (int)date('d', strtotime($ngayChon));
                $month = (int)date('m', strtotime($ngayChon));
                $year = (int)date('Y', strtotime($ngayChon));

                if ($day >= 1 && $day <= 7) {
                    $startWeek = "$year-$month-01";
                    $endWeek = "$year-$month-07";
                } elseif ($day >= 8 && $day <= 14) {
                    $startWeek = "$year-$month-08";
                    $endWeek = "$year-$month-14";
                } elseif ($day >= 15 && $day <= 21) {
                    $startWeek = "$year-$month-15";
                    $endWeek = "$year-$month-21";
                } else {
                    $startWeek = "$year-$month-22";
                    $endWeek = date('Y-m-t', strtotime($ngayChon)); // Ngày cuối tháng
                }
                $query = "
                SELECT
                    SUM(CASE WHEN DATE(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = DATE('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as ngay,
                    SUM(CASE WHEN DATE(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) BETWEEN DATE('$startWeek') AND DATE('$endWeek') THEN SanLuong_Gia ELSE 0 END) as tuan,
                    SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = MONTH('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as thang,
                    SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = QUARTER('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as quy,
                    SUM(CASE WHEN YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = YEAR('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as nam
                FROM (
                    SELECT 
                        LEFT(SanLuong_Tram, 3) as ma_tinh, SanLuong_Ngay, SanLuong_Gia, HopDong_Id
                    FROM 
                        tbl_sanluong
                    WHERE ten_hinh_anh_da_xong NOT LIKE ''
                    GROUP BY 
                        SanLuong_Tram, SanLuong_Ngay, SanLuong_Gia, SanLuong_TenHangMuc, tbl_sanluong.HopDong_Id
                    ORDER BY ma_tinh, SanLuong_Ngay, SanLuong_TenHangMuc
                ) AS subquery_sanluong
                LEFT JOIN tbl_tinh ON subquery_sanluong.ma_tinh = tbl_tinh.ma_tinh
                WHERE subquery_sanluong.ma_tinh LIKE '$maTinh%'
                UNION ALL
                SELECT
                    SUM(CASE WHEN DATE(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = DATE('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as ngay,
                    SUM(CASE WHEN DATE(STR_TO_DATE(ThaoLap_Ngay, '%d%m%Y')) BETWEEN DATE('$startWeek') AND DATE('$endWeek') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as tuan,               
                    SUM(CASE WHEN MONTH(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = MONTH('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as thang,
                    SUM(CASE WHEN QUARTER(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = QUARTER('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as quy,
                    SUM(CASE WHEN YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = YEAR('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as nam
                FROM tbl_sanluong_thaolap
                WHERE ThaoLap_MaTram LIKE '$maTinh%'
                UNION ALL
                SELECT
                    SUM(CASE WHEN DATE(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = DATE('$ngayChon') THEN KiemDinh_DonGia ELSE 0 END) as ngay,
                    SUM(CASE WHEN DATE(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) BETWEEN DATE('$startWeek') AND DATE('$endWeek') THEN KiemDinh_DonGia ELSE 0 END) as tuan,
                    SUM(CASE WHEN MONTH(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = MONTH('$ngayChon') THEN KiemDinh_DonGia ELSE 0 END) as thang,
                    SUM(CASE WHEN QUARTER(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = QUARTER('$ngayChon') THEN KiemDinh_DonGia ELSE 0 END) as quy,
                    SUM(CASE WHEN YEAR(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = YEAR('$ngayChon') THEN KiemDinh_DonGia ELSE 0 END) as nam
                FROM tbl_sanluong_kiemdinh
                WHERE KiemDinh_MaTram LIKE '$maTinh%'
            ";
                $totals = DB::select($query);
            }


            $combinedTotals = (object) [
                'ngay' => 0,
                'tuan' => 0,
                'thang' => 0,
                'quy' => 0,
                'nam' => 0
            ];

            foreach ($totals as $total) {
                if (!empty($startDate) && !empty($endDate)) {
                    $combinedTotals->ngay += $total;
                    $combinedTotals->tuan += $total;
                    $combinedTotals->thang += $total;
                    $combinedTotals->quy += $total;
                    $combinedTotals->nam += $total;
                } else {
                    $combinedTotals->ngay += $total->ngay;
                    $combinedTotals->tuan += $total->tuan;
                    $combinedTotals->thang += $total->thang;
                    $combinedTotals->quy += $total->quy;
                    $combinedTotals->nam += $total->nam;
                }
            }

            $results[] = [
                'ma_tinh' => $maTinh,
                'totals' => [
                    'ngay' => round($combinedTotals->ngay),
                    'tuan' => round($combinedTotals->tuan),
                    'thang' => round($combinedTotals->thang),
                    'quy' => round($combinedTotals->quy),
                    'nam' => round($combinedTotals->nam)
                ]
            ];
        }

        return response()->json($results);
    }

    public function thongKeTinhTongQuat(Request $request)
    {
        $role = session('role');
        $userId = session('userid');
        $userKhuVuc = null;
        if ($role != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', $userId)
                ->value('user_khuvuc');
        }

        $khuVuc = $role == 3 ? $request->input('khu_vuc') : $userKhuVuc;
        // $khuVuc = $request->input('khu_vuc');
        $ngayChon = $request->input('ngay_chon', date('Y-m-d'));
        $namChon = date('Y', strtotime($ngayChon));

        $maTinhs = DB::table('tbl_tinh')
            ->where('ten_khu_vuc', $khuVuc)
            ->whereIn('ten_khu_vuc', ['TTKV1', 'TTKV2', 'TTKV3', 'TTGPHTVT'])
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
                UNION ALL
                SELECT
                    KiemDinh_DonGia as SanLuong_Gia,
                    KiemDinh_Ngay as SanLuong_Ngay
                FROM tbl_sanluong_kiemdinh
                WHERE KiemDinh_MaTram LIKE ? AND YEAR(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = ?
            ) as subquery
        ";
            $bindings = [
                "$maTinh%",
                $namChon,
                "$maTinh%",
                $namChon,
                "$maTinh%",
                $namChon
            ];

            $data = DB::select($query, $bindings);

            foreach ($data as $row) {
                $results[] = [
                    'ma_tinh' => $maTinh,
                    'tong_san_luong' => [
                        'nam' => round($row->total_nam),
                        'quy_1' => round($row->total_quy_1),
                        'quy_2' => round($row->total_quy_2),
                        'quy_3' => round($row->total_quy_3),
                        'quy_4' => round($row->total_quy_4),
                        'thang_1' => round($row->total_thang_1),
                        'thang_2' => round($row->total_thang_2),
                        'thang_3' => round($row->total_thang_3),
                        'thang_4' => round($row->total_thang_4),
                        'thang_5' => round($row->total_thang_5),
                        'thang_6' => round($row->total_thang_6),
                        'thang_7' => round($row->total_thang_7),
                        'thang_8' => round($row->total_thang_8),
                        'thang_9' => round($row->total_thang_9),
                        'thang_10' => round($row->total_thang_10),
                        'thang_11' => round($row->total_thang_11),
                        'thang_12' => round($row->total_thang_12),
                    ],
                ];
            }
        }

        return response()->json($results);
    }

    public function indexChiTietChart(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('/login');
        }
        return view('thong_ke.chitiet_bieudo');
    }
}

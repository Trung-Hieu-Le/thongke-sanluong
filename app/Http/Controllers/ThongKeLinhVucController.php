<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateInterval, DateTime, DatePeriod;
use Illuminate\Support\Carbon;

class ThongKeLinhVucController extends Controller
{
    public function indexLinhVuc(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('/login');
        }
        return view('thong_ke.thongke_linhvuc');
    }


    private function getKpiNgay($khuVuc, $linhVuc, $currentYear, $currentMonth, $timeFormat)
    {
        $kpiSelect = DB::table('tbl_kpi_quy')
            ->where('ten_khu_vuc', $khuVuc)
            ->where('year', $currentYear)
            ->where('noi_dung', $linhVuc)
            ->first();

        if ($kpiSelect) {
            if ($timeFormat == 'tuan' || $timeFormat == 'thang') {
                $monthlyKpiField = 'kpi_thang_' . $currentMonth;
                $kpi_thang = $kpiSelect->$monthlyKpiField;
                $kpi_ngay = $kpi_thang / 30;
            } elseif ($timeFormat == 'quy') {
                $currentQuarter = ceil($currentMonth / 3);
                $quarterlyKpiField = 'kpi_quy_' . $currentQuarter;
                $kpi_quy = $kpiSelect->$quarterlyKpiField;
                $kpi_ngay = $kpi_quy / 90;
            } elseif ($timeFormat == 'nam') {
                $kpi_nam = $kpiSelect->kpi_nam;
                $kpi_ngay = $kpi_nam / 365;
            }
        } else {
            $kpi_ngay = 0;
        }

        return [
            'kpi_ngay' => $kpi_ngay,
        ];
    }

    private function whereClauseTimeFormat($ngayChon, $timeFormat, $startDate, $endDate)
    {
        $currentMonth = intval(date('m', strtotime($ngayChon)));
        $currentYear = date('Y', strtotime($ngayChon));
        $currentQuarter = ceil($currentMonth / 3);

        $whereClauseSanLuong = $whereClauseKiemDinh = "";
        switch ($timeFormat) {
            case 'ngay':
                $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') = DATE('$ngayChon')";
                $whereClauseKiemDinh = "STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y') = DATE('$ngayChon')";
                break;
            case 'tuan':
                $weekNumber = date('W', strtotime($ngayChon));
                $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), 1) = $weekNumber";
                $whereClauseKiemDinh = "YEAR(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = $currentYear AND WEEK(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y'), 1) = $weekNumber";
                break;
            case 'thang':
                $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentMonth";
                $whereClauseKiemDinh = "YEAR(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = $currentYear AND MONTH(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = $currentMonth";
                break;
            case 'quy':
                $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentQuarter";
                $whereClauseKiemDinh = "YEAR(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = $currentYear AND QUARTER(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = $currentQuarter";
                break;
            case 'nam':
                $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear";
                $whereClauseKiemDinh = "YEAR(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = $currentYear";
                break;
        }

        if (!empty($startDate) && !empty($endDate)) {
            $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') BETWEEN DATE('$startDate') AND DATE('$endDate')";
            $whereClauseKiemDinh = "STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y') BETWEEN DATE('$startDate') AND DATE('$endDate')";
        }

        return [$whereClauseSanLuong, $whereClauseKiemDinh];
    }

    private function getDistinctDays($khuVuc, $linhVuc, $ngayChon, $timeFormat, $startDate, $endDate)
    {
        $maTinhs = DB::table('tbl_tram')
            ->where('khu_vuc', $khuVuc)
            ->whereIn('khu_vuc', ['TTKV1', 'TTKV2', 'TTKV3', 'TTGPHTVT'])
            ->distinct()
            ->orderBy('ma_tinh')
            ->pluck('ma_tinh')->toArray();
        [$whereClauseSanLuong, $whereClauseKiemDinh] = $this->whereClauseTimeFormat($ngayChon, $timeFormat, $startDate, $endDate);
        if ($linhVuc == "EC") {
            $distinctQuery = DB::table('tbl_sanluong')
                ->select(DB::raw("DATE(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) AS distinct_date"))
                ->whereRaw($whereClauseSanLuong)
                ->whereIn(DB::raw("LEFT(SanLuong_Tram, 3)"), $maTinhs);
        } elseif ($linhVuc == "Kiểm định") {
            $distinctQuery = DB::table('tbl_sanluong_kiemdinh')
                ->select(DB::raw("DATE(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) AS distinct_date"))
                ->whereRaw($whereClauseKiemDinh) // Tạo where clause tương tự như SanLuong
                ->whereIn(DB::raw("LEFT(KiemDinh_MaTram, 3)"), $maTinhs);
        } else {
            $distinctQuery = DB::table('tbl_sanluong_khac')
                ->select(DB::raw("DATE(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) AS distinct_date"))
                ->whereRaw($whereClauseSanLuong)
                ->where('SanLuong_TenHangMuc', $linhVuc)
                ->where('SanLuong_KhuVuc', $khuVuc);
        }

        $distinctDays = DB::table(DB::raw("({$distinctQuery->toSql()}) as subquery"))
            ->mergeBindings($distinctQuery)
            ->distinct()
            ->count('distinct_date');

        return $distinctDays;
    }


    public function indexChiTietChart(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('/login');
        }
        return view('thong_ke.chitiet_bieudo');
    }

    private function getTotalSanLuongLinhVuc($khuVuc, $linhVuc, $startDate, $endDate)
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
                    ->where('linh_vuc', $linhVuc)
                    ->where('khu_vuc', $khuVuc)
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
        return array_sum($totals);
    }

    public function thongKeLinhVuc(Request $request)
    {
        $timeFormat = $request->input('time_format');
        $currentYear = $request->input('nam', date('Y'));
        $currentMonth = $request->input('thang', date('n'));
        $ngayChon = date("Y-m-d", strtotime("$currentYear-$currentMonth-01"));
        $currentQuarter = ceil($currentMonth / 3);
        $userRole = session('role');
        $userId = session('userid');
        $userKhuVuc = null;
        if ($userRole != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', $userId)
                ->value('user_khuvuc');
        }

        $day = (int)date('d', strtotime($ngayChon));
        $month = (int)date('m', strtotime($ngayChon));
        $year = (int)date('Y', strtotime($ngayChon));
        switch ($timeFormat) {
            case 'ngay':
                $startDate = $endDate = $ngayChon;
                break;
            case 'tuan':
                // Tạo khoảng thời gian cho tuần
                if ($day >= 1 && $day <= 7) {
                    $startDate = "$year-$month-01";
                    $endDate = "$year-$month-07";
                } elseif ($day >= 8 && $day <= 14) {
                    $startDate = "$year-$month-08";
                    $endDate = "$year-$month-14";
                } elseif ($day >= 15 && $day <= 21) {
                    $startDate = "$year-$month-15";
                    $endDate = "$year-$month-21";
                } else {
                    $startDate = "$year-$month-22";
                    $endDate = date('Y-m-t', strtotime($ngayChon));
                }
                break;
            case 'thang':
                $startDate = date('Y-m-01', strtotime($ngayChon));
                $endDate = date('Y-m-t', strtotime($ngayChon));
                break;
            case 'quy':
                $quarter = ceil($month / 3);
                $startDate = date('Y-m-d', strtotime("$year-" . (($quarter - 1) * 3 + 1) . "-01"));
                $endDate = date('Y-m-t', strtotime("$year-" . ($quarter * 3) . "-01"));
                break;
            case 'nam':
                $startDate = "$year-01-01";
                $endDate = "$year-12-31";
                break;
            default:
                return response()->json(['error' => 'Thời gian không hợp lệ']);
        }

        $khuVucs = DB::table('tbl_tram')
            ->distinct()
            ->whereIn('khu_vuc', ['TTKV1', 'TTKV2', 'TTKV3', 'TTGPHTVT'])
            ->pluck('khu_vuc');

        $results = [];

        foreach ($khuVucs as $khuVuc) {
            if ($userRole != 3 && $khuVuc != $userKhuVuc) {
                continue;
            }

            $sanluongKhacData = DB::table('tbl_sanluong_khac')
                ->select('SanLuong_TenHangMuc')
                ->where('SanLuong_KhuVuc', $khuVuc)
                ->groupBy('SanLuong_TenHangMuc')->get()->toArray();

            $totalEC = $this->getTotalSanLuongLinhVuc($khuVuc, 'EC', $startDate, $endDate);
            $kpiDataEC = $this->getKpiNgay($khuVuc, "EC", $currentYear, $currentMonth, $timeFormat);
            $daysEC = $this->getDistinctDays($khuVuc, "EC", $ngayChon, $timeFormat, null, null);
            $results[$khuVuc]['EC'] = [
                'ten_linh_vuc' => 'EC',
                'total' => round($totalEC / 1e9, 2),
                'kpi' => $kpiDataEC['kpi_ngay'] * $daysEC
            ];

            foreach ($sanluongKhacData as $row) {
                $totalKhac = $this->getTotalSanLuongLinhVuc($khuVuc, $row->SanLuong_TenHangMuc, $startDate, $endDate);
                if ($totalKhac > 0) {
                    $kpiDataKhac = $this->getKpiNgay($khuVuc, $row->SanLuong_TenHangMuc, $currentYear, $currentMonth, $timeFormat);
                    $daysKhac = $this->getDistinctDays($khuVuc, $row->SanLuong_TenHangMuc, $ngayChon, $timeFormat, null, null);
                    $results[$khuVuc][$row->SanLuong_TenHangMuc] = [
                        'ten_linh_vuc' => $row->SanLuong_TenHangMuc,
                        'total' => round($totalKhac / 1e9, 2),
                        'kpi' => $kpiDataKhac['kpi_ngay'] * $daysKhac
                    ];
                }
            }
            // foreach ($sanluongKiemdinhData as $row) {
            //     $kpiDataKiemDinh = $this->getKpiNgay($khuVuc, "Kiểm định", $currentYear, $currentMonth, $timeFormat);
            //     $daysKiemDinh = $this->getDistinctDays($khuVuc, "Kiểm định", $ngayChon, $timeFormat, null, null);
            //     $results[$khuVuc][$row->KiemDinh_NoiDung] = [
            //         'ten_linh_vuc' => 'Kiểm định',
            //         'total' => round($row->SanLuong_Gia / 1e9, 2),
            //         'kpi' => $kpiDataKiemDinh['kpi_ngay'] * $daysKiemDinh
            //     ];
            // }
            $totalKiemDinh = $this->getTotalSanLuongLinhVuc($khuVuc, 'Kiểm định', $startDate, $endDate);
            if ($totalKiemDinh) {
                $kpiDataKiemDinh = $this->getKpiNgay($khuVuc, "Kiểm định", $currentYear, $currentMonth, $timeFormat);
                $daysKiemDinh = $this->getDistinctDays($khuVuc, "Kiểm định", $ngayChon, $timeFormat, null, null);
                $results[$khuVuc]['Kiểm định'] = [
                    'ten_linh_vuc' => 'Kiểm định',
                    'total' => round($totalKiemDinh / 1e9, 2),
                    'kpi' => $kpiDataKiemDinh['kpi_ngay'] * $daysKiemDinh
                ];
            }
        }


        $finalResults = [];
        foreach ($results as $khuVuc => $data) {
            foreach ($data as $item) {
                $item['khu_vuc'] = $khuVuc;
                $finalResults[] = $item;
            }
        }

        // Sort final results by khu_vuc in ascending order
        usort($finalResults, function ($a, $b) {
            return strcmp($a['khu_vuc'], $b['khu_vuc']);
        });

        return response()->json($finalResults);
    }
}

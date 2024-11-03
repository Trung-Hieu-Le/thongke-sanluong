<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateInterval, DateTime, DatePeriod;
use Illuminate\Support\Carbon;

class ThongKeTongQuatController extends Controller
{
    public function indexKhuVuc(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('/login');
        }
        $role = session('role');
        $userId = session('userid');
        $userKhuVuc = null;
        if ($role != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', $userId)
                ->value('user_khuvuc');
        }
        $khuVucs = DB::table('tbl_tinh')
            ->distinct()
            ->whereIn('ten_khu_vuc', ['TTKV1', 'TTKV2', 'TTKV3', 'TTGPHTVT'])
            ->orderBy('ten_khu_vuc')
            ->pluck('ten_khu_vuc');
        $hopDongs = DB::table('tbl_hopdong')->select('HopDong_Id', 'HopDong_SoHopDong')->get();
        $doiTacs = DB::table('tbl_user')->select('user_id', 'user_name')->get();
        $linhVucs = DB::table('tbl_sanluongkhac_noidung')->distinct()->select('noi_dung')->get();
        return view('thong_ke.thongke_tongquat', compact('hopDongs', 'khuVucs', 'doiTacs', 'linhVucs'));
    }

    public function thongKeTongThangVaNam(Request $request)
    {
        $timeFormat = $request->input('time_format', 'thang');
        $ngayChon = $request->input('ngay_chon', date('Y-m-d')); // mặc định là ngày hiện tại
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $role = session('role');
        $userKhuVuc = null;
        if ($role != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', session('userid'))
                ->value('user_khuvuc');
        }
        $khuVucs = DB::table('tbl_tram')
            ->distinct()
            ->whereIn('khu_vuc', ['TTKV1', 'TTKV2', 'TTKV3', 'TTGPHTVT'])
            ->orderBy('khu_vuc')
            ->pluck('khu_vuc');
        //TODO: khi đổi timeFormat thì đổi lại KPI
        $currentMonth = intval(date('m', strtotime($ngayChon)));
        $currentYear = date('Y', strtotime($ngayChon));
        $totalMonth = 0;
        $totalYear = 0;
        $kpiMonth = 0;
        $kpiYear = 0;
        $details = [];

        foreach ($khuVucs as $khuVuc) {
            if ($role != 3 && $khuVuc != $userKhuVuc) {
                continue;
            }

            $kpiDataNam = $this->getKpiNgay($khuVuc, $currentYear, $currentMonth, 'nam');
            $kpiDataThang = $this->getKpiNgay($khuVuc, $currentYear, $currentMonth, 'thang');
            // $kpi_ngay = $kpiData['kpi_ngay'];
            $daysInMonth = $this->getDistinctDays($khuVuc, $ngayChon, 'thang', null, null);
            $daysInYear = $this->getDistinctDays($khuVuc, $ngayChon, 'nam', null, null);
            $kpi_thang = $kpiDataThang['kpi_ngay'] * $daysInMonth;
            $kpi_nam = $kpiDataNam['kpi_ngay'] * $daysInYear;
            $totalThang = $this->getTotalSanLuong($khuVuc, $ngayChon, null, null, null, 'thang', null, null);
            $totalNam = $this->getTotalSanLuong($khuVuc, $ngayChon, null, null, null, 'nam', null, null);

            // Cộng dồn tổng tháng, tổng năm, KPI tháng, KPI năm
            $totalMonth += $totalThang;
            $totalYear += $totalNam;
            $kpiMonth += $kpi_thang;
            $kpiYear += $kpi_nam;

            $kpiData = $this->getKpiNgay($khuVuc, $currentYear, $currentMonth, $timeFormat);
            $daysDetail = $this->getDistinctDays($khuVuc, $ngayChon, $timeFormat, $startDate, $endDate);
            $kpiDetail = $kpiData['kpi_ngay'] * $daysDetail;
            $totalDetail = $this->getTotalSanLuong($khuVuc, $ngayChon, null, null, null, $timeFormat, $startDate, $endDate);
            $details[] = [
                'khuVuc' => $khuVuc,
                'total' => round($totalDetail),
                'totalKpi' => round($kpiDetail, 2),
                'kpi' => ($kpiDetail > 0) ? round($totalDetail / 1e7 / $kpiDetail, 2) : 0
            ];
        }

        $results = [
            'totalMonth' => round($totalMonth), // Tổng tháng
            'totalYear' => round($totalYear), // Tổng năm
            'totalKpiMonth' => round($kpiMonth, 2),
            'totalKpiYear' => round($kpiYear, 2),
            'kpiMonth' => ($kpiMonth > 0) ? round($totalMonth / 1e7 / $kpiMonth, 2) : 0, // KPI tháng
            'kpiYear' => ($kpiYear > 0) ? round($totalYear / 1e7 / $kpiYear, 2) : 0, // KPI năm
            'details' => $details
        ];

        return response()->json($results);
    }
    public function thongKeKhuVuc(Request $request)
    {
        // Lấy tham số từ request
        $timeFormat = $request->input('time_format');
        $ngayChon = $request->input('ngay_chon', date('Y-m-d'));
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $hopDongId = $request->input('hop_dong');
        $role = session('role');
        $userId = $request->input('user');
        $linhVuc = $request->input('linh_vuc');
        $userKhuVuc = null;
        if ($role != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', session('userid'))
                ->value('user_khuvuc');
        }

        $khuVucs = DB::table('tbl_tram')
            ->distinct()
            ->whereIn('khu_vuc', ['TTKV1', 'TTKV2', 'TTKV3', 'TTGPHTVT'])
            ->orderBy('khu_vuc')
            ->pluck('khu_vuc');
        $currentMonth = intval(date('m', strtotime($ngayChon)));
        $currentYear = date('Y', strtotime($ngayChon));
        $currentQuarter = ceil($currentMonth / 3);
        $results = [];

        foreach ($khuVucs as $khuVuc) {
            if ($role != 3 && $khuVuc != $userKhuVuc) {
                continue;
            }

            // $maTinhs = DB::table('tbl_tram')
            // ->where('khu_vuc', $khuVuc)
            // ->whereIn('khu_vuc', ['TTKV1', 'TTKV2', 'TTKV3', 'TTGPHTVT'])
            //     ->pluck('ma_tinh')
            //     ->toArray();

            $kpiData = $this->getKpiNgay($khuVuc, $currentYear, $currentMonth, $timeFormat);

            $distinctDays = $this->getDistinctDays($khuVuc, $ngayChon, $timeFormat, $startDate, $endDate);
            $kpi = $kpiData['kpi_ngay'] * $distinctDays;

            $totalSanLuong = $this->getTotalSanLuong($khuVuc, $ngayChon, $hopDongId, $userId, $linhVuc, $timeFormat, $startDate, $endDate);
            $results[] = [
                'ten_khu_vuc' => $khuVuc,
                'total' => round($totalSanLuong / 1e9, 2),
                'kpi' => round($kpi, 2),
            ];
        }

        return response()->json($results);
    }

    //TODO: KPI năm luôn đổi??? do kpi_thang, cần fix funct này (dùng timeFormat)
    private function getKpiNgay($khuVuc, $currentYear, $currentMonth, $timeFormat)
    {
        $kpiSelect = DB::table('tbl_kpi_quy')
            ->where('ten_khu_vuc', $khuVuc)
            ->where('year', $currentYear)
            ->where('noi_dung', 'Tổng sản lượng')
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

        $whereClauseSanLuong = $whereClauseThaoLap = $whereClauseKiemDinh = "";
        switch ($timeFormat) {
            case 'ngay':
                $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') = DATE('$ngayChon')";
                $whereClauseThaoLap = "STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') = DATE('$ngayChon')";
                $whereClauseKiemDinh = "STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y') = DATE('$ngayChon')";
                break;
            case 'tuan':
                $weekNumber = date('W', strtotime($ngayChon));
                $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), 1) = $weekNumber";
                $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear AND WEEK(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), 1) = $weekNumber";
                $whereClauseKiemDinh = "YEAR(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = $currentYear AND WEEK(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y'), 1) = $weekNumber";
                break;
            case 'thang':
                $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentMonth";
                $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear AND MONTH(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentMonth";
                $whereClauseKiemDinh = "YEAR(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = $currentYear AND MONTH(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = $currentMonth";
                break;
            case 'quy':
                $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentQuarter";
                $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear AND QUARTER(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentQuarter";
                $whereClauseKiemDinh = "YEAR(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = $currentYear AND QUARTER(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = $currentQuarter";
                break;
            case 'nam':
                $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear";
                $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear";
                $whereClauseKiemDinh = "YEAR(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = $currentYear";
                break;
        }

        if (!empty($startDate) && !empty($endDate)) {
            $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') BETWEEN DATE('$startDate') AND DATE('$endDate')";
            $whereClauseThaoLap = "STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') BETWEEN DATE('$startDate') AND DATE('$endDate')";
            $whereClauseKiemDinh = "STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y') BETWEEN DATE('$startDate') AND DATE('$endDate')";
        }

        return [$whereClauseSanLuong, $whereClauseThaoLap, $whereClauseKiemDinh];
    }

    private function getDistinctDays($khuVuc, $ngayChon, $timeFormat, $startDate, $endDate)
    {
        $maTinhs = DB::table('tbl_tram')
            ->where('khu_vuc', $khuVuc)
            ->whereIn('khu_vuc', ['TTKV1', 'TTKV2', 'TTKV3', 'TTGPHTVT'])
            ->pluck('ma_tinh')
            ->toArray();
        [$whereClauseSanLuong, $whereClauseThaoLap, $whereClauseKiemDinh] = $this->whereClauseTimeFormat($ngayChon, $timeFormat, $startDate, $endDate);
        $distinctQuery = DB::table('tbl_sanluong')
            ->select(DB::raw("DATE(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) AS distinct_date"))
            ->whereRaw($whereClauseSanLuong)
            ->whereIn(DB::raw("LEFT(SanLuong_Tram, 3)"), $maTinhs)
            ->union(
                DB::table('tbl_sanluong_thaolap')
                    ->select(DB::raw("DATE(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) AS distinct_date"))
                    ->whereRaw($whereClauseThaoLap)
                    ->whereIn(DB::raw("LEFT(ThaoLap_MaTram, 3)"), $maTinhs)
            )
            ->union(
                DB::table('tbl_sanluong_khac')
                    ->select(DB::raw("DATE(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) AS distinct_date"))
                    ->whereRaw($whereClauseSanLuong)
                    ->where('SanLuong_KhuVuc', $khuVuc)
            )
            ->union(
                DB::table('tbl_sanluong_kiemdinh')
                    ->select(DB::raw("DATE(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) AS distinct_date"))
                    ->whereRaw($whereClauseKiemDinh) // Tạo where clause tương tự như SanLuong
                    ->whereIn(DB::raw("LEFT(KiemDinh_MaTram, 3)"), $maTinhs)
            );

        $distinctDays = DB::table(DB::raw("({$distinctQuery->toSql()}) as subquery"))
            ->mergeBindings($distinctQuery)
            ->distinct()
            ->count('distinct_date');

        return $distinctDays;
    }
    private function getTotalSanLuong($khuVuc, $ngayChon, $hopDongId = null, $userId = null, $linhVuc = null, $timeFormat, $startDate, $endDate, $whereClauseSanLuong = null, $whereClauseThaoLap = null, $whereClauseKiemDinh = null)
    {
        if (empty($whereClauseSanLuong) || empty($whereClauseThaoLap)) {
            [$whereClauseSanLuong, $whereClauseThaoLap, $whereClauseKiemDinh] = $this->whereClauseTimeFormat($ngayChon, $timeFormat, $startDate, $endDate);
        }
        if (empty($hopDongId) && empty($userId) && empty($linhVuc) && !empty($startDate) && !empty($endDate)) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            $startMonth = $start->month;
            $startYear = $start->year;
            $endMonth = $end->month;
            $endYear = $end->year;

            $totalSanLuong = 0;

            for ($year = $startYear; $year <= $endYear; $year++) {
                $startMonthLoop = ($year == $startYear) ? $startMonth : 1;
                $endMonthLoop = ($year == $endYear) ? $endMonth : 12;

                for ($month = $startMonthLoop; $month <= $endMonthLoop; $month++) {
                    $formattedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
                    $query = DB::table('tbl_tonghop_sanluong')
                        ->select('ma_tinh', 'SanLuong_Ngay_01', 'SanLuong_Ngay_02', 'SanLuong_Ngay_03', 'SanLuong_Ngay_04', 'SanLuong_Ngay_05', 'SanLuong_Ngay_06', 'SanLuong_Ngay_07', 'SanLuong_Ngay_08', 'SanLuong_Ngay_09', 'SanLuong_Ngay_10', 'SanLuong_Ngay_11', 'SanLuong_Ngay_12', 'SanLuong_Ngay_13', 'SanLuong_Ngay_14', 'SanLuong_Ngay_15', 'SanLuong_Ngay_16', 'SanLuong_Ngay_17', 'SanLuong_Ngay_18', 'SanLuong_Ngay_19', 'SanLuong_Ngay_20', 'SanLuong_Ngay_21', 'SanLuong_Ngay_22', 'SanLuong_Ngay_23', 'SanLuong_Ngay_24', 'SanLuong_Ngay_25', 'SanLuong_Ngay_26', 'SanLuong_Ngay_27', 'SanLuong_Ngay_28', 'SanLuong_Ngay_29', 'SanLuong_Ngay_30', 'SanLuong_Ngay_31')
                        ->where('khu_vuc', $khuVuc)
                        ->where('year', $year)
                        ->where('month', $formattedMonth)
                        ->get();

                    if ($year == $startYear && $month == $startMonth) {
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
                                $totalSanLuong += floatval($data->$dayColumn);
                            }
                        }
                    }
                }
            }
            
            return $totalSanLuong;
        }
        $sanluongDataQuery = DB::select("
        SELECT 
            ma_tinh,
            SanLuong_Tram,
            HopDong_SoHopDong,
            SanLuong_Ngay,
            SUM(SanLuong_Gia) AS SanLuong_Gia,
            khu_vuc 
        FROM (
            SELECT 
                LEFT(sanluong.SanLuong_Tram, 3) AS ma_tinh,
                sanluong.SanLuong_Tram,
                hopdong.HopDong_SoHopDong,
                sanluong.SanLuong_Ngay, -- Thêm cột SanLuong_Ngay để nhóm theo ngày
                SUM(sanluong.SanLuong_Gia) AS SanLuong_Gia,
                tram.khu_vuc
            FROM (
                SELECT 
                    SanLuong_Tram,
                    HopDong_Id,
                    SanLuong_TenHangMuc,
                    SanLuong_Gia,
                    SanLuong_Ngay
                FROM (
                    SELECT 
                        SanLuong_Tram,
                        tbl_sanluong.HopDong_Id,
                        SanLuong_TenHangMuc,
                        SanLuong_Gia,
                        SanLuong_Ngay,
                        ROW_NUMBER() OVER (
                            PARTITION BY SanLuong_Tram, HopDong_Id, SanLuong_TenHangMuc 
                            ORDER BY STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')
                        ) AS row_num
                    FROM tbl_sanluong
                    JOIN tbl_tram ON tbl_sanluong.SanLuong_Tram = tbl_tram.ma_tram
                    LEFT JOIN tbl_hopdong ON tbl_sanluong.HopDong_Id = tbl_hopdong.HopDong_Id
                    WHERE ten_hinh_anh_da_xong <> ''
                    AND EXISTS (
                        SELECT 1 
                        FROM tbl_hinhanh 
                        WHERE tbl_hinhanh.ma_tram = tbl_sanluong.SanLuong_Tram
                    )
                ) AS ranked_sanluong
                WHERE row_num = 1
            ) AS sanluong
            JOIN (
                SELECT 
                    ma_tram,
                    khu_vuc,
                    hopdong_id,
                    ROW_NUMBER() OVER (PARTITION BY ma_tram ORDER BY ma_tram) AS rn
                FROM tbl_tram
            ) AS tram ON sanluong.SanLuong_Tram = tram.ma_tram AND tram.rn = 1
            LEFT JOIN tbl_hopdong AS hopdong ON sanluong.HopDong_Id = hopdong.HopDong_Id
            GROUP BY 
                sanluong.SanLuong_Tram,
                tram.khu_vuc, 
                hopdong.HopDong_SoHopDong,
                sanluong.SanLuong_Ngay -- Nhóm thêm theo SanLuong_Ngay để chia theo ngày
            HAVING COUNT(sanluong.SanLuong_Tram) > 0
        ) AS sanluong_subquery
        WHERE $whereClauseSanLuong AND khu_vuc = '$khuVuc'
        GROUP BY 
            ma_tinh, 
            SanLuong_Tram, 
            khu_vuc, 
            HopDong_SoHopDong, 
            SanLuong_Ngay -- Nhóm theo ngày để chia số tiền cho từng ngày
        ORDER BY 
            SanLuong_Tram ASC, 
            SanLuong_Ngay ASC;
        ");

        $sanluongData = collect($sanluongDataQuery);
        if (!empty($linhVuc) && $linhVuc != "EC") {
            $sanluongData = collect();
        } else {
            if (!empty($hopDongId)) {
                $sanluongData = $sanluongData->where('HopDong_Id', $hopDongId);
            }
        }

        $sanluongKhacData = collect();
        if (empty($hopDongId)) {
            $sanluongKhacQuery = DB::table('tbl_sanluong_khac')
                ->select('SanLuong_Gia')
                ->whereRaw($whereClauseSanLuong)
                ->where('SanLuong_KhuVuc', $khuVuc);
            if (!empty($userId)) {
                $sanluongKhacQuery->where('user_id', $userId);
            }
            if (!empty($linhVuc)) {
                $sanluongKhacQuery->where('SanLuong_TenHangMuc', $linhVuc);
            }
            $sanluongKhacData = $sanluongKhacQuery->get();
        }

        $thaolapQuery = DB::select("
            SELECT 
                ma_tinh, 
                SanLuong_Tram, 
                HopDong_SoHopDong, 
                ThaoLap_Ngay,
                SUM(SanLuong_Gia) AS SanLuong_Gia, 
                khu_vuc 
            FROM (
                SELECT 
                    LEFT(ThaoLap_MaTram, 3) AS ma_tinh,
                    ThaoLap_MaTram AS SanLuong_Tram,
                    tbl_hopdong.HopDong_SoHopDong,
                    ThaoLap_Ngay,
                    MAX(
                        ThaoLap_Anten * DonGia_Anten 
                        + ThaoLap_RRU * DonGia_RRU 
                        + ThaoLap_TuThietBi * DonGia_TuThietBi 
                        + ThaoLap_CapNguon * DonGia_CapNguon
                    ) AS SanLuong_Gia,
                    FirstTram.khu_vuc
                FROM tbl_sanluong_thaolap
                JOIN (
                    SELECT 
                        ma_tram,
                        khu_vuc,
                        hopdong_id
                    FROM tbl_tram
                ) AS FirstTram ON tbl_sanluong_thaolap.ThaoLap_MaTram = FirstTram.ma_tram 
                AND FirstTram.hopdong_id = tbl_sanluong_thaolap.HopDong_Id
                LEFT JOIN tbl_hopdong ON tbl_sanluong_thaolap.HopDong_Id = tbl_hopdong.HopDong_Id
                GROUP BY 
                    ThaoLap_MaTram, 
                    FirstTram.khu_vuc, 
                    tbl_hopdong.HopDong_SoHopDong, 
                    ThaoLap_Ngay 
            ) AS thaolap_kiemdinh_subquery
            WHERE $whereClauseThaoLap AND khu_vuc = '$khuVuc'
            GROUP BY 
                ma_tinh, 
                SanLuong_Tram, 
                khu_vuc, 
                HopDong_SoHopDong, 
                ThaoLap_Ngay
            ORDER BY 
                SanLuong_Tram ASC, 
                ThaoLap_Ngay ASC;
        ");
        $thaolapData = collect($thaolapQuery);
        if (!empty($linhVuc) && $linhVuc == "EC") {
            $thaolapData = collect();
        } else {
            if (!empty($hopDongId)) {
                $thaolapData = $thaolapData->where('HopDong_Id', $hopDongId);
            }
        }
        $kiemdinhQuery = DB::select("
            SELECT 
                ma_tinh,
                SanLuong_Tram,
                HopDong_SoHopDong,
                KiemDinh_Ngay,
                SUM(SanLuong_Gia) AS SanLuong_Gia,
                khu_vuc
            FROM (
                SELECT 
                    LEFT(KiemDinh_MaTram, 3) AS ma_tinh,
                    KiemDinh_MaTram AS SanLuong_Tram,
                    tbl_hopdong.HopDong_SoHopDong,
                    KiemDinh_Ngay,
                    MAX(KiemDinh_DonGia ) AS SanLuong_Gia,
                    FirstTram.khu_vuc
                FROM tbl_sanluong_kiemdinh
                JOIN (
                    SELECT 
                        ma_tram,
                        khu_vuc,
                        hopdong_id
                    FROM tbl_tram
                ) AS FirstTram ON tbl_sanluong_kiemdinh.KiemDinh_MaTram = FirstTram.ma_tram 
                AND FirstTram.hopdong_id = tbl_sanluong_kiemdinh.HopDong_Id
                LEFT JOIN tbl_hopdong ON tbl_sanluong_kiemdinh.HopDong_Id = tbl_hopdong.HopDong_Id
                GROUP BY 
                    KiemDinh_MaTram, 
                    KiemDinh_Ngay, 
                    FirstTram.khu_vuc, 
                    tbl_hopdong.HopDong_SoHopDong
            ) AS thaolap_kiemdinh_subquery
            WHERE $whereClauseKiemDinh AND khu_vuc = '$khuVuc'
            GROUP BY 
                ma_tinh, 
                SanLuong_Tram, 
                khu_vuc, 
                HopDong_SoHopDong, 
                KiemDinh_Ngay
            ORDER BY 
                SanLuong_Tram ASC, 
                KiemDinh_Ngay ASC;

        ");
        $kiemdinhData = collect($kiemdinhQuery);
        if (!empty($linhVuc) && $linhVuc == "EC") {
            $kiemdinhData = collect();
        } else {
            if (!empty($hopDongId)) {
                $kiemdinhData = $kiemdinhData->where('HopDong_Id', $hopDongId);
            }
        }

        $combinedData = $sanluongData->merge($sanluongKhacData)->merge($thaolapData)->merge($kiemdinhData);
        $combinedData = $combinedData->map(function ($item) {
            $item->SanLuong_Gia = floatval($item->SanLuong_Gia);
            return $item;
        });
        $total = $combinedData->sum('SanLuong_Gia');

        return $total;
    }

    public function thongKeXuTheKhuVuc(Request $request)
    {
        // Lấy tham số từ request
        $timeFormat = $request->input('time_format');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $hopDongId = $request->input('hop_dong');
        $userId = $request->input('user');
        $linhVuc = $request->input('linh_vuc');
        $role = session('role');
        $userKhuVuc = ($role != 3) ? DB::table('tbl_user')->where('user_id', session('userid'))->value('user_khuvuc') : null;

        $khuVucs = DB::table('tbl_tram')->distinct()
            ->whereIn('khu_vuc', ['TTKV1', 'TTKV2', 'TTKV3', 'TTGPHTVT'])
            ->orderBy('khu_vuc')->pluck('khu_vuc');

        $results = [];

        foreach ($khuVucs as $khuVuc) {
            if ($role != 3 && $khuVuc != $userKhuVuc) {
                continue;
            }

            // $maTinhs = DB::table('tbl_tinh')->where('ten_khu_vuc', $khuVuc)
            //     ->whereIn('ten_khu_vuc', ['TTKV1', 'TTKV2', 'TTKV3', 'TTGPHTVT'])
            //     ->pluck('ma_tinh')->toArray();

            // Khởi tạo mảng lưu kết quả chi tiết
            $detailedResults = [];
            $periods = [];

            switch ($timeFormat) {
                case 'tuan':
                    $days = $this->getDaysInRange($startDate, $endDate);
                    $periods = array_map(fn($day) => [
                        'start' => $day['date'],
                        'end' => $day['date'],
                        'label' => $day['label']
                    ], $days);
                    break;
                case 'thang':
                case 'quy':
                    $weeks = $this->getWeeksInRange($startDate, $endDate);
                    $periods = array_map(fn($week, $index) => [
                        'start' => $week['start'],
                        'end' => $week['end'],
                        'label' => "Tuần " . ($index + 1)
                    ], $weeks, array_keys($weeks));
                    break;
                case 'nam':
                    $months = $this->getMonthsInRange($startDate, $endDate);
                    $periods = array_map(fn($month) => [
                        'start' => $month['start'],
                        'end' => $month['end'],
                        'label' => "Tháng " . (new DateTime($month['start']))->format('n')
                    ], $months);
                    break;
                default:
                    return response()->json(['error' => 'Thời gian không hợp lệ']);
            }

            foreach ($periods as $period) {
                $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') BETWEEN '{$period['start']}' AND '{$period['end']}'";
                $whereClauseThaoLap = "STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') BETWEEN '{$period['start']}' AND '{$period['end']}'";
                $whereClauseKiemDinh = "STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y') BETWEEN '{$period['start']}' AND '{$period['end']}'";
                $totalSanLuong = $this->getTotalSanLuong($khuVuc, null, $hopDongId, $userId, $linhVuc, $timeFormat, $period['start'], $period['end'], $whereClauseSanLuong, $whereClauseThaoLap, $whereClauseKiemDinh);
                $detailedResults[] = [
                    'total' => round($totalSanLuong / 1e9, 2),
                    'time_period' => $period['label']
                ];
            }

            // Thêm kết quả vào mảng
            $results[] = [
                'ten_khu_vuc' => $khuVuc,
                'details' => $detailedResults
            ];
        }

        return response()->json($results);
    }


    private function getDaysInRange($startDate, $endDate)
    {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $days = [];

        while ($start <= $end) {
            $days[] = [
                'date' => $start->format('Y-m-d'),
                'label' => $start->format('d-m-Y')
            ];
            $start->modify('+1 day');
        }

        return $days;
    }
    //TODO: sai ngày bắt đầu
    private function getWeeksInRange($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $weeks = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $weekStart = $current->copy()->startOfMonth();
            $weekEnd = $current->copy()->endOfMonth();

            if ($current->day <= 7) {
                $weekStart = $current->copy()->startOfMonth();
                $weekEnd = $weekStart->copy()->addDays(6);
            } elseif ($current->day <= 14) {
                $weekStart = $current->copy()->startOfMonth()->addDays(7);
                $weekEnd = $weekStart->copy()->addDays(6);
            } elseif ($current->day <= 21) {
                $weekStart = $current->copy()->startOfMonth()->addDays(14);
                $weekEnd = $weekStart->copy()->addDays(6);
            } else {
                $weekStart = $current->copy()->startOfMonth()->addDays(21);
                $weekEnd = $current->copy()->endOfMonth();
            }

            if ($weekEnd->gt($end)) {
                $weekEnd = $end;
            }

            $weeks[] = [
                'start' => $weekStart->format('Y-m-d'),
                'end' => $weekEnd->format('Y-m-d'),
                'label' => "Tuần " . (count($weeks) + 1) . " ({$weekStart->format('d-m-Y')} - {$weekEnd->format('d-m-Y')})"
            ];

            $current = $weekEnd->copy()->addDay();
        }

        return $weeks;
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
    
    public function indexChiTietChart(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('/login');
        }
        return view('thong_ke.chitiet_bieudo');
    }
    private function getSanLuongDataCollection()
{
    $sanluongDataQuery = DB::select("
        SELECT 
            ma_tinh, SanLuong_Tram, HopDong_SoHopDong, SanLuong_Ngay, SUM(SanLuong_Gia) AS SanLuong_Gia, khu_vuc 
        FROM (
            SELECT 
                LEFT(sanluong.SanLuong_Tram, 3) AS ma_tinh,
                sanluong.SanLuong_Tram,
                hopdong.HopDong_SoHopDong,
                sanluong.SanLuong_Ngay,
                SUM(sanluong.SanLuong_Gia) AS SanLuong_Gia,
                tram.khu_vuc
            FROM (
                SELECT 
                    SanLuong_Tram,
                    HopDong_Id,
                    SanLuong_TenHangMuc,
                    SanLuong_Gia,
                    SanLuong_Ngay
                FROM (
                    SELECT 
                        SanLuong_Tram,
                        tbl_sanluong.HopDong_Id,
                        SanLuong_TenHangMuc,
                        SanLuong_Gia,
                        SanLuong_Ngay,
                        ROW_NUMBER() OVER (
                            PARTITION BY SanLuong_Tram, HopDong_Id, SanLuong_TenHangMuc 
                            ORDER BY STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')
                        ) AS row_num
                    FROM tbl_sanluong
                    JOIN tbl_tram ON tbl_sanluong.SanLuong_Tram = tbl_tram.ma_tram
                    LEFT JOIN tbl_hopdong ON tbl_sanluong.HopDong_Id = tbl_hopdong.HopDong_Id
                    WHERE ten_hinh_anh_da_xong <> ''
                    AND EXISTS (
                        SELECT 1 
                        FROM tbl_hinhanh 
                        WHERE tbl_hinhanh.ma_tram = tbl_sanluong.SanLuong_Tram
                    )
                ) AS ranked_sanluong
                WHERE row_num = 1
            ) AS sanluong
            JOIN (
                SELECT 
                    ma_tram,
                    khu_vuc,
                    hopdong_id,
                    ROW_NUMBER() OVER (PARTITION BY ma_tram ORDER BY ma_tram) AS rn
                FROM tbl_tram
            ) AS tram ON sanluong.SanLuong_Tram = tram.ma_tram AND tram.rn = 1
            LEFT JOIN tbl_hopdong AS hopdong ON sanluong.HopDong_Id = hopdong.HopDong_Id
            GROUP BY 
                sanluong.SanLuong_Tram,
                tram.khu_vuc, 
                hopdong.HopDong_SoHopDong,
                sanluong.SanLuong_Ngay
            HAVING COUNT(sanluong.SanLuong_Tram) > 0
        ) AS sanluong_subquery
        GROUP BY 
            ma_tinh, 
            SanLuong_Tram, 
            khu_vuc, 
            HopDong_SoHopDong, 
            SanLuong_Ngay
        ORDER BY 
            SanLuong_Tram ASC, 
            SanLuong_Ngay ASC;
    ");

    // Chuyển kết quả từ query thành collection
    return collect($sanluongDataQuery);
}

}

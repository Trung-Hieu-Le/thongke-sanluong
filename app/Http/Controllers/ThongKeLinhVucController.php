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

    public function indexChiTietChart(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('/login');
        }
        return view('thong_ke.chitiet_bieudo');
    }

    public function thongKeLinhVuc(Request $request)
    {
        $timeFormat = $request->input('time_format');
        $currentYear = $request->input('nam', date('Y'));
        $currentMonth = $request->input('thang', date('n'));
        $currentQuarter = ceil($currentMonth / 3);
        $userRole = session('role');
        $userId = session('userid');
        $userKhuVuc = null;
        if ($userRole != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', $userId)
                ->value('user_khuvuc');
        }

        $whereClauseSanLuong = "";
        $whereClauseThaoLap = "";
        switch ($timeFormat) {
            case 'ngay':
                $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') = CURRENT_DATE()";
                $whereClauseThaoLap = "STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') = CURRENT_DATE()";
                $whereClauseKiemDinh = "STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y') = CURRENT_DATE()";
                break;
            case 'tuan':
                $whereClauseSanLuong = "WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), 1) = WEEK(CURRENT_DATE(), 1)";
                $whereClauseThaoLap = "WEEK(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), 1) = WEEK(CURRENT_DATE(), 1)";
                $whereClauseKiemDinh = "WEEK(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y'), 1) = WEEK(CURRENT_DATE(), 1)";
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
            default:
                return response()->json(['error' => 'Thời gian không hợp lệ']);
        }

        $khuVucs = DB::table('tbl_tinh')
            ->distinct()
            ->whereIn('ten_khu_vuc', ['TTKV1', 'TTKV2', 'TTKV3', 'TTGPHTVT'])
            ->pluck('ten_khu_vuc');

        $results = [];

        foreach ($khuVucs as $khuVuc) {
            if ($userRole != 3 && $khuVuc != $userKhuVuc) {
                continue;
            }

            $maTinhs = DB::table('tbl_tinh')
                ->where('ten_khu_vuc', $khuVuc)
                ->whereIn('ten_khu_vuc', ['TTKV1', 'TTKV2', 'TTKV3', 'TTGPHTVT'])
                ->pluck('ma_tinh');

            $sanluongDataQuery = DB::table('tbl_sanluong')
                ->join('tbl_tinh', 'tbl_sanluong.SanLuong_Tram', 'LIKE', DB::raw("CONCAT(tbl_tinh.ma_tinh, '%')"))
                ->select(DB::raw("IFNULL(CAST(SanLuong_Gia AS UNSIGNED), 0) as SanLuong_Gia"))
                ->whereRaw($whereClauseSanLuong)
                ->where('ten_hinh_anh_da_xong', '<>', '')
                ->whereIn('tbl_tinh.ma_tinh', $maTinhs);
            $sanluongData = $sanluongDataQuery->get();

            $sanluongThaolapDataQuery = DB::table('tbl_sanluong_thaolap')
                ->join('tbl_tinh', 'tbl_sanluong_thaolap.ThaoLap_MaTram', 'LIKE', DB::raw("CONCAT(tbl_tinh.ma_tinh, '%')"))
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
                ->whereIn('tbl_tinh.ma_tinh', $maTinhs);
            $sanluongThaolapData = $sanluongThaolapDataQuery->get();

            $totalEC = $sanluongData->sum('SanLuong_Gia') + $sanluongThaolapData->sum('SanLuong_Gia');

            $sanluongKiemdinhDataQuery = DB::table('tbl_sanluong_kiemdinh')
                ->join('tbl_tinh', 'tbl_sanluong_kiemdinh.KiemDinh_MaTram', 'LIKE', DB::raw("CONCAT(tbl_tinh.ma_tinh, '%')"))
                ->select('KiemDinh_NoiDung', DB::raw('IFNULL(SUM(CAST(KiemDinh_DonGia AS UNSIGNED)), 0) as SanLuong_Gia'))
                ->whereRaw($whereClauseKiemDinh)
                ->whereIn('tbl_tinh.ma_tinh', $maTinhs)
                ->groupBy('KiemDinh_NoiDung');
            $sanluongKiemdinhData = $sanluongKiemdinhDataQuery->get();

            $sanluongKhacDataQuery = DB::table('tbl_sanluong_khac')
                ->select('SanLuong_TenHangMuc', DB::raw('SUM(SanLuong_Gia) as total'))
                ->whereRaw($whereClauseSanLuong)
                ->where('SanLuong_KhuVuc', $khuVuc)
                ->groupBy('SanLuong_TenHangMuc');
            $sanluongKhacData = $sanluongKhacDataQuery->get();

            $results[$khuVuc]['EC'] = [
                'ten_linh_vuc' => 'EC',
                'total' => round($totalEC / 1e9, 1),
                'kpi' => 0
            ];

            foreach ($sanluongKhacData as $row) {
                $results[$khuVuc][$row->SanLuong_TenHangMuc] = [
                    'ten_linh_vuc' => $row->SanLuong_TenHangMuc,
                    'total' => round($row->total / 1e9, 1),
                    'kpi' => 0
                ];
            }
            foreach ($sanluongKiemdinhData as $row) {
                $results[$khuVuc][$row->KiemDinh_NoiDung] = [
                    'ten_linh_vuc' => 'Kiểm định',
                    'total' => round($row->SanLuong_Gia / 1e9, 1),
                    'kpi' => 0
                ];
            }
        }

        $kpiDataQuery = DB::table('tbl_kpi_quy')
            ->select('ten_khu_vuc', 'noi_dung', 'kpi_quy_1', 'kpi_quy_2', 'kpi_quy_3', 'kpi_quy_4')
            ->where('year', $currentYear);
        if ($userRole != 3) {
            $kpiDataQuery->where('ten_khu_vuc', $userKhuVuc);
        }
        $kpiData = $kpiDataQuery->get();

        foreach ($kpiData as $kpi) {
            $kpiValue = 0;
            switch ($timeFormat) {
                case 'thang':
                    $kpiValue = round(($kpi->{'kpi_quy_' . $currentQuarter} / 3), 1);
                    break;
                case 'quy':
                    $kpiValue = round($kpi->{'kpi_quy_' . $currentQuarter}, 1);
                    break;
                case 'nam':
                    $kpiValue = round($kpi->kpi_quy_1 + $kpi->kpi_quy_2 + $kpi->kpi_quy_3 + $kpi->kpi_quy_4, 1);
                    break;
            }

            if (isset($results[$kpi->ten_khu_vuc][$kpi->noi_dung])) {
                $results[$kpi->ten_khu_vuc][$kpi->noi_dung]['kpi'] += $kpiValue;
            }
        }

        foreach ($results as &$khuVucData) {
            foreach ($khuVucData as &$result) {
                $result['kpi'] = round($result['kpi'], 1);
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

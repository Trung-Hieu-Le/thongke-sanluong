<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateInterval, DateTime, DatePeriod;
use Illuminate\Support\Carbon;

class ThongKeController extends Controller
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
            ->orderBy('ten_khu_vuc')
            ->pluck('ten_khu_vuc');
        $hopDongs = DB::table('tbl_hopdong')->select('HopDong_Id', 'HopDong_SoHopDong')->get();
        $doiTacs = DB::table('tbl_user')->select('user_id', 'user_name')->get();
        $linhVucs = DB::table('tbl_sanluongkhac_noidung')->distinct()->select('noi_dung')->get();
        return view('thong_ke.thongke_tongquat', compact('hopDongs', 'khuVucs', 'doiTacs', 'linhVucs'));
    }
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
        $khuVucs = DB::table('tbl_tinh')
            ->distinct()
            ->orderBy('ten_khu_vuc')
            ->pluck('ten_khu_vuc');
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
            $maTinhs = DB::table('tbl_tinh')
                ->where('ten_khu_vuc', $khuVuc)
                ->pluck('ma_tinh')
                ->toArray();

            $kpiDataNam = $this->getKpiNgay($khuVuc, $currentYear, $currentMonth, 'nam');
            $kpiDataThang = $this->getKpiNgay($khuVuc, $currentYear, $currentMonth, 'thang');
            // $kpi_ngay = $kpiData['kpi_ngay'];
            $daysInMonth = $this->getDistinctDays($maTinhs, $khuVuc, $ngayChon, 'thang', null, null);
            $daysInYear = $this->getDistinctDays($maTinhs, $khuVuc, $ngayChon, 'nam', null, null);
            $kpi_thang = $kpiDataThang['kpi_ngay'] * $daysInMonth;
            $kpi_nam = $kpiDataNam['kpi_ngay'] * $daysInYear;
            $totalThang = $this->getTotalSanLuong($maTinhs, $khuVuc, $ngayChon, null, null, null, 'thang', null, null);
            $totalNam = $this->getTotalSanLuong($maTinhs, $khuVuc, $ngayChon, null, null, null, 'nam', null, null);

            // Cộng dồn tổng tháng, tổng năm, KPI tháng, KPI năm
            $totalMonth += $totalThang;
            $totalYear += $totalNam;
            $kpiMonth += $kpi_thang;
            $kpiYear += $kpi_nam;

            $kpiData = $this->getKpiNgay($khuVuc, $currentYear, $currentMonth, $timeFormat);
            $daysDetail = $this->getDistinctDays($maTinhs, $khuVuc, $ngayChon, $timeFormat, $startDate, $endDate);
            $kpiDetail = $kpiData['kpi_ngay'] * $daysDetail;
            $totalDetail = $this->getTotalSanLuong($maTinhs, $khuVuc, $ngayChon, null, null, null, $timeFormat, $startDate, $endDate);
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

        $khuVucs = DB::table('tbl_tinh')
            ->distinct()
            ->orderBy('ten_khu_vuc')
            ->pluck('ten_khu_vuc');
        $currentMonth = intval(date('m', strtotime($ngayChon)));
        $currentYear = date('Y', strtotime($ngayChon));
        $currentQuarter = ceil($currentMonth / 3);
        $results = [];

        foreach ($khuVucs as $khuVuc) {
            if ($role != 3 && $khuVuc != $userKhuVuc) {
                continue;
            }

            $maTinhs = DB::table('tbl_tinh')
                ->where('ten_khu_vuc', $khuVuc)
                ->pluck('ma_tinh')
                ->toArray();

            $kpiData = $this->getKpiNgay($khuVuc, $currentYear, $currentMonth, $timeFormat);

            $distinctDays = $this->getDistinctDays($maTinhs, $khuVuc, $ngayChon, $timeFormat, $startDate, $endDate);
            $kpi = $kpiData['kpi_ngay'] * $distinctDays;

            $totalSanLuong = $this->getTotalSanLuong($maTinhs, $khuVuc, $ngayChon, $hopDongId, $userId, $linhVuc, $timeFormat, $startDate, $endDate);

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
                // TODO: select kpi_quy_
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

    private function getDistinctDays($maTinhs, $khuVuc, $ngayChon, $timeFormat, $startDate, $endDate)
    {
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
    private function getTotalSanLuong($maTinhs, $khuVuc, $ngayChon, $hopDongId = null, $userId = null, $linhVuc = null, $timeFormat, $startDate, $endDate, $whereClauseSanLuong = null, $whereClauseThaoLap = null, $whereClauseKiemDinh = null)
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
                        ->select('SanLuong_Ngay_01', 'SanLuong_Ngay_02', 'SanLuong_Ngay_03', 'SanLuong_Ngay_04', 'SanLuong_Ngay_05', 'SanLuong_Ngay_06', 'SanLuong_Ngay_07', 'SanLuong_Ngay_08', 'SanLuong_Ngay_09', 'SanLuong_Ngay_10', 'SanLuong_Ngay_11', 'SanLuong_Ngay_12', 'SanLuong_Ngay_13', 'SanLuong_Ngay_14', 'SanLuong_Ngay_15', 'SanLuong_Ngay_16', 'SanLuong_Ngay_17', 'SanLuong_Ngay_18', 'SanLuong_Ngay_19', 'SanLuong_Ngay_20', 'SanLuong_Ngay_21', 'SanLuong_Ngay_22', 'SanLuong_Ngay_23', 'SanLuong_Ngay_24', 'SanLuong_Ngay_25', 'SanLuong_Ngay_26', 'SanLuong_Ngay_27', 'SanLuong_Ngay_28', 'SanLuong_Ngay_29', 'SanLuong_Ngay_30', 'SanLuong_Ngay_31')
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
        $sanluongQuery = DB::table('tbl_sanluong')
            ->select('SanLuong_Gia')
            ->whereRaw($whereClauseSanLuong)
            ->where('ten_hinh_anh_da_xong', '!=', '')
            ->groupBy('tbl_sanluong.SanLuong_Tram', DB::raw("DATE_FORMAT(STR_TO_DATE(tbl_sanluong.SanLuong_Ngay, '%d%m%Y'), '%d/%m/%Y')"), 'tbl_sanluong.SanLuong_TenHangMuc', 'SanLuong_Gia')
            ->where(function ($query) use ($maTinhs) {
                foreach ($maTinhs as $maTinh) {
                    $query->orWhere('SanLuong_Tram', 'LIKE', "$maTinh%");
                }
            });
        if (!empty($hopDongId)) {
            $sanluongQuery->where('HopDong_Id', $hopDongId);
        }
        if (!empty($linhVuc) && $linhVuc != "EC") {
            $sanluongData = collect();
        } else {
            $sanluongData = $sanluongQuery->get();
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

        $thaolapQuery = DB::table('tbl_sanluong_thaolap')
            ->select(DB::raw("
            ThaoLap_Anten * DonGia_Anten +
            ThaoLap_RRU * DonGia_RRU +
            ThaoLap_TuThietBi * DonGia_TuThietBi +
            ThaoLap_CapNguon * DonGia_CapNguon as SanLuong_Gia
        "))
            ->whereRaw($whereClauseThaoLap)
            ->where(function ($query) use ($maTinhs) {
                foreach ($maTinhs as $maTinh) {
                    $query->orWhere('ThaoLap_MaTram', 'LIKE', "$maTinh%");
                }
            });
        if (!empty($hopDongId)) {
            $thaolapQuery->where('HopDong_Id', $hopDongId);
        }
        if (!empty($linhVuc) && $linhVuc != "EC") {
            $sanluongThaolapData = collect();
        } else {
            $sanluongThaolapData = $thaolapQuery->get();
        }

        $kiemdinhQuery = DB::table('tbl_sanluong_kiemdinh')
            ->select(DB::raw("KiemDinh_DonGia as SanLuong_Gia"))
            ->whereRaw($whereClauseKiemDinh)
            ->where(function ($query) use ($maTinhs) {
                foreach ($maTinhs as $maTinh) {
                    $query->orWhere('KiemDinh_MaTram', 'LIKE', "$maTinh%");
                }
            });
        if (!empty($hopDongId)) {
            $kiemdinhQuery->where('HopDong_Id', $hopDongId);
        }
        if (!empty($linhVuc) && $linhVuc != "EC") {
            $sanluongKiemdinhData = collect();
        } else {
            $sanluongKiemdinhData = $kiemdinhQuery->get();
        }

        $combinedData = $sanluongData->merge($sanluongKhacData)->merge($sanluongThaolapData)->merge($sanluongKiemdinhData);
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

        $khuVucs = DB::table('tbl_tinh')->distinct()->orderBy('ten_khu_vuc')->pluck('ten_khu_vuc');

        $results = [];

        foreach ($khuVucs as $khuVuc) {
            if ($role != 3 && $khuVuc != $userKhuVuc) {
                continue;
            }

            $maTinhs = DB::table('tbl_tinh')->where('ten_khu_vuc', $khuVuc)->pluck('ma_tinh')->toArray();

            // Khởi tạo mảng lưu kết quả chi tiết
            $detailedResults = [];
            $periods = [];

            switch ($timeFormat) {
                case 'tuan':
                    $days = $this->getDaysInRange($startDate, $endDate);
                    $periods = array_map(fn ($day) => [
                        'start' => $day['date'],
                        'end' => $day['date'],
                        'label' => $day['label']
                    ], $days);
                    break;
                case 'thang':
                case 'quy':
                    $weeks = $this->getWeeksInRange($startDate, $endDate);
                    $periods = array_map(fn ($week, $index) => [
                        'start' => $week['start'],
                        'end' => $week['end'],
                        'label' => "Tuần " . ($index + 1)
                    ], $weeks, array_keys($weeks));
                    break;
                case 'nam':
                    $months = $this->getMonthsInRange($startDate, $endDate);
                    $periods = array_map(fn ($month) => [
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
                $totalSanLuong = $this->getTotalSanLuong($maTinhs, $khuVuc, null, $hopDongId, $userId, $linhVuc, $timeFormat, $period['start'], $period['end'], $whereClauseSanLuong, $whereClauseThaoLap, $whereClauseKiemDinh);
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
    //TODO: Lọc hợp đồng, đối tác, lĩnh vực???
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

                    for ($month = $startMonthLoop; $month <= $endMonthLoop; $month++) {
                        $formattedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);

                        $query = DB::table('tbl_tonghop_sanluong')
                            ->select('SanLuong_Ngay_01', 'SanLuong_Ngay_02', 'SanLuong_Ngay_03', 'SanLuong_Ngay_04', 'SanLuong_Ngay_05', 'SanLuong_Ngay_06', 'SanLuong_Ngay_07', 'SanLuong_Ngay_08', 'SanLuong_Ngay_09', 'SanLuong_Ngay_10', 'SanLuong_Ngay_11', 'SanLuong_Ngay_12', 'SanLuong_Ngay_13', 'SanLuong_Ngay_14', 'SanLuong_Ngay_15', 'SanLuong_Ngay_16', 'SanLuong_Ngay_17', 'SanLuong_Ngay_18', 'SanLuong_Ngay_19', 'SanLuong_Ngay_20', 'SanLuong_Ngay_21', 'SanLuong_Ngay_22', 'SanLuong_Ngay_23', 'SanLuong_Ngay_24', 'SanLuong_Ngay_25', 'SanLuong_Ngay_26', 'SanLuong_Ngay_27', 'SanLuong_Ngay_28', 'SanLuong_Ngay_29', 'SanLuong_Ngay_30', 'SanLuong_Ngay_31')
                            ->where('ma_tinh', $maTinh)
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
                                    $totals[] += floatval($data->$dayColumn);
                                }
                            }
                        }
                    }
                }
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
                FROM tbl_sanluong
                WHERE SanLuong_Tram LIKE '$maTinh%'
                AND ten_hinh_anh_da_xong NOT LIKE ''
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
                UNION ALL
                SELECT
                    KiemDinh_DonGia as SanLuong_Gia,
                    KiemDinh_Ngay as SanLuong_Ngay
                FROM tbl_sanluong_kiemdinh
                WHERE KiemDinh_MaTram LIKE ? AND YEAR(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')) = ?
            ) as subquery
        ";
            //TODO: Sai phần tbl_sanluong_kiemdinh
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
    public function thongKeTram(Request $request)
    {
        $maTinh = $request->ma_tinh;
        $ngayChon = $request->input('ngay');
        if (is_null($ngayChon) || $ngayChon === '') {
            $ngayChon = date('Y-m-d');
        }
        $role = session('role');
        $userId = session('userid');
        $userKhuVuc = null;
        if ($role != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', $userId)
                ->value('user_khuvuc');
            $tinhKhuVuc = DB::table('tbl_tinh')
                ->where('ma_tinh', $maTinh)
                ->value('ten_khu_vuc');
            if ($tinhKhuVuc != $userKhuVuc) {
                return response()->json([]); // Trả về dữ liệu trống nếu không khớp
            }
        }

        $query = "
        SELECT
            SanLuong_Tram,
            ROUND(SUM(CASE WHEN DATE(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = DATE(?) THEN SanLuong_Gia ELSE 0 END)) as ngay,
            ROUND(SUM(CASE WHEN WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), 1) = WEEK(?, 1) THEN SanLuong_Gia ELSE 0 END)) as tuan,
            ROUND(SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = MONTH(?) THEN SanLuong_Gia ELSE 0 END)) as thang,
            ROUND(SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = QUARTER(?) THEN SanLuong_Gia ELSE 0 END)) as quy,
            ROUND(SUM(CASE WHEN YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = YEAR(?) THEN SanLuong_Gia ELSE 0 END)) as nam
        FROM tbl_sanluong
        WHERE SanLuong_Tram LIKE ?
        AND ten_hinh_anh_da_xong NOT LIKE ''
        GROUP BY SanLuong_Tram
        UNION ALL
        SELECT
            ThaoLap_MaTram as SanLuong_Tram,
            ROUND(SUM(CASE WHEN DATE(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = DATE(?) THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END)) as ngay,
            ROUND(SUM(CASE WHEN WEEK(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), 1) = WEEK(?, 1) THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END)) as tuan,
            ROUND(SUM(CASE WHEN MONTH(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = MONTH(?) THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END)) as thang,
            ROUND(SUM(CASE WHEN QUARTER(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = QUARTER(?) THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END)) as quy,
            ROUND(SUM(CASE WHEN YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = YEAR(?) THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END)) as nam
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
        $role = session('role');
        $userId = session('userid');
        $userKhuVuc = null;
        if ($role != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', $userId)
                ->value('user_khuvuc');
            $tinhKhuVuc = DB::table('tbl_tinh')
                ->where('ma_tinh', $maTinh)
                ->value('ten_khu_vuc');
            if ($tinhKhuVuc != $userKhuVuc) {
                return response()->json([]); // Trả về dữ liệu trống nếu không khớp
            }
        }

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

        return response()->json($results);
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
            ->pluck('ten_khu_vuc');

        $results = [];

        foreach ($khuVucs as $khuVuc) {
            if ($userRole != 3 && $khuVuc != $userKhuVuc) {
                continue;
            }

            $maTinhs = DB::table('tbl_tinh')
                ->where('ten_khu_vuc', $khuVuc)
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
                    'ten_linh_vuc' => $row->KiemDinh_NoiDung,
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

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateInterval, DateTime, DatePeriod;

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
        return view('thong_ke.thongke_tongquat', compact('hopDongs', 'khuVucs', 'doiTacs'));
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

    //TODO: phân theo khu vực, thống kê đang sai???
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
        $currentMonth = intval(date('m', strtotime($ngayChon)));
        $currentYear = date('Y', strtotime($ngayChon));
        $totalMonth = 0;
        $totalYear = 0;
        $kpiMonth = 0;
        $kpiYear = 0;
        $details=[];

        foreach ($khuVucs as $khuVuc) {
            if ($role != 3 && $khuVuc != $userKhuVuc) {
                continue;
            }
            $maTinhs = DB::table('tbl_tinh')
                ->where('ten_khu_vuc', $khuVuc)
                ->pluck('ma_tinh')
                ->toArray();

            $kpiData = $this->getKpiNgay($khuVuc, $currentYear, $currentMonth);
            $kpi_ngay = $kpiData['kpi_ngay'];
            $daysInMonth = $this->getDistinctDays($maTinhs, $khuVuc, $ngayChon, 'thang', null, null);
            $daysInYear = $this->getDistinctDays($maTinhs, $khuVuc, $ngayChon, 'nam', null, null);
            $kpi_thang = $kpi_ngay * $daysInMonth;
            $kpi_nam = $kpi_ngay * $daysInYear;
            $totalThang = $this->getTotalSanLuong($maTinhs, $khuVuc, $ngayChon, null, null, 'thang', null, null);
            $totalNam = $this->getTotalSanLuong($maTinhs, $khuVuc, $ngayChon, null, null, 'nam', null, null);

            // Cộng dồn tổng tháng, tổng năm, KPI tháng, KPI năm
            $totalMonth += $totalThang;
            $totalYear += $totalNam;
            $kpiMonth += $kpi_thang;
            $kpiYear += $kpi_nam; 

            $daysDetail = $this->getDistinctDays($maTinhs, $khuVuc, $ngayChon, $timeFormat, $startDate, $endDate);
            $kpiDetail = $kpi_ngay * $daysDetail;
            $totalDetail = $this->getTotalSanLuong($maTinhs, $khuVuc, $ngayChon, null, null, $timeFormat, $startDate, $endDate);
            $details[] = [
                'khuVuc' => $khuVuc,
                'total' => round($totalDetail),
                'kpi' => ($kpiDetail > 0) ? round($totalDetail / 1e7 / $kpiDetail, 2) : 0
            ];
    
        }

        $results = [
            'totalMonth' => round($totalMonth), // Tổng tháng
            'totalYear' => round($totalYear), // Tổng năm
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
        $userId = $request->input('user'); // Lấy user từ request
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

            $kpiData = $this->getKpiNgay($khuVuc, $currentYear, $currentMonth);

            $distinctDays = $this->getDistinctDays($maTinhs, $khuVuc, $ngayChon, $timeFormat, $startDate, $endDate);
            $kpi = $kpiData['kpi_ngay'] * $distinctDays;
            
            $totalSanLuong = $this->getTotalSanLuong($maTinhs, $khuVuc, $ngayChon, $hopDongId, $userId, $timeFormat, $startDate, $endDate);

            $results[] = [
                'ten_khu_vuc' => $khuVuc,
                'total' => round($totalSanLuong / 1e9, 2),
                'kpi' => round($kpi, 2),
            ];
        }

        return response()->json($results);
    }

    //TODO: KPI năm luôn đổi??? do kpi_thang, cần fix funct này
    private function getKpiNgay($khuVuc, $currentYear, $currentMonth)
    {
        $kpiSelect = DB::table('tbl_kpi_quy')
            ->where('ten_khu_vuc', $khuVuc)
            ->where('year', $currentYear)
            ->where('noi_dung', 'Tổng sản lượng')
            ->first();

        if ($kpiSelect) {
            $kpi_nam = $kpiSelect->kpi_nam;
            $monthlyKpiField = 'kpi_thang_' . $currentMonth;
            $kpi_thang = $kpiSelect->$monthlyKpiField;
            $kpi_ngay = $kpi_thang / 30;
        } else {
            $kpi_nam = 0;
            $kpi_ngay = 0;
        }
        return [
            'kpi_nam' => $kpi_nam,
            'kpi_ngay' => $kpi_ngay,
        ];
    }

    //TODO: tao funct getWhereClause
    private function getDistinctDays($maTinhs, $khuVuc, $ngayChon, $timeFormat, $startDate, $endDate)
    {
        $currentMonth = intval(date('m', strtotime($ngayChon)));
        $currentYear = date('Y', strtotime($ngayChon));
        $currentQuarter = ceil($currentMonth / 3);
        
        switch ($timeFormat) {
            case 'ngay':
                $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') = DATE('$ngayChon')";
                $whereClauseThaoLap = "STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') = DATE('$ngayChon')";
                break;
            case 'tuan':
                $weekNumber = date('W', strtotime($ngayChon));
                $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), 1) = $weekNumber";
                $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear AND WEEK(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), 1) = $weekNumber";
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
        }
        if (!empty($startDate) && !empty($endDate)) {
            $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') BETWEEN DATE('$startDate') AND DATE('$endDate')";
            $whereClauseThaoLap = "STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') BETWEEN DATE('$startDate') AND DATE('$endDate')";
        }

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
            );

        $distinctDays = DB::table(DB::raw("({$distinctQuery->toSql()}) as subquery"))
            ->mergeBindings($distinctQuery)
            ->distinct()
            ->count('distinct_date');

        return $distinctDays;
    }
    private function getTotalSanLuong($maTinhs, $khuVuc, $ngayChon, $hopDongId=null, $userId=null, $timeFormat, $startDate, $endDate)
    {
        $currentMonth = intval(date('m', strtotime($ngayChon)));
        $currentYear = date('Y', strtotime($ngayChon));
        $currentQuarter = ceil($currentMonth / 3);

        $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear";
        $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear";

        switch ($timeFormat) {
            case 'ngay':
                $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') = DATE('$ngayChon')";
                $whereClauseThaoLap = "STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') = DATE('$ngayChon')";
                break;
            case 'tuan':
                $weekNumber = date('W', strtotime($ngayChon));
                $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), 1) = $weekNumber";
                $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear AND WEEK(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), 1) = $weekNumber";
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
        }
        if (!empty($startDate) && !empty($endDate)) {
            $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') BETWEEN DATE('$startDate') AND DATE('$endDate')";
            $whereClauseThaoLap = "STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') BETWEEN DATE('$startDate') AND DATE('$endDate')";
        }

        $sanluongQuery = DB::table('tbl_sanluong')
            ->select('SanLuong_Gia')
            ->whereRaw($whereClauseSanLuong)
            ->where('ten_hinh_anh_da_xong', '!=', '')
            ->where(function ($query) use ($maTinhs) {
                foreach ($maTinhs as $maTinh) {
                    $query->orWhere('SanLuong_Tram', 'LIKE', "$maTinh%");
                }
            });
        if (!empty($hopDongId)) {
            $sanluongQuery->where('HopDong_Id', $hopDongId);
        }
        $sanluongData = $sanluongQuery->get();
        
        $sanluongKhacData = null;
        if (empty($hopDongId)) {
            $sanluongKhacQuery = DB::table('tbl_sanluong_khac')
                ->select('SanLuong_Gia')
                ->whereRaw($whereClauseSanLuong)
                ->where('SanLuong_KhuVuc', $khuVuc);
            if (!empty($userId)) {
                $sanluongKhacQuery->where('user_id', $userId);
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
        $sanluongThaolapData = $thaolapQuery->get();

        $combinedData = $sanluongData->merge($sanluongKhacData)->merge($sanluongThaolapData);
        $combinedData = $combinedData->map(function ($item) {
            $item->SanLuong_Gia = floatval($item->SanLuong_Gia);
            return $item;
        });
        $total = $combinedData->sum('SanLuong_Gia');

        return $total;
    }

    //TODO: tăng tốc độ lấy data, lọc đối tác, start_date, end_date
    public function thongKeXuTheKhuVuc(Request $request)
{
    // Lấy tham số từ request
    $timeFormat = $request->input('time_format');
    // $ngayChon = $request->input('ngay_chon', date('Y-m-d'));
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');
    $hopDongId = $request->input('hop_dong');
    $userId = $request->input('user');
    $role = session('role');
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

    $results = [];
    // $ngayChonDate = new DateTime($ngayChon);
    // $ngayChonDate->setTime(0, 0);
    // $currentYear = $ngayChonDate->format('Y');
    // $currentMonth = $ngayChonDate->format('n');
    // $currentQuarter = ceil($currentMonth / 3);

    foreach ($khuVucs as $khuVuc) {
        if ($role != 3 && $khuVuc != $userKhuVuc) {
            continue;
        }

        $maTinhs = DB::table('tbl_tinh')
            ->where('ten_khu_vuc', $khuVuc)
            ->pluck('ma_tinh')
            ->toArray();

        // Khởi tạo mảng lưu kết quả chi tiết
        $detailedResults = [];

        switch ($timeFormat) {
            case 'tuan':
                $days = $this->getDaysInRange($startDate, $endDate);
                foreach ($days as $day) {
                    $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') = '{$day}'";
                    $whereClauseThaoLap = "STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') = '{$day}'";
                    $result = $this->getSanLuongData($khuVuc, $maTinhs, $whereClauseSanLuong, $whereClauseThaoLap, $hopDongId, $userId);
                    $result['time_period'] = date('d-m-Y', strtotime($day));;
                    $detailedResults[] = $result;
                }
                break;
            case 'thang':
            case 'quy':
                $weeks = $this->getWeeksInRange($startDate, $endDate);
                $weekNumber = 1;
                foreach ($weeks as $week) {
                    $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') BETWEEN '{$week['start']}' AND '{$week['end']}'";
                    $whereClauseThaoLap = "STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') BETWEEN '{$week['start']}' AND '{$week['end']}'";
                    $result = $this->getSanLuongData($khuVuc, $maTinhs, $whereClauseSanLuong, $whereClauseThaoLap, $hopDongId, $userId);
                    $result['time_period'] = "Tuần $weekNumber";
                    $detailedResults[] = $result;
                    $weekNumber++;
                }
                break;


                case 'nam':
                    $startYear = (new DateTime($startDate))->format('Y');
                    $endYear = (new DateTime($endDate))->format('Y');
                    $startMonth = (new DateTime($startDate))->format('n');
                    $endMonth = (new DateTime($endDate))->format('n');
                    for ($year = $startYear; $year <= $endYear; $year++) {
                        $start = $year == $startYear ? $startMonth : 1;
                        $end = $year == $endYear ? $endMonth : 12;
                        for ($month = $start; $month <= $end; $month++) {
                            $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $year AND MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $month";
                            $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $year AND MONTH(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $month";
                            $result = $this->getSanLuongData($khuVuc, $maTinhs, $whereClauseSanLuong, $whereClauseThaoLap, $hopDongId, $userId);
                            $result['time_period'] = "Tháng $month";
                            $detailedResults[] = $result;
                        }
                    }
                    break;

            default:
                return response()->json(['error' => 'Thời gian không hợp lệ']);
        }

        // Thêm kết quả vào mảng
        $results[] = [
            'ten_khu_vuc' => $khuVuc,
            'details' => $detailedResults
        ];
    }

    return response()->json($results);
}

// Hàm phụ để lấy dữ liệu sản lượng
private function getSanLuongData($khuVuc, $maTinhs, $whereClauseSanLuong, $whereClauseThaoLap, $hopDongId, $userId)
{
    // Lấy dữ liệu từ bảng tbl_sanluong
    $sanluongQuery = DB::table('tbl_sanluong')
        ->select('SanLuong_Tram', 'SanLuong_Ngay', 'SanLuong_Gia')
        ->whereRaw($whereClauseSanLuong)
        ->where('ten_hinh_anh_da_xong', '!=', '')
        ->where(function ($query) use ($maTinhs) {
            foreach ($maTinhs as $maTinh) {
                $query->orWhere('SanLuong_Tram', 'LIKE', "$maTinh%");
            }
        });
    if (!empty($hopDongId)) {
        $sanluongQuery->where('HopDong_Id', $hopDongId);
    }
    $sanluongData = $sanluongQuery->get();

    // Lấy dữ liệu từ bảng tbl_sanluong_khac
    $sanluongKhacData = null;
    if (empty($hopDongId)) {
        $sanluongKhacQuery = DB::table('tbl_sanluong_khac')
            ->select('SanLuong_Tram', 'SanLuong_Ngay', 'SanLuong_Gia')
            ->whereRaw($whereClauseSanLuong)
            ->where('SanLuong_KhuVuc', $khuVuc);
        if (!empty($userId)) {
            $sanluongKhacQuery->where('user_id', $userId);
        }
        $sanluongKhacData = $sanluongKhacQuery->get();
    }

    // Lấy dữ liệu từ bảng tbl_sanluong_thaolap
    $thaolapQuery = DB::table('tbl_sanluong_thaolap')
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
        ->where(function ($query) use ($maTinhs) {
            foreach ($maTinhs as $maTinh) {
                $query->orWhere('ThaoLap_MaTram', 'LIKE', "$maTinh%");
            }
        });
    if (!empty($hopDongId)) {
        $thaolapQuery->where('HopDong_Id', $hopDongId);
    }
    $thaolapData = $thaolapQuery->get();

    // Tính tổng giá trị sản lượng
    $filteredThaolapData = $thaolapData->filter(function ($item) {
        return $item->SanLuong_Gia !== '' && $item->SanLuong_Gia !== null;
    });
    $totalSanLuong = $sanluongData->filter(function ($item) {
        return $item->SanLuong_Gia !== '' && $item->SanLuong_Gia !== null;
    })->sum('SanLuong_Gia');
    if ($sanluongKhacData) {
        $totalSanLuong += $sanluongKhacData->filter(function ($item) {
            return $item->SanLuong_Gia !== '' && $item->SanLuong_Gia !== null;
        })->sum('SanLuong_Gia');
    }
    $totalSanLuong += $filteredThaolapData->sum('SanLuong_Gia');

    return [
        'total' => round($totalSanLuong / 1e9, 2) // Đổi sang đơn vị triệu VNĐ
    ];
}

// Hàm phụ để lấy danh sách các ngày, tuần trong khoảng thời gian
private function getDaysInRange($startDate, $endDate)
{
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $end->setTime(23, 59, 59); // Đặt thời gian kết thúc là cuối ngày

    $interval = DateInterval::createFromDateString('1 day');
    $period = new DatePeriod($start, $interval, $end);

    $days = [];
    foreach ($period as $dt) {
        $days[] = $dt->format('Y-m-d');
    }

    return $days;
}
private function getWeeksInRange($start, $end)
{
    $startDate = new DateTime($start);
    $endDate = new DateTime($end);
    $weeks = [];
    while ($startDate <= $endDate) {
        $endOfWeek = (clone $startDate)->modify('Sunday');
        if ($endOfWeek > $endDate) {
            $endOfWeek = $endDate;
        }
        $weeks[] = [
            'start' => $startDate->format('Y-m-d'),
            'end' => $endOfWeek->format('Y-m-d'),
        ];
        $startDate->modify('next Monday');
    }
    return $weeks;
}


    //TODO: start_date, end_date
    public function thongKeTinh(Request $request)
    {
        // $khuVuc = $request->input('khu_vuc');
        $ngayChon = $request->input('ngay_chon', date('Y-m-d'));
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

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
        $khuVucTotals = (object) [
            'ngay' => 0,
            'tuan' => 0,
            'thang' => 0,
            'quy' => 0,
            'nam' => 0
        ];
        $results = [];
        foreach ($maTinhs as $maTinh) {
            if (!empty($startDate) && !empty($endDate)) {
                $query = "
                SELECT
                    SUM(CASE WHEN DATE(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) BETWEEN DATE('$startDate') AND DATE('$endDate') THEN SanLuong_Gia ELSE 0 END) as ngay
                FROM tbl_sanluong
                WHERE SanLuong_Tram LIKE '$maTinh%'
                AND ten_hinh_anh_da_xong NOT LIKE ''
                UNION ALL
                SELECT
                    SUM(CASE WHEN DATE(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) BETWEEN DATE('$startDate') AND DATE('$endDate') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as ngay
                FROM tbl_sanluong_thaolap
                WHERE ThaoLap_MaTram LIKE '$maTinh%'
            ";

            } else {
                $query = "
                SELECT
                SUM(CASE WHEN DATE(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = DATE('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as ngay,
                SUM(CASE WHEN WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), 1) = WEEK('$ngayChon', 1) THEN SanLuong_Gia ELSE 0 END) as tuan,
                SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = MONTH('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as thang,
                SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = QUARTER('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as quy,
                SUM(CASE WHEN YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = YEAR('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as nam
                FROM tbl_sanluong
                WHERE SanLuong_Tram LIKE '$maTinh%'
                AND ten_hinh_anh_da_xong NOT LIKE ''
                UNION ALL
                SELECT
                SUM(CASE WHEN DATE(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = DATE('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as ngay,
                SUM(CASE WHEN WEEK(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), 1) = WEEK('$ngayChon', 1) THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as tuan,
                SUM(CASE WHEN MONTH(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = MONTH('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as thang,
                SUM(CASE WHEN QUARTER(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = QUARTER('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as quy,
                SUM(CASE WHEN YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = YEAR('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as nam
                FROM tbl_sanluong_thaolap
                WHERE ThaoLap_MaTram LIKE '$maTinh%'
                ";
            }

            $totals = DB::select($query);

            $combinedTotals = (object) [
                'ngay' => 0,
                'tuan' => 0,
                'thang' => 0,
                'quy' => 0,
                'nam' => 0
            ];

            foreach ($totals as $total) {
                if (!empty($startDate) && !empty($endDate)) {
                    $combinedTotals->ngay += $total->ngay;
                    $combinedTotals->tuan += $total->ngay;
                    $combinedTotals->thang += $total->ngay;
                    $combinedTotals->quy += $total->ngay;
                    $combinedTotals->nam += $total->ngay;
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
            $khuVucTotals->ngay += $combinedTotals->ngay;
            $khuVucTotals->tuan += $combinedTotals->tuan;
            $khuVucTotals->thang += $combinedTotals->thang;
            $khuVucTotals->quy += $combinedTotals->quy;
            $khuVucTotals->nam += $combinedTotals->nam;
        }
        $resultsTotal[] = [
            'khu_vuc' => $khuVuc,
            'totals' => [
                'ngay' => round($khuVucTotals->ngay),
                'tuan' => round($khuVucTotals->tuan),
                'thang' => round($khuVucTotals->thang),
                'quy' => round($khuVucTotals->quy),
                'nam' => round($khuVucTotals->nam)
            ]
        ];

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
                break;
            case 'tuan':
                $whereClauseSanLuong = "WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), 1) = WEEK(CURRENT_DATE(), 1)";
                $whereClauseThaoLap = "WEEK(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), 1) = WEEK(CURRENT_DATE(), 1)";
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
    public function updateTableTongHopSanLuong (Request $request){
    // $years = [2020, date('Y')];
    // $months = range(1, 12);
    $ngayChon = $request->input('ngay_chon', date('Y-m-d'));
    $year = date('Y', strtotime($ngayChon));
    $month = date('m', strtotime($ngayChon));
    $day = date('d', strtotime($ngayChon));

    // foreach ($years as $year) {
    //     foreach ($months as $month) {
            $formattedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
            $sanluongData = DB::table('tbl_sanluong')
            ->select(
                DB::raw("UPPER(LEFT(SanLuong_Tram, 3)) as ma_tinh"),
                DB::raw("DATE_FORMAT(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), '%d') as day"),
                DB::raw("SUM(SanLuong_Gia) as total_sanluong"),
                'tbl_tinh.ten_khu_vuc as khu_vuc'
            )
            ->leftJoin('tbl_tinh', DB::raw("UPPER(LEFT(tbl_sanluong.SanLuong_Tram, 3))"), '=', 'tbl_tinh.ma_tinh')
            // ->whereYear(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), $year)
            // ->whereMonth(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), $month)
            ->where('ten_hinh_anh_da_xong', '<>', '')
            ->whereDate(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), '=', $ngayChon)
            ->groupBy(DB::raw("UPPER(LEFT(SanLuong_Tram, 3))"), DB::raw("DATE_FORMAT(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), '%d')"), 'tbl_tinh.ten_khu_vuc')
            ->get();

            $thaolapData = DB::table('tbl_sanluong_thaolap')
                ->select(
                    DB::raw("UPPER(LEFT(ThaoLap_MaTram, 3)) as ma_tinh"),
                    DB::raw("DATE_FORMAT(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), '%d') as day"),
                    DB::raw("SUM(ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon) as total_sanluong"),
                    'tbl_tinh.ten_khu_vuc as khu_vuc'
                )
                ->leftJoin('tbl_tinh', DB::raw("UPPER(LEFT(ThaoLap_MaTram, 3))"), '=', 'tbl_tinh.ma_tinh')
                // ->whereYear(DB::raw("STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')"), $year)
                // ->whereMonth(DB::raw("STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')"), $month)
                ->whereDate(DB::raw("STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')"), '=', $ngayChon)
                ->groupBy(DB::raw("UPPER(LEFT(ThaoLap_MaTram, 3))"), 'day', 'tbl_tinh.ten_khu_vuc')
                ->get();

                $sanluongKhacData = DB::table('tbl_sanluong_khac')
                ->select(
                    DB::raw("UPPER(LEFT(SanLuong_Tram, 3)) as ma_tinh"),
                    DB::raw("DATE_FORMAT(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), '%d') as day"),
                    'SanLuong_TenHangMuc as linh_vuc',
                    DB::raw("SUM(SanLuong_Gia) as total_sanluong"),
                    'SanLuong_KhuVuc as khu_vuc'
                )
                // ->whereYear(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), $year)
                // ->whereMonth(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), $month)
                ->whereDate(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), '=', $ngayChon)
                ->groupBy('ma_tinh', 'day', 'SanLuong_TenHangMuc', 'SanLuong_KhuVuc')
                ->get();

                $combinedData = [];
            foreach ($sanluongData as $data) {
                $key = "{$data->ma_tinh}-EC-{$year}-{$formattedMonth}";
                if (!isset($combinedData[$key])) {
                    $combinedData[$key] = [
                        'khu_vuc' => $data->khu_vuc ?? '',
                        'linh_vuc' => 'EC',
                        'ma_tinh' => $data->ma_tinh ?? '',
                        'year' => $year,
                        'month' => $formattedMonth,
                        'SanLuong_Ngay_01' => 0, 'SanLuong_Ngay_02' => 0, 'SanLuong_Ngay_03' => 0,
                        'SanLuong_Ngay_04' => 0, 'SanLuong_Ngay_05' => 0, 'SanLuong_Ngay_06' => 0,
                        'SanLuong_Ngay_07' => 0, 'SanLuong_Ngay_08' => 0, 'SanLuong_Ngay_09' => 0,
                        'SanLuong_Ngay_10' => 0, 'SanLuong_Ngay_11' => 0, 'SanLuong_Ngay_12' => 0,
                        'SanLuong_Ngay_13' => 0, 'SanLuong_Ngay_14' => 0, 'SanLuong_Ngay_15' => 0,
                        'SanLuong_Ngay_16' => 0, 'SanLuong_Ngay_17' => 0, 'SanLuong_Ngay_18' => 0,
                        'SanLuong_Ngay_19' => 0, 'SanLuong_Ngay_20' => 0, 'SanLuong_Ngay_21' => 0,
                        'SanLuong_Ngay_22' => 0, 'SanLuong_Ngay_23' => 0, 'SanLuong_Ngay_24' => 0,
                        'SanLuong_Ngay_25' => 0, 'SanLuong_Ngay_26' => 0, 'SanLuong_Ngay_27' => 0,
                        'SanLuong_Ngay_28' => 0, 'SanLuong_Ngay_29' => 0, 'SanLuong_Ngay_30' => 0,
                        'SanLuong_Ngay_31' => 0,
                    ];
                }
                $combinedData[$key]["SanLuong_Ngay_{$data->day}"] += $data->total_sanluong;
            }

            foreach ($thaolapData as $data) {
                $key = "{$data->ma_tinh}-EC-{$year}-{$formattedMonth}";
                if (!isset($combinedData[$key])) {
                    $combinedData[$key] = [
                        'khu_vuc' => $data->khu_vuc ?? '',
                        'linh_vuc' => 'EC',
                        'ma_tinh' => $data->ma_tinh ?? '',
                        'year' => $year,
                        'month' => $formattedMonth,
                        'SanLuong_Ngay_01' => 0, 'SanLuong_Ngay_02' => 0, 'SanLuong_Ngay_03' => 0,
                        'SanLuong_Ngay_04' => 0, 'SanLuong_Ngay_05' => 0, 'SanLuong_Ngay_06' => 0,
                        'SanLuong_Ngay_07' => 0, 'SanLuong_Ngay_08' => 0, 'SanLuong_Ngay_09' => 0,
                        'SanLuong_Ngay_10' => 0, 'SanLuong_Ngay_11' => 0, 'SanLuong_Ngay_12' => 0,
                        'SanLuong_Ngay_13' => 0, 'SanLuong_Ngay_14' => 0, 'SanLuong_Ngay_15' => 0,
                        'SanLuong_Ngay_16' => 0, 'SanLuong_Ngay_17' => 0, 'SanLuong_Ngay_18' => 0,
                        'SanLuong_Ngay_19' => 0, 'SanLuong_Ngay_20' => 0, 'SanLuong_Ngay_21' => 0,
                        'SanLuong_Ngay_22' => 0, 'SanLuong_Ngay_23' => 0, 'SanLuong_Ngay_24' => 0,
                        'SanLuong_Ngay_25' => 0, 'SanLuong_Ngay_26' => 0, 'SanLuong_Ngay_27' => 0,
                        'SanLuong_Ngay_28' => 0, 'SanLuong_Ngay_29' => 0, 'SanLuong_Ngay_30' => 0,
                        'SanLuong_Ngay_31' => 0,
                    ];
                }
                $combinedData[$key]["SanLuong_Ngay_{$data->day}"] += $data->total_sanluong;
            }

            foreach ($sanluongKhacData as $data) {
                $key = "{$data->ma_tinh}-{$data->linh_vuc}-{$year}-{$formattedMonth}";
                if (!isset($combinedData[$key])) {
                    $combinedData[$key] = [
                        'khu_vuc' => $data->khu_vuc ?? '',
                        'linh_vuc' => $data->linh_vuc,
                        'ma_tinh' => $data->ma_tinh ?? '',
                        'year' => $year,
                        'month' => $formattedMonth,
                        'SanLuong_Ngay_01' => 0, 'SanLuong_Ngay_02' => 0, 'SanLuong_Ngay_03' => 0,
                        'SanLuong_Ngay_04' => 0, 'SanLuong_Ngay_05' => 0, 'SanLuong_Ngay_06' => 0,
                        'SanLuong_Ngay_07' => 0, 'SanLuong_Ngay_08' => 0, 'SanLuong_Ngay_09' => 0,
                        'SanLuong_Ngay_10' => 0, 'SanLuong_Ngay_11' => 0, 'SanLuong_Ngay_12' => 0,
                        'SanLuong_Ngay_13' => 0, 'SanLuong_Ngay_14' => 0, 'SanLuong_Ngay_15' => 0,
                        'SanLuong_Ngay_16' => 0, 'SanLuong_Ngay_17' => 0, 'SanLuong_Ngay_18' => 0,
                        'SanLuong_Ngay_19' => 0, 'SanLuong_Ngay_20' => 0, 'SanLuong_Ngay_21' => 0,
                        'SanLuong_Ngay_22' => 0, 'SanLuong_Ngay_23' => 0, 'SanLuong_Ngay_24' => 0,
                        'SanLuong_Ngay_25' => 0, 'SanLuong_Ngay_26' => 0, 'SanLuong_Ngay_27' => 0,
                        'SanLuong_Ngay_28' => 0, 'SanLuong_Ngay_29' => 0, 'SanLuong_Ngay_30' => 0,
                        'SanLuong_Ngay_31' => 0,
                    ];
                }
                $combinedData[$key]["SanLuong_Ngay_{$data->day}"] += $data->total_sanluong;
            }
            dd($combinedData);
            
            //TODO: Nếu có rồi thì update
            foreach ($combinedData as $data) {
                DB::table('tbl_tonghop_sanluong')->updateOrInsert([
                        'ma_tinh' => $data['ma_tinh'],
                        'linh_vuc' => $data['linh_vuc'],
                        'year' => $data['year'],
                        'month' => $data['month'],
                    ],
                    $data
                    );            
                }
        //     }
        // }
    }
}

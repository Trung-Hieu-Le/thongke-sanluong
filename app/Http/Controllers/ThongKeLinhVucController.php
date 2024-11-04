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
        $whereClauseKiemDinh = "";
        switch ($timeFormat) {
            case 'ngay':
                $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') = CURRENT_DATE()";
                $whereClauseKiemDinh = "STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y') = CURRENT_DATE()";
                break;
            case 'tuan':
                $whereClauseSanLuong = "WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), 1) = WEEK(CURRENT_DATE(), 1)";
                $whereClauseKiemDinh = "WEEK(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y'), 1) = WEEK(CURRENT_DATE(), 1)";
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

            // $maTinhs = DB::table('tbl_tinh')
            //     ->where('ten_khu_vuc', $khuVuc)
            //     ->whereIn('ten_khu_vuc', ['TTKV1', 'TTKV2', 'TTKV3', 'TTGPHTVT'])
            //     ->pluck('ma_tinh');

            $sanluongData = DB::table('tbl_sanluong')
                ->leftJoin('tbl_tram', function ($join) {
                    $join->on('tbl_sanluong.SanLuong_Tram', '=', 'tbl_tram.ma_tram')
                        ->on('tbl_sanluong.HopDong_Id', '=', 'tbl_tram.hopdong_id');
                })
                ->leftJoin('tbl_tinh', function ($join) {
                    $join->on(DB::raw("UPPER(LEFT(tbl_sanluong.SanLuong_Tram, 3))"), '=', 'tbl_tinh.ma_tinh');
                })
                ->join('tbl_hopdong', 'tbl_sanluong.HopDong_Id', '=', 'tbl_hopdong.HopDong_Id')
                ->select(
                    DB::raw("COALESCE(tbl_tram.khu_vuc, tbl_tinh.ten_khu_vuc) as khu_vuc"),
                    DB::raw('SUM(tbl_sanluong.SanLuong_Gia) as SanLuong_Gia')
                )
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('tbl_hinhanh')
                        ->whereColumn('tbl_hinhanh.ma_tram', 'tbl_sanluong.SanLuong_Tram');
                })
                ->whereNot('ten_hinh_anh_da_xong', "")
                ->whereRaw("COALESCE(tbl_tram.khu_vuc, tbl_tinh.ten_khu_vuc) LIKE '$khuVuc'")
                ->whereRaw($whereClauseSanLuong)
                ->groupBy('tbl_tram.khu_vuc', 'SanLuong_Tram', 'tbl_tinh.ten_khu_vuc')->get();


            $totalEC = $sanluongData->sum('SanLuong_Gia');

            $sanluongKiemdinhData = DB::table('tbl_sanluong_kiemdinh')
                ->leftJoin('tbl_tram', function ($join) {
                    $join->on('tbl_sanluong_kiemdinh.KiemDinh_MaTram', '=', 'tbl_tram.ma_tram')
                        ->on('tbl_sanluong_kiemdinh.HopDong_Id', '=', 'tbl_tram.hopdong_id');
                })
                ->leftJoin('tbl_tinh', function ($join) {
                    $join->on(DB::raw("UPPER(LEFT(tbl_sanluong_kiemdinh.KiemDinh_MaTram, 3))"), '=', 'tbl_tinh.ma_tinh');
                })
                ->join('tbl_hopdong', 'tbl_sanluong_kiemdinh.HopDong_Id', '=', 'tbl_hopdong.HopDong_Id')
                ->select(
                    DB::raw("COALESCE(tbl_tram.khu_vuc, tbl_tinh.ten_khu_vuc) as khu_vuc"),
                    'KiemDinh_NoiDung',
                    DB::raw('SUM(KiemDinh_DonGia) as SanLuong_Gia')
                )
                ->whereRaw($whereClauseKiemDinh)
                ->whereRaw("COALESCE(tbl_tram.khu_vuc, tbl_tinh.ten_khu_vuc) LIKE '$khuVuc'")
                ->groupBy('tbl_tram.khu_vuc', 'KiemDinh_NoiDung', 'tbl_tinh.ten_khu_vuc')->get();

            $sanluongKhacDataQuery = DB::table('tbl_sanluong_khac')
                ->select('SanLuong_TenHangMuc', DB::raw('SUM(SanLuong_Gia) as total'))
                ->whereRaw($whereClauseSanLuong)
                ->where('SanLuong_KhuVuc', $khuVuc)
                ->groupBy('SanLuong_TenHangMuc');
            $sanluongKhacData = $sanluongKhacDataQuery->get();

            $results[$khuVuc]['EC'] = [
                'ten_linh_vuc' => 'EC',
                'total' => round($totalEC / 1e9, 2),
                'kpi' => 0
            ];

            foreach ($sanluongKhacData as $row) {
                $results[$khuVuc][$row->SanLuong_TenHangMuc] = [
                    'ten_linh_vuc' => $row->SanLuong_TenHangMuc,
                    'total' => round($row->total / 1e9, 2),
                    'kpi' => 0
                ];
            }
            foreach ($sanluongKiemdinhData as $row) {
                $results[$khuVuc][$row->KiemDinh_NoiDung] = [
                    'ten_linh_vuc' => 'Kiểm định',
                    'total' => round($row->SanLuong_Gia / 1e9, 2),
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
                    $kpiValue = round(($kpi->{'kpi_quy_' . $currentQuarter} / 3), 2);
                    break;
                case 'quy':
                    $kpiValue = round($kpi->{'kpi_quy_' . $currentQuarter}, 2);
                    break;
                case 'nam':
                    $kpiValue = round($kpi->kpi_quy_1 + $kpi->kpi_quy_2 + $kpi->kpi_quy_3 + $kpi->kpi_quy_4, 2);
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

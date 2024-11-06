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

        $khuVucListQuery = DB::table('tbl_tram')
            ->distinct()
            // ->where('ten_khu_vuc', $userKhuVuc)
            ->select('khu_vuc')
            ->whereIn('khu_vuc', ['TTKV1', 'TTKV2', 'TTKV3', 'TTGPHTVT'])
            ->orderBy('khu_vuc');
        if ($role != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', $userId)
                ->value('user_khuvuc');
            $khuVucListQuery->where('ten_khu_vuc', $userKhuVuc);
        }
        $khuVucList = $khuVucListQuery->get()->toArray();
        return view('thong_ke.thongke_tinh', compact('khuVucList'));
    }


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

                // Tạo khoảng thời gian cho ngày
                $startDay = $endDay = $ngayChon;

                // Tạo khoảng thời gian cho tuần
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

                // Tạo khoảng thời gian cho tháng
                $startMonth = date('Y-m-01', strtotime($ngayChon));
                $endMonth = date('Y-m-t', strtotime($ngayChon));

                // Tạo khoảng thời gian cho quý
                $quarter = ceil($month / 3);
                $startQuarter = date('Y-m-d', strtotime("$year-" . (($quarter - 1) * 3 + 1) . "-01"));
                $endQuarter = date('Y-m-t', strtotime("$year-" . ($quarter * 3) . "-01"));

                // Tạo khoảng thời gian cho năm
                $startYear = "$year-01-01";
                $endYear = "$year-12-31";
                // dd($startDay, $startWeek, $startMonth, $startQuarter, $startYear,
                // $endDay, $endWeek, $endMonth, $endQuarter, $endYear);

                // Lấy dữ liệu cho các khoảng thời gian
                $totalsArr = [
                    'ngay' => array_sum($this->getTotalSanLuongWithMaTram($maTinh, $startDay, $endDay)),
                    'tuan' => array_sum($this->getTotalSanLuongWithMaTram($maTinh, $startWeek, $endWeek)),
                    'thang' => array_sum($this->getTotalSanLuongWithMaTram($maTinh, $startMonth, $endMonth)),
                    'quy' => array_sum($this->getTotalSanLuongWithMaTram($maTinh, $startQuarter, $endQuarter)),
                    'nam' => array_sum($this->getTotalSanLuongWithMaTram($maTinh, $startYear, $endYear))
                ];
            }

            $combinedTotals = (object) [
                'ngay' => 0,
                'tuan' => 0,
                'thang' => 0,
                'quy' => 0,
                'nam' => 0
            ];

            if (!empty($startDate) && !empty($endDate)) {
                // If we're using a specific date range, add only once
                foreach ($totals as $total) {
                    $combinedTotals->ngay += $total;
                    $combinedTotals->tuan += $total;
                    $combinedTotals->thang += $total;
                    $combinedTotals->quy += $total;
                    $combinedTotals->nam += $total;
                }
            } else {
                // Otherwise, accumulate totals for each specific period
                $combinedTotals->ngay += $totalsArr['ngay'];
                $combinedTotals->tuan += $totalsArr['tuan'];
                $combinedTotals->thang += $totalsArr['thang'];
                $combinedTotals->quy += $totalsArr['quy'];
                $combinedTotals->nam += $totalsArr['nam'];
            }

            // Add the rounded totals to results array
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
            $yearTotal = 0;
            $quarterTotals = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
            $monthlyTotals = [];

            // Loop through each month of the selected year
            for ($month = 1; $month <= 12; $month++) {
                // Format start and end date for the month
                $startDate = "$namChon-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
                $endDate = date('Y-m-t', strtotime($startDate));

                // Get total production for the given month
                $monthlyData = $this->getTotalSanLuongWithMaTram($maTinh, $startDate, $endDate);
                $monthlyTotal = array_sum($monthlyData);

                // Add the monthly total to the yearly and quarterly totals
                $yearTotal += $monthlyTotal;
                $quarter = ceil($month / 3); // Determine the quarter (1, 2, 3, or 4)
                $quarterTotals[$quarter] += $monthlyTotal;
                $monthlyTotals["thang_$month"] = round($monthlyTotal);
            }

            // Collect the data for each province
            $results[] = [
                'ma_tinh' => $maTinh,
                'tong_san_luong' => [
                    'nam' => round($yearTotal),
                    'quy_1' => round($quarterTotals[1]),
                    'quy_2' => round($quarterTotals[2]),
                    'quy_3' => round($quarterTotals[3]),
                    'quy_4' => round($quarterTotals[4]),
                ] + $monthlyTotals, // Merge monthly totals into the result
            ];
        }

        return response()->json($results);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableUpdateController extends Controller
{
    public function updateTableTongHopSanLuong(Request $request)
    {
        $years = [2020, date('Y')];
        $months = range(1, 12);
        // $ngayChon = $request->input('ngay_chon', date('Y-m-d'));
        // $year = date('Y', strtotime($ngayChon));
        // $month = date('m', strtotime($ngayChon));
        // $day = date('d', strtotime($ngayChon));

        foreach ($years as $year) {
            foreach ($months as $month) {
                $formattedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
                $sanluongData = DB::table('tbl_sanluong')
                    ->select(
                        DB::raw("UPPER(LEFT(SanLuong_Tram, 3)) as ma_tinh"),
                        DB::raw("DATE_FORMAT(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), '%d') as day"),
                        DB::raw("SUM(SanLuong_Gia) as total_sanluong"),
                        'tbl_tinh.ten_khu_vuc as khu_vuc'
                    )
                    ->leftJoin('tbl_tinh', DB::raw("UPPER(LEFT(tbl_sanluong.SanLuong_Tram, 3))"), '=', 'tbl_tinh.ma_tinh')
                    ->whereYear(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), $year)
                    ->whereMonth(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), $month)
                    ->where('ten_hinh_anh_da_xong', '<>', '')
                    // ->whereDate(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), '=', $ngayChon)
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
                    ->whereYear(DB::raw("STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')"), $year)
                    ->whereMonth(DB::raw("STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')"), $month)
                    // ->whereDate(DB::raw("STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')"), '=', $ngayChon)
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
                    ->whereYear(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), $year)
                    ->whereMonth(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), $month)
                    // ->whereDate(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), '=', $ngayChon)
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
                    DB::table('tbl_tonghop_sanluong')->updateOrInsert(
                        [
                            'ma_tinh' => $data['ma_tinh'],
                            'linh_vuc' => $data['linh_vuc'],
                            'year' => $data['year'],
                            'month' => $data['month'],
                        ],
                        $data
                    );
                }
            }
        }
    }
}
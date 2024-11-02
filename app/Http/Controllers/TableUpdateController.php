<?php

namespace App\Http\Controllers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nette\Utils\DateTime;

class TableUpdateController extends Controller
{
    public function updateTableTongHopSanLuong2(Request $request)
    {
        $years = [2023, date('Y')];
        $months = range(1, 12);
        // $ngayChon = $request->input('ngay_chon', date('Y-m-d'));
        // $year = date('Y', strtotime($ngayChon));
        // $month = date('m', strtotime($ngayChon));
        // $day = date('d', strtotime($ngayChon));
        DB::table('tbl_tonghop_sanluong')
            ->whereNot('linh_vuc', 'EC')
            ->delete();
        foreach ($years as $year) {
            foreach ($months as $month) {
                $formattedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
                $sanluongData = DB::table(DB::raw("(SELECT 
                                        UPPER(LEFT(SanLuong_Tram, 3)) as ma_tinh,
                                        SanLuong_Ngay,
                                        SanLuong_Gia,
                                        HopDong_Id
                                    FROM 
                                        tbl_sanluong
                                    WHERE 
                                        ten_hinh_anh_da_xong <> ''
                                    GROUP BY 
                                        SanLuong_Tram,
                                        SanLuong_Ngay,
                                        SanLuong_Gia,
                                        SanLuong_TenHangMuc,
                                        tbl_sanluong.HopDong_Id
                                    ORDER BY 
                                        UPPER(LEFT(SanLuong_Tram, 3)),
                                        SanLuong_Ngay,
                                        SanLuong_TenHangMuc) AS subquery_sanluong"))
                    ->select(
                        'subquery_sanluong.ma_tinh',
                        DB::raw("DATE_FORMAT(STR_TO_DATE(subquery_sanluong.SanLuong_Ngay, '%d%m%Y'), '%d') as day"),
                        DB::raw("SUM(subquery_sanluong.SanLuong_Gia) as total_sanluong"),
                        'tbl_tinh.ten_khu_vuc as khu_vuc'
                    )
                    ->leftJoin('tbl_tinh', 'subquery_sanluong.ma_tinh', '=', 'tbl_tinh.ma_tinh')
                    ->whereYear(DB::raw("STR_TO_DATE(subquery_sanluong.SanLuong_Ngay, '%d%m%Y')"), $year)
                    ->whereMonth(DB::raw("STR_TO_DATE(subquery_sanluong.SanLuong_Ngay, '%d%m%Y')"), $month)
                    ->groupBy(
                        'subquery_sanluong.ma_tinh',
                        'tbl_tinh.ten_khu_vuc',
                        'subquery_sanluong.SanLuong_Ngay'
                    )
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

                $kiemdinhData = DB::table('tbl_sanluong_kiemdinh')
                    ->select(
                        DB::raw("UPPER(LEFT(KiemDinh_MaTram, 3)) as ma_tinh"),
                        DB::raw("DATE_FORMAT(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y'), '%d') as day"),
                        'KiemDinh_NoiDung as linh_vuc',
                        DB::raw("SUM(KiemDinh_DonGia) as total_sanluong"),
                        'tbl_tinh.ten_khu_vuc as khu_vuc'
                    )
                    ->leftJoin('tbl_tinh', DB::raw("UPPER(LEFT(KiemDinh_MaTram, 3))"), '=', 'tbl_tinh.ma_tinh')
                    ->whereYear(DB::raw("STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')"), $year)
                    ->whereMonth(DB::raw("STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')"), $month)
                    ->groupBy(DB::raw("UPPER(LEFT(KiemDinh_MaTram, 3))"), 'day', 'tbl_tinh.ten_khu_vuc', 'KiemDinh_NoiDung')
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
                            'SanLuong_Ngay_01' => 0,
                            'SanLuong_Ngay_02' => 0,
                            'SanLuong_Ngay_03' => 0,
                            'SanLuong_Ngay_04' => 0,
                            'SanLuong_Ngay_05' => 0,
                            'SanLuong_Ngay_06' => 0,
                            'SanLuong_Ngay_07' => 0,
                            'SanLuong_Ngay_08' => 0,
                            'SanLuong_Ngay_09' => 0,
                            'SanLuong_Ngay_10' => 0,
                            'SanLuong_Ngay_11' => 0,
                            'SanLuong_Ngay_12' => 0,
                            'SanLuong_Ngay_13' => 0,
                            'SanLuong_Ngay_14' => 0,
                            'SanLuong_Ngay_15' => 0,
                            'SanLuong_Ngay_16' => 0,
                            'SanLuong_Ngay_17' => 0,
                            'SanLuong_Ngay_18' => 0,
                            'SanLuong_Ngay_19' => 0,
                            'SanLuong_Ngay_20' => 0,
                            'SanLuong_Ngay_21' => 0,
                            'SanLuong_Ngay_22' => 0,
                            'SanLuong_Ngay_23' => 0,
                            'SanLuong_Ngay_24' => 0,
                            'SanLuong_Ngay_25' => 0,
                            'SanLuong_Ngay_26' => 0,
                            'SanLuong_Ngay_27' => 0,
                            'SanLuong_Ngay_28' => 0,
                            'SanLuong_Ngay_29' => 0,
                            'SanLuong_Ngay_30' => 0,
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
                            'SanLuong_Ngay_01' => 0,
                            'SanLuong_Ngay_02' => 0,
                            'SanLuong_Ngay_03' => 0,
                            'SanLuong_Ngay_04' => 0,
                            'SanLuong_Ngay_05' => 0,
                            'SanLuong_Ngay_06' => 0,
                            'SanLuong_Ngay_07' => 0,
                            'SanLuong_Ngay_08' => 0,
                            'SanLuong_Ngay_09' => 0,
                            'SanLuong_Ngay_10' => 0,
                            'SanLuong_Ngay_11' => 0,
                            'SanLuong_Ngay_12' => 0,
                            'SanLuong_Ngay_13' => 0,
                            'SanLuong_Ngay_14' => 0,
                            'SanLuong_Ngay_15' => 0,
                            'SanLuong_Ngay_16' => 0,
                            'SanLuong_Ngay_17' => 0,
                            'SanLuong_Ngay_18' => 0,
                            'SanLuong_Ngay_19' => 0,
                            'SanLuong_Ngay_20' => 0,
                            'SanLuong_Ngay_21' => 0,
                            'SanLuong_Ngay_22' => 0,
                            'SanLuong_Ngay_23' => 0,
                            'SanLuong_Ngay_24' => 0,
                            'SanLuong_Ngay_25' => 0,
                            'SanLuong_Ngay_26' => 0,
                            'SanLuong_Ngay_27' => 0,
                            'SanLuong_Ngay_28' => 0,
                            'SanLuong_Ngay_29' => 0,
                            'SanLuong_Ngay_30' => 0,
                            'SanLuong_Ngay_31' => 0,
                        ];
                    }
                    $combinedData[$key]["SanLuong_Ngay_{$data->day}"] += $data->total_sanluong;
                }

                foreach ($kiemdinhData as $data) {
                    $key = "{$data->ma_tinh}-KD-{$year}-{$formattedMonth}";
                    if (!isset($combinedData[$key])) {
                        $combinedData[$key] = [
                            'khu_vuc' => $data->khu_vuc ?? '',
                            'linh_vuc' => $data->linh_vuc,
                            'ma_tinh' => $data->ma_tinh ?? '',
                            'year' => $year,
                            'month' => $formattedMonth,
                            'SanLuong_Ngay_01' => 0,
                            'SanLuong_Ngay_02' => 0,
                            'SanLuong_Ngay_03' => 0,
                            'SanLuong_Ngay_04' => 0,
                            'SanLuong_Ngay_05' => 0,
                            'SanLuong_Ngay_06' => 0,
                            'SanLuong_Ngay_07' => 0,
                            'SanLuong_Ngay_08' => 0,
                            'SanLuong_Ngay_09' => 0,
                            'SanLuong_Ngay_10' => 0,
                            'SanLuong_Ngay_11' => 0,
                            'SanLuong_Ngay_12' => 0,
                            'SanLuong_Ngay_13' => 0,
                            'SanLuong_Ngay_14' => 0,
                            'SanLuong_Ngay_15' => 0,
                            'SanLuong_Ngay_16' => 0,
                            'SanLuong_Ngay_17' => 0,
                            'SanLuong_Ngay_18' => 0,
                            'SanLuong_Ngay_19' => 0,
                            'SanLuong_Ngay_20' => 0,
                            'SanLuong_Ngay_21' => 0,
                            'SanLuong_Ngay_22' => 0,
                            'SanLuong_Ngay_23' => 0,
                            'SanLuong_Ngay_24' => 0,
                            'SanLuong_Ngay_25' => 0,
                            'SanLuong_Ngay_26' => 0,
                            'SanLuong_Ngay_27' => 0,
                            'SanLuong_Ngay_28' => 0,
                            'SanLuong_Ngay_29' => 0,
                            'SanLuong_Ngay_30' => 0,
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
                            'SanLuong_Ngay_01' => 0,
                            'SanLuong_Ngay_02' => 0,
                            'SanLuong_Ngay_03' => 0,
                            'SanLuong_Ngay_04' => 0,
                            'SanLuong_Ngay_05' => 0,
                            'SanLuong_Ngay_06' => 0,
                            'SanLuong_Ngay_07' => 0,
                            'SanLuong_Ngay_08' => 0,
                            'SanLuong_Ngay_09' => 0,
                            'SanLuong_Ngay_10' => 0,
                            'SanLuong_Ngay_11' => 0,
                            'SanLuong_Ngay_12' => 0,
                            'SanLuong_Ngay_13' => 0,
                            'SanLuong_Ngay_14' => 0,
                            'SanLuong_Ngay_15' => 0,
                            'SanLuong_Ngay_16' => 0,
                            'SanLuong_Ngay_17' => 0,
                            'SanLuong_Ngay_18' => 0,
                            'SanLuong_Ngay_19' => 0,
                            'SanLuong_Ngay_20' => 0,
                            'SanLuong_Ngay_21' => 0,
                            'SanLuong_Ngay_22' => 0,
                            'SanLuong_Ngay_23' => 0,
                            'SanLuong_Ngay_24' => 0,
                            'SanLuong_Ngay_25' => 0,
                            'SanLuong_Ngay_26' => 0,
                            'SanLuong_Ngay_27' => 0,
                            'SanLuong_Ngay_28' => 0,
                            'SanLuong_Ngay_29' => 0,
                            'SanLuong_Ngay_30' => 0,
                            'SanLuong_Ngay_31' => 0,
                        ];
                    }
                    $combinedData[$key]["SanLuong_Ngay_{$data->day}"] += $data->total_sanluong;
                }
                //dd($combinedData);

                foreach ($combinedData as $data) {
                    $existingData = DB::table('tbl_tonghop_sanluong')
                        ->select('id', 'linh_vuc')
                        ->where('ma_tinh', $data['ma_tinh'])
                        ->where('linh_vuc', $data['linh_vuc'])
                        ->where('year', $data['year'])
                        ->where('month', $data['month'])
                        ->first();
                    if ($existingData) {
                        DB::table('tbl_tonghop_sanluong')
                            ->where(
                                'id',
                                $existingData->id
                            )
                            ->update($data);
                    } else {
                        DB::table('tbl_tonghop_sanluong')->insert($data);
                    }
                }
            }
        }
    }
    public function updateTableTongHopSanLuong()
    {
        // Step 1: Delete all records where linh_vuc is not 'EC'
        DB::table('tbl_tonghop_sanluong')
            ->where('linh_vuc', '<>', 'EC')
            ->delete();
        $years = [2023, date('Y')];
        $months = range(1, 12);
        //TODO: join first tbl_tram.khu_vuc
        $combinedData = [];

        foreach ($years as $year) {
            foreach ($months as $month) {
                $formattedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);

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

                $kiemdinhData = DB::table('tbl_sanluong_kiemdinh')
                    ->select(
                        DB::raw("UPPER(LEFT(KiemDinh_MaTram, 3)) as ma_tinh"),
                        DB::raw("DATE_FORMAT(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y'), '%d') as day"),
                        'KiemDinh_NoiDung as linh_vuc',
                        DB::raw("SUM(KiemDinh_DonGia) as total_sanluong"),
                        'tbl_tinh.ten_khu_vuc as khu_vuc'
                    )
                    ->leftJoin('tbl_tinh', DB::raw("UPPER(LEFT(KiemDinh_MaTram, 3))"), '=', 'tbl_tinh.ma_tinh')
                    ->whereYear(DB::raw("STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')"), $year)
                    ->whereMonth(DB::raw("STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')"), $month)
                    ->groupBy(DB::raw("UPPER(LEFT(KiemDinh_MaTram, 3))"), 'day', 'tbl_tinh.ten_khu_vuc', 'KiemDinh_NoiDung')
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
                foreach ($thaolapData as $data) {
                    $key = "{$data->ma_tinh}-EC-{$year}-{$formattedMonth}";
                    if (!isset($combinedData[$key])) {
                        $combinedData[$key] = [
                            'khu_vuc' => $data->khu_vuc ?? '',
                            'linh_vuc' => 'EC',
                            'ma_tinh' => $data->ma_tinh ?? '',
                            'year' => $year,
                            'month' => $formattedMonth,
                            'SanLuong_Ngay_01' => 0,
                            'SanLuong_Ngay_02' => 0,
                            'SanLuong_Ngay_03' => 0,
                            'SanLuong_Ngay_04' => 0,
                            'SanLuong_Ngay_05' => 0,
                            'SanLuong_Ngay_06' => 0,
                            'SanLuong_Ngay_07' => 0,
                            'SanLuong_Ngay_08' => 0,
                            'SanLuong_Ngay_09' => 0,
                            'SanLuong_Ngay_10' => 0,
                            'SanLuong_Ngay_11' => 0,
                            'SanLuong_Ngay_12' => 0,
                            'SanLuong_Ngay_13' => 0,
                            'SanLuong_Ngay_14' => 0,
                            'SanLuong_Ngay_15' => 0,
                            'SanLuong_Ngay_16' => 0,
                            'SanLuong_Ngay_17' => 0,
                            'SanLuong_Ngay_18' => 0,
                            'SanLuong_Ngay_19' => 0,
                            'SanLuong_Ngay_20' => 0,
                            'SanLuong_Ngay_21' => 0,
                            'SanLuong_Ngay_22' => 0,
                            'SanLuong_Ngay_23' => 0,
                            'SanLuong_Ngay_24' => 0,
                            'SanLuong_Ngay_25' => 0,
                            'SanLuong_Ngay_26' => 0,
                            'SanLuong_Ngay_27' => 0,
                            'SanLuong_Ngay_28' => 0,
                            'SanLuong_Ngay_29' => 0,
                            'SanLuong_Ngay_30' => 0,
                            'SanLuong_Ngay_31' => 0,
                        ];
                    }
                    $combinedData[$key]["SanLuong_Ngay_{$data->day}"] += $data->total_sanluong;
                }

                foreach ($kiemdinhData as $data) {
                    
                    $key = "{$data->ma_tinh}-KD-{$year}-{$formattedMonth}";
                    if (!isset($combinedData[$key])) {
                        $combinedData[$key] = [
                            'khu_vuc' => $data->khu_vuc ?? '',
                            'linh_vuc' => $data->linh_vuc,
                            'ma_tinh' => $data->ma_tinh ?? '',
                            'year' => $year,
                            'month' => $formattedMonth,
                            'SanLuong_Ngay_01' => 0,'SanLuong_Ngay_02' => 0,
                            'SanLuong_Ngay_03' => 0,
                            'SanLuong_Ngay_04' => 0,
                            'SanLuong_Ngay_05' => 0,
                            'SanLuong_Ngay_06' => 0,
                            'SanLuong_Ngay_07' => 0,
                            'SanLuong_Ngay_08' => 0,
                            'SanLuong_Ngay_09' => 0,
                            'SanLuong_Ngay_10' => 0,
                            'SanLuong_Ngay_11' => 0,
                            'SanLuong_Ngay_12' => 0,
                            'SanLuong_Ngay_13' => 0,
                            'SanLuong_Ngay_14' => 0,
                            'SanLuong_Ngay_15' => 0,
                            'SanLuong_Ngay_16' => 0,
                            'SanLuong_Ngay_17' => 0,
                            'SanLuong_Ngay_18' => 0,
                            'SanLuong_Ngay_19' => 0,
                            'SanLuong_Ngay_20' => 0,
                            'SanLuong_Ngay_21' => 0,
                            'SanLuong_Ngay_22' => 0,
                            'SanLuong_Ngay_23' => 0,
                            'SanLuong_Ngay_24' => 0,
                            'SanLuong_Ngay_25' => 0,
                            'SanLuong_Ngay_26' => 0,
                            'SanLuong_Ngay_27' => 0,
                            'SanLuong_Ngay_28' => 0,
                            'SanLuong_Ngay_29' => 0,
                            'SanLuong_Ngay_30' => 0,
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
                            'SanLuong_Ngay_01' => 0,
                            'SanLuong_Ngay_02' => 0,
                            'SanLuong_Ngay_03' => 0,
                            'SanLuong_Ngay_04' => 0,
                            'SanLuong_Ngay_05' => 0,
                            'SanLuong_Ngay_06' => 0,
                            'SanLuong_Ngay_07' => 0,
                            'SanLuong_Ngay_08' => 0,
                            'SanLuong_Ngay_09' => 0,
                            'SanLuong_Ngay_10' => 0,
                            'SanLuong_Ngay_11' => 0,
                            'SanLuong_Ngay_12' => 0,
                            'SanLuong_Ngay_13' => 0,
                            'SanLuong_Ngay_14' => 0,
                            'SanLuong_Ngay_15' => 0,
                            'SanLuong_Ngay_16' => 0,
                            'SanLuong_Ngay_17' => 0,
                            'SanLuong_Ngay_18' => 0,
                            'SanLuong_Ngay_19' => 0,
                            'SanLuong_Ngay_20' => 0,
                            'SanLuong_Ngay_21' => 0,
                            'SanLuong_Ngay_22' => 0,
                            'SanLuong_Ngay_23' => 0,
                            'SanLuong_Ngay_24' => 0,
                            'SanLuong_Ngay_25' => 0,
                            'SanLuong_Ngay_26' => 0,
                            'SanLuong_Ngay_27' => 0,
                            'SanLuong_Ngay_28' => 0,
                            'SanLuong_Ngay_29' => 0,
                            'SanLuong_Ngay_30' => 0,
                            'SanLuong_Ngay_31' => 0,
                        ];
                    }
                    $combinedData[$key]["SanLuong_Ngay_{$data->day}"] += $data->total_sanluong;
                }
            }
        }

        // Step 2: Fetch the data with earliest non-empty 'ten_hinh_anh_da_xong' for each group
        $sanluongData = DB::select("
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
        // Step 3: Aggregate data by day within each key (combination of ma_tinh, khu_vuc, year, month)
        foreach ($sanluongData as $data) {
            $date = DateTime::createFromFormat('dmY', $data->SanLuong_Ngay);

            if ($date) {
                $day = $date->format('d');
                $month = $date->format('m');
                $year = $date->format('Y');
                $dayField = "SanLuong_Ngay_" . str_pad($day, 2, '0', STR_PAD_LEFT);
            } else {
                // Trường hợp lỗi định dạng ngày
                dd("Invalid date format in SanLuong_Ngay: " . $data->SanLuong_Ngay);
            }

            $key = "{$data->ma_tinh}-EC-{$data->khu_vuc}-{$year}-{$month}";
            if (!isset($combinedData[$key])) {
                $combinedData[$key] = [
                    'ma_tinh' => $data->ma_tinh,
                    'linh_vuc' => 'EC',
                    'khu_vuc' => $data->khu_vuc,
                    'year' => $year,
                    'month' => $month,
                    'SanLuong_Ngay_01' => 0,
                    'SanLuong_Ngay_02' => 0,
                    'SanLuong_Ngay_03' => 0,
                    'SanLuong_Ngay_04' => 0,
                    'SanLuong_Ngay_05' => 0,
                    'SanLuong_Ngay_06' => 0,
                    'SanLuong_Ngay_07' => 0,
                    'SanLuong_Ngay_08' => 0,
                    'SanLuong_Ngay_09' => 0,
                    'SanLuong_Ngay_10' => 0,
                    'SanLuong_Ngay_11' => 0,
                    'SanLuong_Ngay_12' => 0,
                    'SanLuong_Ngay_13' => 0,
                    'SanLuong_Ngay_14' => 0,
                    'SanLuong_Ngay_15' => 0,
                    'SanLuong_Ngay_16' => 0,
                    'SanLuong_Ngay_17' => 0,
                    'SanLuong_Ngay_18' => 0,
                    'SanLuong_Ngay_19' => 0,
                    'SanLuong_Ngay_20' => 0,
                    'SanLuong_Ngay_21' => 0,
                    'SanLuong_Ngay_22' => 0,
                    'SanLuong_Ngay_23' => 0,
                    'SanLuong_Ngay_24' => 0,
                    'SanLuong_Ngay_25' => 0,
                    'SanLuong_Ngay_26' => 0,
                    'SanLuong_Ngay_27' => 0,
                    'SanLuong_Ngay_28' => 0,
                    'SanLuong_Ngay_29' => 0,
                    'SanLuong_Ngay_30' => 0,
                    'SanLuong_Ngay_31' => 0,
                ];
            }


            if (isset($combinedData[$key])) {
                $combinedData[$key][$dayField] += $data->SanLuong_Gia;
            } else {
                $combinedData[$key][$dayField] = $data->SanLuong_Gia;
            }
        }

        foreach ($combinedData as $data) {
            $existingData = DB::table('tbl_tonghop_sanluong')
                ->select('id', 'linh_vuc')
                ->where('ma_tinh', $data['ma_tinh'])
                ->where('khu_vuc', $data['khu_vuc'])
                ->where('linh_vuc', $data['linh_vuc'])
                ->where('year', $data['year'])
                ->where('month', $data['month'])
                ->first();
            if ($existingData) {
                DB::table('tbl_tonghop_sanluong')
                    ->where(
                        'id',
                        $existingData->id
                    )
                    ->update($data);
            } else {
                DB::table('tbl_tonghop_sanluong')->insert($data);
            }
        }
    }


    public function updateDailyTableTongHopSanLuong(Request $request)
    {
        // Ngày hiện tại
        $today = $request->input('ngay_chon', date('Y-m-d'));
        $year = date('Y', strtotime($today));
        $month = date('m', strtotime($today));
        $formattedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
        DB::table('tbl_tonghop_sanluong')
            ->whereNot('linh_vuc', 'EC')
            ->delete();

        // Dữ liệu từ bảng tbl_sanluong
        $sanluongData = DB::table(DB::raw("(SELECT 
                                            UPPER(LEFT(SanLuong_Tram, 3)) as ma_tinh,
                                            SanLuong_Ngay,
                                            SanLuong_Gia,
                                            HopDong_Id
                                        FROM 
                                            tbl_sanluong
                                        WHERE 
                                            ten_hinh_anh_da_xong <> ''
                                        GROUP BY 
                                            SanLuong_Tram,
                                            SanLuong_Ngay,
                                            SanLuong_Gia,
                                            SanLuong_TenHangMuc,
                                            tbl_sanluong.HopDong_Id
                                        ORDER BY 
                                            UPPER(LEFT(SanLuong_Tram, 3)),
                                            SanLuong_Ngay,
                                            SanLuong_TenHangMuc) AS subquery_sanluong"))
            ->select(
                'subquery_sanluong.ma_tinh',
                DB::raw("DATE_FORMAT(STR_TO_DATE(subquery_sanluong.SanLuong_Ngay, '%d%m%Y'), '%d') as day"),
                DB::raw("SUM(subquery_sanluong.SanLuong_Gia) as total_sanluong"),
                'tbl_tinh.ten_khu_vuc as khu_vuc'
            )
            ->leftJoin('tbl_tinh', 'subquery_sanluong.ma_tinh', '=', 'tbl_tinh.ma_tinh')
            ->whereDate(DB::raw("STR_TO_DATE(subquery_sanluong.SanLuong_Ngay, '%d%m%Y')"), $today)
            ->groupBy(
                'subquery_sanluong.ma_tinh',
                'tbl_tinh.ten_khu_vuc',
                'subquery_sanluong.SanLuong_Ngay'
            )
            ->get();

        // Dữ liệu từ bảng tbl_sanluong_thaolap
        $thaolapData = DB::table('tbl_sanluong_thaolap')
            ->select(
                DB::raw("UPPER(LEFT(ThaoLap_MaTram, 3)) as ma_tinh"),
                DB::raw("DATE_FORMAT(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), '%d') as day"),
                DB::raw("SUM(ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon) as total_sanluong"),
                'tbl_tinh.ten_khu_vuc as khu_vuc'
            )
            ->leftJoin('tbl_tinh', DB::raw("UPPER(LEFT(ThaoLap_MaTram, 3))"), '=', 'tbl_tinh.ma_tinh')
            ->whereDate(DB::raw("STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')"), $today)
            ->groupBy(DB::raw("UPPER(LEFT(ThaoLap_MaTram, 3))"), 'day', 'tbl_tinh.ten_khu_vuc')
            ->get();

        // Dữ liệu từ bảng tbl_sanluong_kiemdinh
        $kiemdinhData = DB::table('tbl_sanluong_kiemdinh')
            ->select(
                DB::raw("UPPER(LEFT(KiemDinh_MaTram, 3)) as ma_tinh"),
                DB::raw("DATE_FORMAT(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y'), '%d') as day"),
                'KiemDinh_NoiDung as linh_vuc',
                DB::raw("SUM(KiemDinh_DonGia) as total_sanluong"),
                'tbl_tinh.ten_khu_vuc as khu_vuc'
            )
            ->leftJoin('tbl_tinh', DB::raw("UPPER(LEFT(KiemDinh_MaTram, 3))"), '=', 'tbl_tinh.ma_tinh')
            ->whereDate(DB::raw("STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y')"), $today)
            ->groupBy(DB::raw("UPPER(LEFT(KiemDinh_MaTram, 3))"), 'day', 'tbl_tinh.ten_khu_vuc', 'KiemDinh_NoiDung')
            ->get();

        // Dữ liệu từ bảng tbl_sanluong_khac
        $sanluongKhacData = DB::table('tbl_sanluong_khac')
            ->select(
                DB::raw("UPPER(LEFT(SanLuong_Tram, 3)) as ma_tinh"),
                DB::raw("DATE_FORMAT(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), '%d') as day"),
                'SanLuong_TenHangMuc as linh_vuc',
                DB::raw("SUM(SanLuong_Gia) as total_sanluong"),
                'SanLuong_KhuVuc as khu_vuc'
            )
            ->whereDate(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), $today)
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
                    'SanLuong_Ngay_01' => 0,
                    'SanLuong_Ngay_02' => 0,
                    'SanLuong_Ngay_03' => 0,
                    'SanLuong_Ngay_04' => 0,
                    'SanLuong_Ngay_05' => 0,
                    'SanLuong_Ngay_06' => 0,
                    'SanLuong_Ngay_07' => 0,
                    'SanLuong_Ngay_08' => 0,
                    'SanLuong_Ngay_09' => 0,
                    'SanLuong_Ngay_10' => 0,
                    'SanLuong_Ngay_11' => 0,
                    'SanLuong_Ngay_12' => 0,
                    'SanLuong_Ngay_13' => 0,
                    'SanLuong_Ngay_14' => 0,
                    'SanLuong_Ngay_15' => 0,
                    'SanLuong_Ngay_16' => 0,
                    'SanLuong_Ngay_17' => 0,
                    'SanLuong_Ngay_18' => 0,
                    'SanLuong_Ngay_19' => 0,
                    'SanLuong_Ngay_20' => 0,
                    'SanLuong_Ngay_21' => 0,
                    'SanLuong_Ngay_22' => 0,
                    'SanLuong_Ngay_23' => 0,
                    'SanLuong_Ngay_24' => 0,
                    'SanLuong_Ngay_25' => 0,
                    'SanLuong_Ngay_26' => 0,
                    'SanLuong_Ngay_27' => 0,
                    'SanLuong_Ngay_28' => 0,
                    'SanLuong_Ngay_29' => 0,
                    'SanLuong_Ngay_30' => 0,
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
                    'SanLuong_Ngay_01' => 0,
                    'SanLuong_Ngay_02' => 0,
                    'SanLuong_Ngay_03' => 0,
                    'SanLuong_Ngay_04' => 0,
                    'SanLuong_Ngay_05' => 0,
                    'SanLuong_Ngay_06' => 0,
                    'SanLuong_Ngay_07' => 0,
                    'SanLuong_Ngay_08' => 0,
                    'SanLuong_Ngay_09' => 0,
                    'SanLuong_Ngay_10' => 0,
                    'SanLuong_Ngay_11' => 0,
                    'SanLuong_Ngay_12' => 0,
                    'SanLuong_Ngay_13' => 0,
                    'SanLuong_Ngay_14' => 0,
                    'SanLuong_Ngay_15' => 0,
                    'SanLuong_Ngay_16' => 0,
                    'SanLuong_Ngay_17' => 0,
                    'SanLuong_Ngay_18' => 0,
                    'SanLuong_Ngay_19' => 0,
                    'SanLuong_Ngay_20' => 0,
                    'SanLuong_Ngay_21' => 0,
                    'SanLuong_Ngay_22' => 0,
                    'SanLuong_Ngay_23' => 0,
                    'SanLuong_Ngay_24' => 0,
                    'SanLuong_Ngay_25' => 0,
                    'SanLuong_Ngay_26' => 0,
                    'SanLuong_Ngay_27' => 0,
                    'SanLuong_Ngay_28' => 0,
                    'SanLuong_Ngay_29' => 0,
                    'SanLuong_Ngay_30' => 0,
                    'SanLuong_Ngay_31' => 0,
                ];
            }
            $combinedData[$key]["SanLuong_Ngay_{$data->day}"] += $data->total_sanluong;
        }

        foreach ($kiemdinhData as $data) {
            $key = "{$data->ma_tinh}-KD-{$year}-{$formattedMonth}";
            if (!isset($combinedData[$key])) {
                $combinedData[$key] = [
                    'khu_vuc' => $data->khu_vuc ?? '',
                    'linh_vuc' => $data->linh_vuc,
                    'ma_tinh' => $data->ma_tinh ?? '',
                    'year' => $year,
                    'month' => $formattedMonth,
                    'SanLuong_Ngay_01' => 0,
                    'SanLuong_Ngay_02' => 0,
                    'SanLuong_Ngay_03' => 0,
                    'SanLuong_Ngay_04' => 0,
                    'SanLuong_Ngay_05' => 0,
                    'SanLuong_Ngay_06' => 0,
                    'SanLuong_Ngay_07' => 0,
                    'SanLuong_Ngay_08' => 0,
                    'SanLuong_Ngay_09' => 0,
                    'SanLuong_Ngay_10' => 0,
                    'SanLuong_Ngay_11' => 0,
                    'SanLuong_Ngay_12' => 0,
                    'SanLuong_Ngay_13' => 0,
                    'SanLuong_Ngay_14' => 0,
                    'SanLuong_Ngay_15' => 0,
                    'SanLuong_Ngay_16' => 0,
                    'SanLuong_Ngay_17' => 0,
                    'SanLuong_Ngay_18' => 0,
                    'SanLuong_Ngay_19' => 0,
                    'SanLuong_Ngay_20' => 0,
                    'SanLuong_Ngay_21' => 0,
                    'SanLuong_Ngay_22' => 0,
                    'SanLuong_Ngay_23' => 0,
                    'SanLuong_Ngay_24' => 0,
                    'SanLuong_Ngay_25' => 0,
                    'SanLuong_Ngay_26' => 0,
                    'SanLuong_Ngay_27' => 0,
                    'SanLuong_Ngay_28' => 0,
                    'SanLuong_Ngay_29' => 0,
                    'SanLuong_Ngay_30' => 0,
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
                    'SanLuong_Ngay_01' => 0,
                    'SanLuong_Ngay_02' => 0,
                    'SanLuong_Ngay_03' => 0,
                    'SanLuong_Ngay_04' => 0,
                    'SanLuong_Ngay_05' => 0,
                    'SanLuong_Ngay_06' => 0,
                    'SanLuong_Ngay_07' => 0,
                    'SanLuong_Ngay_08' => 0,
                    'SanLuong_Ngay_09' => 0,
                    'SanLuong_Ngay_10' => 0,
                    'SanLuong_Ngay_11' => 0,
                    'SanLuong_Ngay_12' => 0,
                    'SanLuong_Ngay_13' => 0,
                    'SanLuong_Ngay_14' => 0,
                    'SanLuong_Ngay_15' => 0,
                    'SanLuong_Ngay_16' => 0,
                    'SanLuong_Ngay_17' => 0,
                    'SanLuong_Ngay_18' => 0,
                    'SanLuong_Ngay_19' => 0,
                    'SanLuong_Ngay_20' => 0,
                    'SanLuong_Ngay_21' => 0,
                    'SanLuong_Ngay_22' => 0,
                    'SanLuong_Ngay_23' => 0,
                    'SanLuong_Ngay_24' => 0,
                    'SanLuong_Ngay_25' => 0,
                    'SanLuong_Ngay_26' => 0,
                    'SanLuong_Ngay_27' => 0,
                    'SanLuong_Ngay_28' => 0,
                    'SanLuong_Ngay_29' => 0,
                    'SanLuong_Ngay_30' => 0,
                    'SanLuong_Ngay_31' => 0,
                ];
            }
            $combinedData[$key]["SanLuong_Ngay_{$data->day}"] += $data->total_sanluong;
        }

        foreach ($combinedData as $key => $data) {
            $existingData = DB::table('tbl_tonghop_sanluong')
                ->select('id')
                ->where('ma_tinh', $data['ma_tinh'])
                ->where('linh_vuc', $data['linh_vuc'])
                ->where('year', $data['year'])
                ->where('month', $data['month'])
                ->first();

            if ($existingData) {
                $updateData = [];
                foreach ($data as $column => $value) {
                    if ($value != 0 && strpos($column, 'SanLuong_Ngay_') !== false) {
                        $updateData[$column] = $value;
                    }
                }
                if (!empty($updateData)) {
                    DB::table('tbl_tonghop_sanluong')
                        ->where('id', $existingData->id)
                        ->update($updateData);
                }
            } else {
                DB::table('tbl_tonghop_sanluong')->insert($data);
            }
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nette\Utils\DateTime;

class TableUpdateController extends Controller
{
    
    public function updateTableTongHopSanLuong()
    {
        // Step 1: Delete all records where linh_vuc is not 'EC'
        DB::table('tbl_tonghop_sanluong')
            ->where('linh_vuc', '<>', 'EC')
            ->delete();
        $combinedData = [];

        // Step 2: Fetch the data
        $sanluongData = DB::table('tbl_sanluong')
            ->join('tbl_tram', function ($join) {
                $join->on('tbl_sanluong.SanLuong_Tram', '=', 'tbl_tram.ma_tram')
                    ->on('tbl_sanluong.HopDong_Id', '=', 'tbl_tram.hopdong_id')
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('tbl_hinhanh')
                            ->whereColumn('tbl_hinhanh.ma_tram', 'tbl_sanluong.SanLuong_Tram');
                    });
            })
            ->join('tbl_hopdong', 'tbl_sanluong.HopDong_Id', '=', 'tbl_hopdong.HopDong_Id')
            ->select(
                'tbl_sanluong.SanLuong_Tram',
                'tbl_tram.khu_vuc',
                'tbl_tram.ma_tinh',
                'tbl_hopdong.HopDong_SoHopDong',
                'SanLuong_Ngay',
                DB::raw('SUM(tbl_sanluong.SanLuong_Gia) as SanLuong_Gia')
            )
            ->whereNot('ten_hinh_anh_da_xong', "")
            ->groupBy('tbl_sanluong.SanLuong_Ngay', 'tbl_sanluong.SanLuong_Tram', 'tbl_tram.khu_vuc', 'tbl_tram.ma_tinh', 'tbl_hopdong.HopDong_SoHopDong')
            // ->orderBy('tbl_sanluong.SanLuong_Ngay', 'asc')
            ->get();

        $thaolapData = DB::table('tbl_sanluong_thaolap')
            ->join('tbl_tram', function ($join) {
                $join->on('tbl_sanluong_thaolap.ThaoLap_MaTram', '=', 'tbl_tram.ma_tram')
                    ->on('tbl_sanluong_thaolap.HopDong_Id', '=', 'tbl_tram.hopdong_id');
            })
            ->join('tbl_hopdong', 'tbl_sanluong_thaolap.HopDong_Id', '=', 'tbl_hopdong.HopDong_Id')
            ->select(
                'tbl_sanluong_thaolap.ThaoLap_MaTram as SanLuong_Tram',
                'tbl_tram.khu_vuc',
                'tbl_tram.ma_tinh',
                'tbl_hopdong.HopDong_SoHopDong',
                'ThaoLap_Ngay',
                DB::raw('SUM(ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon) as SanLuong_Gia')
            )
            ->groupBy('ThaoLap_Ngay', 'tbl_sanluong_thaolap.ThaoLap_MaTram', 'tbl_tram.khu_vuc', 'tbl_tram.ma_tinh', 'tbl_hopdong.HopDong_SoHopDong')
            // ->orderBy('tbl_sanluong.SanLuong_Tram', 'asc')
            ->get();

        $kiemdinhData = DB::table('tbl_sanluong_kiemdinh')
            ->join('tbl_tram', function ($join) {
                $join->on('tbl_sanluong_kiemdinh.KiemDinh_MaTram', '=', 'tbl_tram.ma_tram')
                    ->on('tbl_sanluong_kiemdinh.HopDong_Id', '=', 'tbl_tram.hopdong_id');
            })
            ->join('tbl_hopdong', 'tbl_sanluong_kiemdinh.HopDong_Id', '=', 'tbl_hopdong.HopDong_Id')
            ->select(
                'tbl_sanluong_kiemdinh.KiemDinh_MaTram as SanLuong_Tram',
                'tbl_tram.khu_vuc',
                'tbl_tram.ma_tinh',
                'tbl_hopdong.HopDong_SoHopDong',
                'KiemDinh_Ngay',
                DB::raw('SUM(KiemDinh_DonGia) as SanLuong_Gia')
            )
            ->groupBy('KiemDinh_Ngay', 'tbl_sanluong_kiemdinh.KiemDinh_MaTram', 'tbl_tram.khu_vuc', 'tbl_tram.ma_tinh', 'tbl_hopdong.HopDong_SoHopDong')
            // ->orderBy('tbl_sanluong.SanLuong_Tram', 'asc')
            ->get();

        //TODO: SL khác có join theo trạm không?
        $sanluongKhacData = DB::table('tbl_sanluong_khac')
            ->select(
                DB::raw("UPPER(LEFT(SanLuong_Tram, 3)) as ma_tinh"),
                'SanLuong_Ngay',
                'SanLuong_TenHangMuc as linh_vuc',
                DB::raw("SUM(SanLuong_Gia) as SanLuong_Gia"),
                'SanLuong_KhuVuc as khu_vuc',
            )
            // ->whereDate(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), '=', $ngayChon)
            ->groupBy('ma_tinh', 'SanLuong_Ngay', 'SanLuong_TenHangMuc', 'SanLuong_KhuVuc')
            ->get();

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
        foreach ($thaolapData as $data) {
            $date = DateTime::createFromFormat('d/m/Y', $data->ThaoLap_Ngay);
            if ($date) {
                $day = $date->format('d');
                $month = $date->format('m');
                $year = $date->format('Y');
                $dayField = "SanLuong_Ngay_" . str_pad($day, 2, '0', STR_PAD_LEFT);
            } else {
                // Trường hợp lỗi định dạng ngày
                dd("Invalid date format in SanLuong_Ngay: " . $data->SanLuong_Ngay);
            }

            $key = "{$data->ma_tinh}-TL-{$data->khu_vuc}-{$year}-{$month}";
            if (!isset($combinedData[$key])) {
                $combinedData[$key] = [
                    'ma_tinh' => $data->ma_tinh,
                    'linh_vuc' => 'Tháo lắp',
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
        foreach ($kiemdinhData as $data) {
            $date = DateTime::createFromFormat('d/m/Y', $data->KiemDinh_Ngay);
            if ($date) {
                $day = $date->format('d');
                $month = $date->format('m');
                $year = $date->format('Y');
                $dayField = "SanLuong_Ngay_" . str_pad($day, 2, '0', STR_PAD_LEFT);
            } else {
                // Trường hợp lỗi định dạng ngày
                dd("Invalid date format in SanLuong_Ngay: " . $data->SanLuong_Ngay);
            }

            $key = "{$data->ma_tinh}-KD-{$data->khu_vuc}-{$year}-{$month}";
            if (!isset($combinedData[$key])) {
                $combinedData[$key] = [
                    'ma_tinh' => $data->ma_tinh,
                    'linh_vuc' => 'Kiểm định',
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
        foreach ($sanluongKhacData as $data) {
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

            $key = "{$data->ma_tinh}-Khac-{$data->khu_vuc}-{$year}-{$month}";
            if (!isset($combinedData[$key])) {
                $combinedData[$key] = [
                    'ma_tinh' => $data->ma_tinh,
                    'linh_vuc' => $data->linh_vuc,
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
        $daysString = $request->input('days', date('d-m-Y'));
        $days = [];
        if (!empty($daysString)) {
            $days = explode(',', $daysString);
            $days = array_map(function ($day) {
                return str_replace('-', '', $day);
            }, $days);
        }
        $searchMaTram = $request->input('searchMaTram', '');
        $searchHopDong = $request->input('searchHopDong', '');
        $searchKhuVuc = $request->input('searchKhuVuc', '');
        dd($days);

        // Biến điều kiện
        $hinhAnhDayCondition = count($days) > 0 ? "AND thoi_gian_chup IN ('" . implode("','", $days) . "')" : '';
        $dayCondition = count($days) > 0 ? "AND SanLuong_Ngay IN (" . implode(',', $days) . ")" : "";
        $thaoLapDayCondition = count($days) > 0 ? "AND DATE_FORMAT(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), '%d%m%Y') IN (" . implode(',', $days) . ")" : "";
        $kiemDinhDayCondition = count($days) > 0 ? "AND DATE_FORMAT(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y'), '%d%m%Y') IN (" . implode(',', $days) . ")" : "";
        $searchCondition = !empty($searchMaTram) ? "AND SanLuong_Tram LIKE '%$searchMaTram%'" : "";
        $searchCondition2 = !empty($searchMaTram) ? "AND ThaoLap_MaTram LIKE '%$searchMaTram%'" : "";
        $searchCondition3 = !empty($searchMaTram) ? "AND KiemDinh_MaTram LIKE '%$searchMaTram%'" : "";
        $searchCondition4 = !empty($searchMaTram) ? "AND tbl_hinhanh.ma_tram LIKE '%$searchMaTram%'" : '';
        $searchConditionHopDong = !empty($searchHopDong) ? "AND tbl_hopdong.HopDong_SoHopDong LIKE '%$searchHopDong%'" : "";
        $searchConditionKhuVuc = !empty($searchKhuVuc) ? "AND tbl_tram.khu_vuc LIKE '%$searchKhuVuc%'" : "";
        $searchConditionKhuVuc2 = !empty($searchKhuVuc) ? "AND FirstTram.khu_vuc LIKE '%$searchKhuVuc%'" : "";

        // $today = $request->input('ngay_chon', date('Y-m-d'));
        // $year = date('Y', strtotime($today));
        // $month = date('m', strtotime($today));
        // $formattedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
        // Step 1: Delete all records where linh_vuc is not 'EC'
        DB::table('tbl_tonghop_sanluong')
            ->where('linh_vuc', '<>', 'EC')
            ->delete();
        $combinedData = [];

        // Step 2: Fetch the data
        $sanluongData = DB::table('tbl_sanluong')
            ->leftJoin('tbl_tram', function ($join) {
                $join->on('tbl_sanluong.SanLuong_Tram', '=', 'tbl_tram.ma_tram')
                    ->on('tbl_sanluong.HopDong_Id', '=', 'tbl_tram.hopdong_id')
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('tbl_hinhanh')
                            ->whereColumn('tbl_hinhanh.ma_tram', 'tbl_sanluong.SanLuong_Tram');
                    });
            })
            ->join('tbl_hopdong', 'tbl_sanluong.HopDong_Id', '=', 'tbl_hopdong.HopDong_Id')
            ->select(
                'tbl_sanluong.SanLuong_Tram',
                DB::raw('COALESCE(tbl_tram.khu_vuc, (SELECT khu_vuc FROM tbl_tram WHERE tbl_sanluong.SanLuong_Tram = tbl_tram.ma_tram LIMIT 1)) AS khu_vuc'),
                'tbl_tram.ma_tinh',
                'tbl_hopdong.HopDong_SoHopDong',
                'SanLuong_Ngay',
                DB::raw('SUM(tbl_sanluong.SanLuong_Gia) as SanLuong_Gia')
            )
            ->where('SanLuong_Ngay')
            ->whereNot('ten_hinh_anh_da_xong', "")
            ->whereRaw("1 $dayCondition $searchCondition $searchConditionHopDong $searchConditionKhuVuc")
            ->groupBy('tbl_sanluong.SanLuong_Ngay', 'tbl_sanluong.SanLuong_Tram', 'khu_vuc', 'tbl_tram.ma_tinh', 'tbl_hopdong.HopDong_SoHopDong')
            // ->orderBy('tbl_sanluong.SanLuong_Ngay', 'asc')
            ->get();

        $thaolapData = DB::table('tbl_sanluong_thaolap')
            ->join('tbl_tram', function ($join) {
                $join->on('tbl_sanluong_thaolap.ThaoLap_MaTram', '=', 'tbl_tram.ma_tram')
                    ->on('tbl_sanluong_thaolap.HopDong_Id', '=', 'tbl_tram.hopdong_id');
            })
            ->join('tbl_hopdong', 'tbl_sanluong_thaolap.HopDong_Id', '=', 'tbl_hopdong.HopDong_Id')
            ->select(
                'tbl_sanluong_thaolap.ThaoLap_MaTram as SanLuong_Tram',
                'tbl_tram.khu_vuc',
                'tbl_tram.ma_tinh',
                'tbl_hopdong.HopDong_SoHopDong',
                'ThaoLap_Ngay',
                DB::raw('SUM(ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon) as SanLuong_Gia')
            )
            ->whereRaw("1 $thaoLapDayCondition $searchCondition2 $searchConditionHopDong $searchConditionKhuVuc")
            ->groupBy('ThaoLap_Ngay', 'tbl_sanluong_thaolap.ThaoLap_MaTram', 'tbl_tram.khu_vuc', 'tbl_tram.ma_tinh', 'tbl_hopdong.HopDong_SoHopDong')
            // ->orderBy('tbl_sanluong.SanLuong_Tram', 'asc')
            ->get();

        $kiemdinhData = DB::table('tbl_sanluong_kiemdinh')
            ->join('tbl_tram', function ($join) {
                $join->on('tbl_sanluong_kiemdinh.KiemDinh_MaTram', '=', 'tbl_tram.ma_tram')
                    ->on('tbl_sanluong_kiemdinh.HopDong_Id', '=', 'tbl_tram.hopdong_id');
            })
            ->join('tbl_hopdong', 'tbl_sanluong_kiemdinh.HopDong_Id', '=', 'tbl_hopdong.HopDong_Id')
            ->select(
                'tbl_sanluong_kiemdinh.KiemDinh_MaTram as SanLuong_Tram',
                'tbl_tram.khu_vuc',
                'tbl_tram.ma_tinh',
                'tbl_hopdong.HopDong_SoHopDong',
                'KiemDinh_Ngay',
                DB::raw('SUM(KiemDinh_DonGia) as SanLuong_Gia')
            )
            ->whereRaw("1 $kiemDinhDayCondition $searchCondition3 $searchConditionHopDong $searchConditionKhuVuc")
            ->groupBy('KiemDinh_Ngay', 'tbl_sanluong_kiemdinh.KiemDinh_MaTram', 'tbl_tram.khu_vuc', 'tbl_tram.ma_tinh', 'tbl_hopdong.HopDong_SoHopDong')
            // ->orderBy('tbl_sanluong.SanLuong_Tram', 'asc')
            ->get();

        //TODO: SL khác có join theo trạm không?
        $sanluongKhacData = DB::table('tbl_sanluong_khac')
            ->select(
                DB::raw("UPPER(LEFT(SanLuong_Tram, 3)) as ma_tinh"),
                'SanLuong_Ngay',
                'SanLuong_TenHangMuc as linh_vuc',
                DB::raw("SUM(SanLuong_Gia) as SanLuong_Gia"),
                'SanLuong_KhuVuc as khu_vuc',
            )
            // ->whereDate(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), '=', $ngayChon)
            ->groupBy('ma_tinh', 'SanLuong_Ngay', 'SanLuong_TenHangMuc', 'SanLuong_KhuVuc')
            ->get();

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
        foreach ($thaolapData as $data) {
            $date = DateTime::createFromFormat('d/m/Y', $data->ThaoLap_Ngay);
            if ($date) {
                $day = $date->format('d');
                $month = $date->format('m');
                $year = $date->format('Y');
                $dayField = "SanLuong_Ngay_" . str_pad($day, 2, '0', STR_PAD_LEFT);
            } else {
                // Trường hợp lỗi định dạng ngày
                dd("Invalid date format in SanLuong_Ngay: " . $data->SanLuong_Ngay);
            }

            $key = "{$data->ma_tinh}-TL-{$data->khu_vuc}-{$year}-{$month}";
            if (!isset($combinedData[$key])) {
                $combinedData[$key] = [
                    'ma_tinh' => $data->ma_tinh,
                    'linh_vuc' => 'Tháo lắp',
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
        foreach ($kiemdinhData as $data) {
            $date = DateTime::createFromFormat('d/m/Y', $data->KiemDinh_Ngay);
            if ($date) {
                $day = $date->format('d');
                $month = $date->format('m');
                $year = $date->format('Y');
                $dayField = "SanLuong_Ngay_" . str_pad($day, 2, '0', STR_PAD_LEFT);
            } else {
                // Trường hợp lỗi định dạng ngày
                dd("Invalid date format in SanLuong_Ngay: " . $data->SanLuong_Ngay);
            }

            $key = "{$data->ma_tinh}-KD-{$data->khu_vuc}-{$year}-{$month}";
            if (!isset($combinedData[$key])) {
                $combinedData[$key] = [
                    'ma_tinh' => $data->ma_tinh,
                    'linh_vuc' => 'Kiểm định',
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
        foreach ($sanluongKhacData as $data) {
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

            $key = "{$data->ma_tinh}-Khac-{$data->khu_vuc}-{$year}-{$month}";
            if (!isset($combinedData[$key])) {
                $combinedData[$key] = [
                    'ma_tinh' => $data->ma_tinh,
                    'linh_vuc' => $data->linh_vuc,
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
}

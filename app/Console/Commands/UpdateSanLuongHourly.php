<?php

namespace App\Console\Commands;

use GuzzleHttp\Psr7\Request;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Nette\Utils\DateTime;

class UpdateSanLuongHourly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-san-luong-hourly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all into tbl_tonghop_sanluong';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Ngày hiện tại
            $daysString = date('d-m-Y');
        $days = [];
        if (!empty($daysString)) {
            $days = explode(',', $daysString);
            $days = array_map(function ($day) {
                return str_replace('-', '', $day);
            }, $days);
        }

        // Biến điều kiện
        $dayCondition = count($days) > 0 ? "AND SanLuong_Ngay IN (" . implode(',', $days) . ")" : "";
        $thaoLapDayCondition = count($days) > 0 ? "AND DATE_FORMAT(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), '%d%m%Y') IN (" . implode(',', $days) . ")" : "";
        $kiemDinhDayCondition = count($days) > 0 ? "AND DATE_FORMAT(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y'), '%d%m%Y') IN (" . implode(',', $days) . ")" : "";
        
        $combinedData = [];

        // Step 2: Fetch the data
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
                'tbl_sanluong.SanLuong_Tram',
                DB::raw("COALESCE(tbl_tram.khu_vuc, tbl_tinh.ten_khu_vuc) as khu_vuc"),
                DB::raw("UPPER(LEFT(tbl_sanluong.SanLuong_Tram, 3)) as ma_tinh"),
                'tbl_hopdong.HopDong_SoHopDong',
                'SanLuong_Ngay',
                DB::raw('SUM(tbl_sanluong.SanLuong_Gia) as SanLuong_Gia')
            )
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('tbl_hinhanh')
                    ->whereColumn('tbl_hinhanh.ma_tram', 'tbl_sanluong.SanLuong_Tram');
            })
            ->whereNot('ten_hinh_anh_da_xong', "")
            ->whereNot("tbl_tram.ma_tinh", null)
            ->whereRaw("1 $dayCondition")
            ->groupBy('SanLuong_Ngay', 'tbl_sanluong.SanLuong_Tram', 'khu_vuc', 'tbl_tram.ma_tinh', 'tbl_hopdong.HopDong_SoHopDong', 'tbl_tinh.ten_khu_vuc')
            // ->orderBy('tbl_sanluong.SanLuong_Tram', 'asc')
            ->get();

            $thaolapData = DB::table('tbl_sanluong_thaolap')
            ->leftJoin('tbl_tram', function ($join) {
                $join->on('tbl_sanluong_thaolap.ThaoLap_MaTram', '=', 'tbl_tram.ma_tram')
                    ->on('tbl_sanluong_thaolap.HopDong_Id', '=', 'tbl_tram.hopdong_id');
            })
            ->leftJoin('tbl_tinh', function ($join) {
                $join->on(DB::raw("UPPER(LEFT(tbl_sanluong_thaolap.ThaoLap_MaTram, 3))"), '=', 'tbl_tinh.ma_tinh');
            })
            ->join('tbl_hopdong', 'tbl_sanluong_thaolap.HopDong_Id', '=', 'tbl_hopdong.HopDong_Id')
            ->select(
                'tbl_sanluong_thaolap.ThaoLap_MaTram as SanLuong_Tram',
                DB::raw("COALESCE(tbl_tram.khu_vuc, tbl_tinh.ten_khu_vuc) as khu_vuc"),
                DB::raw("UPPER(LEFT(tbl_sanluong_thaolap.ThaoLap_MaTram, 3)) as ma_tinh"),
                'tbl_hopdong.HopDong_SoHopDong',
                'ThaoLap_Ngay',
                DB::raw('SUM(ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon) as SanLuong_Gia')
                )
            ->whereNot('ThaoLap_Ngay', "")
            ->whereNot("tbl_tram.ma_tinh", null)
            ->whereRaw("1 $thaoLapDayCondition")
            ->groupBy('ThaoLap_Ngay', 'tbl_sanluong_thaolap.ThaoLap_MaTram', 'tbl_tram.khu_vuc', 'tbl_tram.ma_tinh', 'tbl_hopdong.HopDong_SoHopDong', "tbl_tinh.ten_khu_vuc")
            // ->orderBy('tbl_sanluong.SanLuong_Tram', 'asc')
            ->get();

            $kiemdinhData = DB::table('tbl_sanluong_kiemdinh')
            ->leftJoin('tbl_tram', function ($join) {
                $join->on('tbl_sanluong_kiemdinh.KiemDinh_MaTram', '=', 'tbl_tram.ma_tram')
                    ->on('tbl_sanluong_kiemdinh.HopDong_Id', '=', 'tbl_tram.hopdong_id');
            })
            ->leftJoin('tbl_tinh', function ($join) {
                $join->on(DB::raw("UPPER(LEFT(tbl_sanluong_kiemdinh.KiemDinh_MaTram, 3))"), '=', 'tbl_tinh.ma_tinh');
            })
            ->join('tbl_hopdong', 'tbl_sanluong_kiemdinh.HopDong_Id', '=', 'tbl_hopdong.HopDong_Id')
            ->select(
                'tbl_sanluong_kiemdinh.KiemDinh_MaTram as SanLuong_Tram',
                DB::raw("COALESCE(tbl_tram.khu_vuc, tbl_tinh.ten_khu_vuc) as khu_vuc"),
                DB::raw("UPPER(LEFT(tbl_sanluong_kiemdinh.KiemDinh_MaTram, 3)) as ma_tinh"),
                'tbl_hopdong.HopDong_SoHopDong',
                'KiemDinh_Ngay',
                DB::raw('SUM(KiemDinh_DonGia) as SanLuong_Gia')
            )
            ->whereNot("tbl_tram.ma_tinh", null)
            ->whereRaw("1 $kiemDinhDayCondition")
            ->groupBy('KiemDinh_Ngay', 'tbl_sanluong_kiemdinh.KiemDinh_MaTram', 'tbl_tram.khu_vuc', 'tbl_tram.ma_tinh', 'tbl_hopdong.HopDong_SoHopDong', 'tbl_tinh.ten_khu_vuc')
            // ->orderBy('tbl_sanluong.SanLuong_Tram', 'asc')
            ->get();

        $sanluongKhacData = DB::table('tbl_sanluong_khac')
            ->select(
                'SanLuong_Ngay',
                'SanLuong_TenHangMuc as linh_vuc',
                DB::raw("SUM(SanLuong_Gia) as SanLuong_Gia"),
                'SanLuong_KhuVuc as khu_vuc',
            )
            // ->whereRaw("1 $dayCondition")
            ->groupBy('SanLuong_Ngay', 'SanLuong_TenHangMuc', 'SanLuong_KhuVuc')
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

            $key = "Khac-{$data->khu_vuc}-{$year}-{$month}";
            if (!isset($combinedData[$key])) {
                $combinedData[$key] = [
                    'ma_tinh' => "",
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
        } catch (\Throwable $th) {
            info('Error Update san luong hourly: ', $th);
        }
        
    }
}

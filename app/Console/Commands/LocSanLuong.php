<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocSanLuong extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:loc-san-luong';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update tbl_sanluong based on tbl_hinhanh';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = date('dmY');

        try {
            // Fetch relevant records from tbl_hinhanh
            $images = DB::table('tbl_hinhanh')
                ->select('ma_tram', 'ten_hang_muc', 'HopDong_Id', 'ten_anh_chuan_bi', 'ten_anh_da_xong')
                ->where(function($query) use ($today) {
                    $query->where('loc_dau_viec_trung', 'Khong Trung')
                          ->where('ten_anh_da_xong', '<>', '')
                          ->orWhere('ten_anh_chuan_bi', '<>', '');
                })
                ->whereDate('thoi_gian_chup', $today)
                ->get();

            foreach ($images as $image) {
                $ma_tram = $image->ma_tram;
                $ten_hang_muc = $image->ten_hang_muc;

                // Fetch max ten_anh_chuan_bi and max ten_anh_da_xong
                $max_chuan_bi = DB::table('tbl_hinhanh')
                    ->where('ma_tram', $ma_tram)
                    ->where('ten_hang_muc', $ten_hang_muc)
                    ->max('ten_anh_chuan_bi');

                $max_da_xong = DB::table('tbl_hinhanh')
                    ->where('ma_tram', $ma_tram)
                    ->where('ten_hang_muc', $ten_hang_muc)
                    ->max('ten_anh_da_xong');

                // Get the corresponding CongViec_DonGia
                $sanLuongGia = DB::table('tbl_hopdong_congviec')
                    ->where('CongViec_Ten', $ten_hang_muc)
                    ->value('CongViec_DonGia');

                // Insert into tbl_sanluong
                DB::table('tbl_sanluong')->insert([
                    'HopDong_Id' => $image->HopDong_Id,
                    'SanLuong_Tram' => $ma_tram,
                    'SanLuong_Ngay' => $today,
                    'SanLuong_Gia' => $sanLuongGia,
                    'SanLuong_TenHangMuc' => $ten_hang_muc,
                    'ten_hinh_anh_chuan_bi' => $max_chuan_bi,
                    'ten_hinh_anh_da_xong' => $max_da_xong
                ]);
            }

            Log::info('Successfully updated tbl_sanluong');
            return 0;
        } catch (\Exception $e) {
            Log::error('Error updating tbl_sanluong', ['exception' => $e->getMessage()]);
            return 1;
        }
    }
}

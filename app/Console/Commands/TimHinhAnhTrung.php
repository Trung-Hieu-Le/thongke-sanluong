<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TimHinhAnhTrung extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:tim-hinh-anh-trung';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find duplicated on tbl_hinhanh';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = date('dmY');

        try {
            Log::info('Starting the TimHinhAnhTrung command', ['date' => $today]);

            $images = DB::table('tbl_hinhanh')
                ->select('ma_tram', 'ten_hang_muc')
                ->distinct()
                ->where('ten_anh_da_xong', '<>', '')
                ->whereDate('thoi_gian_chup', '=', $today)
                ->get();

            if ($images->isEmpty()) {
                Log::info('No images found for today.');
                return 0;
            }
            Log::info('Fetched images', ['count' => $images->count()]);

            foreach ($images as $image) {
                $ma_tram = $image->ma_tram;
                $ten_hang_muc = $image->ten_hang_muc;

                // Fetch minimum thoi_gian_chup
                $min_time = DB::table('tbl_hinhanh')
                    ->where('ma_tram', $ma_tram)
                    ->where('ten_hang_muc', $ten_hang_muc)
                    ->min('thoi_gian_chup');

                if ($min_time) {
                    // Update loc_dau_viec_trung
                    DB::table('tbl_hinhanh')
                        ->where('ma_tram', $ma_tram)
                        ->where('ten_hang_muc', $ten_hang_muc)
                        ->whereDate('thoi_gian_chup', '=', $today)
                        ->update([
                            'loc_dau_viec_trung' => DB::raw("CASE WHEN thoi_gian_chup = '$min_time' THEN 'Khong Trung' ELSE 'Trung' END")
                        ]);
                } else {
                    Log::info("No images found for ma_tram: $ma_tram and ten_hang_muc: $ten_hang_muc");
                }
            }

            Log::info('Successfully updated tbl_hinhanh.');
            return 0;
        } catch (\Exception $e) {
            Log::error('Error updating tbl_hinhanh', ['exception' => $e->getMessage()]);
            return 1;
        }
    }
}

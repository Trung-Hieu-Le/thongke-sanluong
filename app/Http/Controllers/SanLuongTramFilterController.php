<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SanLuongTramFilterController extends Controller
{
    public function indexTramFilter(Request $request)
    {
        $query = DB::table('tbl_sanluong')
            ->select('SanLuong_Id', 'HopDong_Id', 'SanLuong_Tram', 'SanLuong_Ngay', 'SanLuong_Gia', 'SanLuong_TenHangMuc', 'ten_hinh_anh_chuan_bi', 'ten_hinh_anh_da_xong');
    
        $days = $request->input('days', []);
        $selectedMonth = $request->input('month', date('m'));
        $selectedYear = $request->input('year', date('Y'));
        $totalGia = 0;

        if (count($days) > 0) {
            $query->whereIn('SanLuong_Ngay', $days);
            $totalGia = DB::table('tbl_sanluong')
                ->whereIn('SanLuong_Ngay', $days)
                ->sum('SanLuong_Gia');
        }
        $query->whereNotNull('ten_hinh_anh_da_xong');  
        $data = $query->simplePaginate(100);
        // dd($data);
        return view('thongke_tram_filter', compact('data', 'totalGia', 'days', 'selectedMonth', 'selectedYear'));
    }
public function getDayTramFilter(Request $request)
{
    $month = $request->input('thang', date('n'));
    $year = $request->input('nam', date('Y'));
    $days = DB::table('tbl_sanluong')
        ->select('SanLuong_Ngay')
        ->whereRaw("YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $year AND MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $month")
        ->distinct()
        ->orderBy('SanLuong_Ngay')
        ->get()
        ->pluck('SanLuong_Ngay');

    return response()->json($days);
}

}
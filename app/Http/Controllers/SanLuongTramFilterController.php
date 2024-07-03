<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SanLuongTramFilterController extends Controller
{
    //TODO: cộng 3 bảng
    public function indexTramFilter(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }

        // Lấy thông tin ngày
        $daysString = $request->input('days', date('d-m-Y'));
        $days = [];
        if (!empty($daysString)) {
            $days = explode(',', $daysString);
            $days = array_map(function ($day) {
                return str_replace('-', '', $day);
            }, $days);
        }

        $search = $request->input('search', '');

        // Truy vấn dữ liệu chi tiết
        $query = DB::table('tbl_sanluong')
            ->select(
                DB::raw('LEFT(tbl_sanluong.SanLuong_Tram, 3) as ma_tinh'),
                'tbl_sanluong.SanLuong_Tram',
                DB::raw('SUM(tbl_sanluong.SanLuong_Gia) as SanLuong_Gia'),
                'tbl_tinh.ten_khu_vuc'
            )
            ->leftJoin('tbl_tinh', DB::raw('LEFT(tbl_sanluong.SanLuong_Tram, 3)'), '=', 'tbl_tinh.ma_tinh')
            ->groupBy('tbl_sanluong.SanLuong_Tram', 'tbl_tinh.ten_khu_vuc')
            ->orderBy('tbl_sanluong.SanLuong_Tram', 'asc');

        if (count($days) > 0) {
            $query->whereIn('tbl_sanluong.SanLuong_Ngay', $days);
        }
        if (!empty($search)) {
            $query->where('tbl_sanluong.SanLuong_Tram', 'like', "%$search%");
        }

        $query->whereNotNull('tbl_sanluong.ten_hinh_anh_da_xong')
            ->where('tbl_sanluong.ten_hinh_anh_da_xong', '<>', '');

        // Truy vấn để tính tổng giá trị
        // $totalQuery = DB::table('tbl_sanluong')
        //     ->select(DB::raw('SUM(tbl_sanluong.SanLuong_Gia) as total_gia'))
        //     ->leftJoin('tbl_tinh', DB::raw('LEFT(tbl_sanluong.SanLuong_Tram, 3)'), '=', 'tbl_tinh.ma_tinh');

        // if (count($days) > 0) {
        //     $totalQuery->whereIn('tbl_sanluong.SanLuong_Ngay', $days);
        // }
        // if (!empty($search)) {
        //     $totalQuery->where('tbl_sanluong.SanLuong_Tram', 'like', "%$search%");
        // }

        // $totalQuery->whereNotNull('tbl_sanluong.ten_hinh_anh_da_xong')
        //     ->where('tbl_sanluong.ten_hinh_anh_da_xong', '<>', '');

        // $totalGia = $totalQuery->value('total_gia');

        // Truy vấn để tính tổng giá trị và số trạm theo khu vực
        $khuVucQuery = DB::table('tbl_sanluong')
            ->select(
                'tbl_tinh.ten_khu_vuc',
                DB::raw('COUNT(DISTINCT tbl_sanluong.SanLuong_Tram) as so_tram'),
                DB::raw('SUM(tbl_sanluong.SanLuong_Gia) as tong_san_luong')
            )
            ->leftJoin('tbl_tinh', DB::raw('LEFT(tbl_sanluong.SanLuong_Tram, 3)'), '=', 'tbl_tinh.ma_tinh')
            ->groupBy('tbl_tinh.ten_khu_vuc');
        if (count($days) > 0) {
            $khuVucQuery->whereIn('tbl_sanluong.SanLuong_Ngay', $days);
        }
        if (!empty($search)) {
            $khuVucQuery->where('tbl_sanluong.SanLuong_Tram', 'like', "%$search%");
        }
        $khuVucQuery->whereNotNull('tbl_sanluong.ten_hinh_anh_da_xong')
            ->where('tbl_sanluong.ten_hinh_anh_da_xong', '<>', '');
        $khuVucData = $khuVucQuery->get();
        // dd($khuVucData);

        $data = $query->simplePaginate(100);

        return view('thong_ke.thongke_tram_filter', compact('data', 'khuVucData', 'days', 'search'));
    }

    // public function getDayTramFilter(Request $request)
    // {
    //     $month = $request->input('thang', date('n'));
    //     $year = $request->input('nam', date('Y'));
    //     $days = DB::table('tbl_sanluong')
    //         ->select('SanLuong_Ngay')
    //         ->whereRaw("YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $year AND MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $month")
    //         ->distinct()
    //         ->orderBy('SanLuong_Ngay')
    //         ->get()
    //         ->pluck('SanLuong_Ngay');

    //     return response()->json($days);
    // }
}

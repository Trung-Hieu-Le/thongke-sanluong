<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
class TinhSanLuongController extends Controller
{
    public function viewSanLuongTram(Request $request)
{
    if (!$request->session()->has('username')) {
        return redirect('login');
    }

    $ma_tram = $request->ma_tram;
    $daysString = $request->input('days', date('dmY'));
    $days = [];

    if (!empty($daysString)) {
        $days = explode(',', $daysString);
        $days = array_map(function ($day) {
            return str_replace('-', '', $day);
        }, $days);
    }
    $daysList = "'" . implode("','", $days) . "'";

    $perPage = 100; // Number of items per page

    // Fetching paginated data from tbl_sanluong
    $sanluongData = DB::table('tbl_sanluong')
        ->select('HopDong_Id', 'SanLuong_Tram', DB::raw("DATE_FORMAT(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), '%d/%m/%Y') as SanLuong_Ngay"), 'SanLuong_Gia', 'SanLuong_TenHangMuc', DB::raw("CASE WHEN ten_hinh_anh_da_xong <> '' THEN 1 ELSE 0 END as SoLuong"))
        ->where('SanLuong_Tram', $ma_tram)
        ->whereIn(DB::raw("DATE_FORMAT(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), '%d%m%Y')"), $days)
        ->simplePaginate($perPage, ['*'], 'sanluong_page');

    // Fetching paginated data from tbl_sanluong_khac
    $sanluongKhacData = DB::table('tbl_sanluong_khac')
        ->select('HopDong_Id', 'SanLuong_Tram', DB::raw("DATE_FORMAT(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), '%d/%m/%Y') as SanLuong_Ngay"), 'SanLuong_Gia', DB::raw("'Hạng mục khác' as SanLuong_TenHangMuc"), DB::raw("1 as SoLuong"))
        ->where('SanLuong_Tram', $ma_tram)
        ->whereIn(DB::raw("DATE_FORMAT(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), '%d%m%Y')"), $days)
        ->simplePaginate($perPage, ['*'], 'sanluong_khac_page');

    // Fetching and transforming paginated data from tbl_sanluong_thaolap
    $sanluongThaolapData = DB::table('tbl_sanluong_thaolap')
        ->select('tbl_sanluong_thaolap.HopDong_Id', 'ThaoLap_MaTram as SanLuong_Tram', DB::raw("DATE_FORMAT(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), '%d/%m/%Y') as SanLuong_Ngay"), 
                    DB::raw("CASE
                        WHEN tbl_sanluong_thaolap.HopDong_Id = 3 AND CongViec_Ten = 'Anten' THEN 'Anten'
                        WHEN tbl_sanluong_thaolap.HopDong_Id = 3 AND CongViec_Ten = 'RRU' THEN 'RRU'
                        WHEN tbl_sanluong_thaolap.HopDong_Id = 3 AND CongViec_Ten = 'Tủ thiết bị' THEN 'Tủ thiết bị'
                        WHEN tbl_sanluong_thaolap.HopDong_Id = 3 AND CongViec_Ten = 'Cáp nguồn' THEN 'Cáp nguồn'
                        ELSE 'Hạng mục tháo lắp khác' 
                    END as SanLuong_TenHangMuc"), 
                    DB::raw("CASE 
                        WHEN tbl_sanluong_thaolap.HopDong_Id = 3 AND CongViec_Ten = 'Anten' THEN ThaoLap_Anten
                        WHEN tbl_sanluong_thaolap.HopDong_Id = 3 AND CongViec_Ten = 'RRU' THEN ThaoLap_RRU
                        WHEN tbl_sanluong_thaolap.HopDong_Id = 3 AND CongViec_Ten = 'Tủ thiết bị' THEN ThaoLap_TuThietBi
                        WHEN tbl_sanluong_thaolap.HopDong_Id = 3 AND CongViec_Ten = 'Cáp nguồn' THEN ThaoLap_CapNguon
                    ELSE 0 END as SoLuong"), 
                    DB::raw("CASE
                        WHEN tbl_sanluong_thaolap.HopDong_Id = 3 AND CongViec_Ten = 'Anten' THEN CongViec_DonGia 
                        WHEN tbl_sanluong_thaolap.HopDong_Id = 3 AND CongViec_Ten = 'RRU' THEN CongViec_DonGia
                        WHEN tbl_sanluong_thaolap.HopDong_Id = 3 AND CongViec_Ten = 'Tủ thiết bị' THEN CongViec_DonGia
                        WHEN tbl_sanluong_thaolap.HopDong_Id = 3 AND CongViec_Ten = 'Cáp nguồn' THEN CongViec_DonGia 
                    ELSE 0 END as SanLuong_Gia"))
        ->leftJoin('tbl_hopdong_congviec', function ($join) {
            $join->on('tbl_sanluong_thaolap.HopDong_Id', '=', 'tbl_hopdong_congviec.HopDong_Id')
                ->whereIn('tbl_hopdong_congviec.CongViec_Ten', ['Anten', 'RRU', 'Tủ thiết bị', 'Cáp nguồn']);
        })
        ->where('tbl_sanluong_thaolap.ThaoLap_MaTram', $ma_tram)
        ->whereIn(DB::raw("DATE_FORMAT(STR_TO_DATE(tbl_sanluong_thaolap.ThaoLap_Ngay, '%d/%m/%Y'), '%d%m%Y')"), $days)
        ->simplePaginate($perPage, ['*'], 'sanluong_thaolap_page');

    // Merging data from all tables
    $allData = new Collection;
    $allData = $allData->merge($sanluongData->items());
    $allData = $allData->merge($sanluongKhacData->items());
    $allData = $allData->merge($sanluongThaolapData->items());

    // Create a paginator for the merged data
    $currentPage = LengthAwarePaginator::resolveCurrentPage();
    $pagedData = new LengthAwarePaginator(
        $allData->forPage($currentPage, $perPage),
        $allData->count(),
        $perPage,
        $currentPage,
        ['path' => LengthAwarePaginator::resolveCurrentPath()]
    );

    // Calculate total amount
    $totalThanhTien = $allData->sum(function ($item) {
        return $item->SanLuong_Gia * $item->SoLuong;
    });

    return view('san_luong.sanluong_tram_view', compact('pagedData', 'ma_tram', 'days', 'totalThanhTien'));
}




    public function viewHinhAnhTram(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }

        $ma_tram = $request->ma_tram;
        $daysString = $request->input('days', date('dmY'));
        $days = [];
        if (!empty($daysString)) {
            $days = explode(',', $daysString);
            $days = array_map(function ($day) {
                return str_replace('-', '', $day);
            }, $days);
        }

        $query = DB::table('tbl_hinhanh')
            ->where('ma_tram', $ma_tram)
            ->whereIn(DB::raw("DATE_FORMAT(STR_TO_DATE(thoi_gian_chup, '%d%m%Y'), '%d%m%Y')"), $days)
            ->select('ten_hang_muc', 'ten_anh_chuan_bi', 'ten_anh_da_xong');

        $userid = $request->session()->get('userid');
        if (in_array($userid, [0, 1])) {
            $query->where('user_id', $userid);
        }

        $rawData = $query->get();
        $groupedData = [];

        foreach ($rawData as $item) {
            if (!isset($groupedData[$item->ten_hang_muc])) {
                $groupedData[$item->ten_hang_muc] = [
                    'ten_hang_muc' => $item->ten_hang_muc,
                    'anh_chuan_bi' => [],
                    'anh_da_xong' => [],
                ];
            }

            if (!empty($item->ten_anh_chuan_bi)) {
                $groupedData[$item->ten_hang_muc]['anh_chuan_bi'][] = $item->ten_anh_chuan_bi;
            }

            if (!empty($item->ten_anh_da_xong)) {
                $groupedData[$item->ten_hang_muc]['anh_da_xong'][] = $item->ten_anh_da_xong;
            }
        }

        return view('san_luong.hinhanh_tram_view', compact('ma_tram', 'groupedData', 'days'));
    }
}
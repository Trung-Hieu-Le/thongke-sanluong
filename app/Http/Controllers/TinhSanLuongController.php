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
    //TODO: Sản lượng cùng hợp đồng, khác hạng mục (VD: bgg0007 ngày 16/7, 15/7)
    if (!$request->session()->has('username')) {
        return redirect('login');
    }

    $ma_tram = $request->ma_tram;
    $daysString = $request->input('days', date('dmY'));
    $sohopdong = $request->input('sohopdong', '');
    $days = [];

    if (!empty($daysString)) {
        $days = explode(',', $daysString);
        $days = array_map(function ($day) {
            return str_replace('-', '', $day);
        }, $days);
    }
    $daysList = "'" . implode("','", $days) . "'";
    $perPage = 100;

    $userId = $request->session()->get('userid');
    $userRole = $request->session()->get('role');
    $userKhuVuc = DB::table('tbl_user')->where('user_id', $userId)->value('user_khuvuc');

    //TODO: Hiện không lấy ngày đầu tiên (do SanLuong_Ngay dạng text)
    $sanluongDataQuery = DB::table('tbl_sanluong')
    ->leftJoin('tbl_tram', "tbl_sanluong.SanLuong_Tram", '=', 'tbl_tram.ma_tram')
    ->leftJoin('tbl_hopdong', 'tbl_sanluong.HopDong_Id', '=', 'tbl_hopdong.HopDong_Id')
    ->select(
        'tbl_sanluong.HopDong_Id',
        'tbl_sanluong.SanLuong_Tram',
        'tbl_sanluong.SanLuong_TenHangMuc',
        DB::raw("DATE_FORMAT(STR_TO_DATE(MIN(tbl_sanluong.SanLuong_Ngay), '%d%m%Y'), '%d/%m/%Y') as SanLuong_Ngay"),
        DB::raw("
            CASE 
                WHEN $userRole IN (0, 1) THEN 0
                ELSE COALESCE(MAX(tbl_sanluong.SanLuong_Gia), 0)
            END as SanLuong_Gia
        "),
        DB::raw("MAX(CASE WHEN tbl_sanluong.ten_hinh_anh_da_xong <> '' THEN 1 ELSE 0 END) as SoLuong"),
        DB::raw("MAX(CASE WHEN tbl_sanluong.ten_hinh_anh_da_xong <> '' THEN 'Đã thi công' ELSE 'Đã khảo sát' END) as TrangThai")
    )
    ->where('tbl_sanluong.SanLuong_Tram', $ma_tram)
    ->whereIn(DB::raw("DATE_FORMAT(STR_TO_DATE(tbl_sanluong.SanLuong_Ngay, '%d%m%Y'), '%d%m%Y')"), $days)
    ->groupBy('tbl_sanluong.SanLuong_Tram', 'tbl_sanluong.SanLuong_TenHangMuc', 'tbl_sanluong.HopDong_Id');

    // Fetching and transforming paginated data from tbl_sanluong_thaolap
    $sanluongThaolapDataQuery = DB::table('tbl_sanluong_thaolap')
        ->leftJoin('tbl_tram', "tbl_sanluong_thaolap.ThaoLap_MaTram", '=', 'tbl_tram.ma_tram')
        ->join('tbl_hopdong', 'tbl_sanluong_thaolap.HopDong_Id', '=', 'tbl_hopdong.HopDong_Id')
        ->select('tbl_sanluong_thaolap.HopDong_Id', 'ThaoLap_MaTram as SanLuong_Tram', DB::raw("DATE_FORMAT(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), '%d/%m/%Y') as SanLuong_Ngay"), 
            DB::raw("'Anten' as SanLuong_TenHangMuc"), 
            DB::raw("CASE WHEN ThaoLap_Anten IS NULL OR ThaoLap_Anten = '' THEN 0 ELSE ThaoLap_Anten END as SoLuong"), 
            DB::raw("
                CASE 
                    WHEN $userRole IN (0, 1) THEN 0 
                    ELSE CASE 
                        WHEN DonGia_Anten IS NULL OR DonGia_Anten = '' 
                        THEN 0 
                        ELSE DonGia_Anten 
                    END 
                END as SanLuong_Gia
            "),
            DB::raw("'Đã thi công' as TrangThai"))
        ->where('tbl_sanluong_thaolap.ThaoLap_MaTram', $ma_tram)
        ->whereIn(DB::raw("DATE_FORMAT(STR_TO_DATE(tbl_sanluong_thaolap.ThaoLap_Ngay, '%d/%m/%Y'), '%d%m%Y')"), $days)
        ->when(!empty($sohopdong), function ($query) use ($sohopdong) {
            return $query->where('tbl_hopdong.HopDong_SoHopDong', $sohopdong);
        })
        ->unionAll(
            DB::table('tbl_sanluong_thaolap')
            ->leftJoin('tbl_tram', "tbl_sanluong_thaolap.ThaoLap_MaTram", '=', 'tbl_tram.ma_tram')
            ->join('tbl_hopdong', 'tbl_sanluong_thaolap.HopDong_Id', '=', 'tbl_hopdong.HopDong_Id')
            ->select('tbl_sanluong_thaolap.HopDong_Id', 'ThaoLap_MaTram as SanLuong_Tram', DB::raw("DATE_FORMAT(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), '%d/%m/%Y') as SanLuong_Ngay"), 
                DB::raw("'RRU' as SanLuong_TenHangMuc"), 
                DB::raw("CASE WHEN ThaoLap_RRU IS NULL OR ThaoLap_RRU = '' THEN 0 ELSE ThaoLap_RRU END as SoLuong"), 
                DB::raw("
                    CASE 
                        WHEN $userRole IN (0, 1) THEN 0 
                        ELSE CASE 
                            WHEN DonGia_RRU IS NULL OR DonGia_RRU = '' 
                            THEN 0 
                            ELSE DonGia_RRU 
                        END 
                    END as SanLuong_Gia
                "), 
                DB::raw("'Đã thi công' as TrangThai"))
            ->where('tbl_sanluong_thaolap.ThaoLap_MaTram', $ma_tram)
            ->whereIn(DB::raw("DATE_FORMAT(STR_TO_DATE(tbl_sanluong_thaolap.ThaoLap_Ngay, '%d/%m/%Y'), '%d%m%Y')"), $days)
            ->when(!empty($sohopdong), function ($query) use ($sohopdong) {
                return $query->where('tbl_hopdong.HopDong_SoHopDong', $sohopdong);
            })
        )
        ->unionAll(
            DB::table('tbl_sanluong_thaolap')
            ->leftJoin('tbl_tram', "tbl_sanluong_thaolap.ThaoLap_MaTram", '=', 'tbl_tram.ma_tram')
            ->join('tbl_hopdong', 'tbl_sanluong_thaolap.HopDong_Id', '=', 'tbl_hopdong.HopDong_Id')
            ->select('tbl_sanluong_thaolap.HopDong_Id', 'ThaoLap_MaTram as SanLuong_Tram', DB::raw("DATE_FORMAT(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), '%d/%m/%Y') as SanLuong_Ngay"), 
                DB::raw("'Tủ thiết bị' as SanLuong_TenHangMuc"), 
                DB::raw("CASE WHEN ThaoLap_TuThietBi IS NULL OR ThaoLap_TuThietBi = '' THEN 0 ELSE ThaoLap_TuThietBi END as SoLuong"), 
                DB::raw("
                    CASE 
                        WHEN $userRole IN (0, 1) THEN 0 
                        ELSE CASE 
                            WHEN DonGia_TuThietBi IS NULL OR DonGia_TuThietBi = '' 
                            THEN 0 
                            ELSE DonGia_TuThietBi 
                        END 
                    END as SanLuong_Gia
                "), 
                DB::raw("'Đã thi công' as TrangThai"))
            ->where('tbl_sanluong_thaolap.ThaoLap_MaTram', $ma_tram)
            ->whereIn(DB::raw("DATE_FORMAT(STR_TO_DATE(tbl_sanluong_thaolap.ThaoLap_Ngay, '%d/%m/%Y'), '%d%m%Y')"), $days)
            ->when(!empty($sohopdong), function ($query) use ($sohopdong) {
                return $query->where('tbl_hopdong.HopDong_SoHopDong', $sohopdong);
            })
        )
        ->unionAll(
            DB::table('tbl_sanluong_thaolap')
            ->leftJoin('tbl_tram', "tbl_sanluong_thaolap.ThaoLap_MaTram", '=', 'tbl_tram.ma_tram')
            ->join('tbl_hopdong', 'tbl_sanluong_thaolap.HopDong_Id', '=', 'tbl_hopdong.HopDong_Id')
            ->select('tbl_sanluong_thaolap.HopDong_Id', 'ThaoLap_MaTram as SanLuong_Tram', DB::raw("DATE_FORMAT(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), '%d/%m/%Y') as SanLuong_Ngay"), 
                DB::raw("'Cáp nguồn' as SanLuong_TenHangMuc"), 
                DB::raw("CASE WHEN ThaoLap_CapNguon IS NULL OR ThaoLap_CapNguon = '' THEN 0 ELSE ThaoLap_CapNguon END as SoLuong"), 
                DB::raw("
                    CASE 
                        WHEN $userRole IN (0, 1) THEN 0 
                        ELSE CASE 
                            WHEN DonGia_CapNguon IS NULL OR DonGia_CapNguon = '' 
                            THEN 0 
                            ELSE DonGia_CapNguon 
                        END 
                    END as SanLuong_Gia
                "), 
                DB::raw("'Đã thi công' as TrangThai"))
            ->where('tbl_sanluong_thaolap.ThaoLap_MaTram', $ma_tram)
            ->whereIn(DB::raw("DATE_FORMAT(STR_TO_DATE(tbl_sanluong_thaolap.ThaoLap_Ngay, '%d/%m/%Y'), '%d%m%Y')"), $days)
            ->when(!empty($sohopdong), function ($query) use ($sohopdong) {
                return $query->where('tbl_hopdong.HopDong_SoHopDong', $sohopdong);
            })
        );

    $sanluongKiemdinhDataQuery = DB::table('tbl_sanluong_kiemdinh')
        ->leftJoin('tbl_tram', "tbl_sanluong_kiemdinh.KiemDinh_MaTram", '=', 'tbl_tram.ma_tram')
        ->leftJoin('tbl_hopdong', 'tbl_sanluong_kiemdinh.HopDong_Id', '=', 'tbl_hopdong.HopDong_Id')
        ->select('tbl_sanluong_kiemdinh.HopDong_Id', 'tbl_sanluong_kiemdinh.KiemDinh_MaTram', DB::raw("DATE_FORMAT(STR_TO_DATE(tbl_sanluong_kiemdinh.KiemDinh_Ngay, '%d/%m/%Y'), '%d/%m/%Y') as SanLuong_Ngay"), 
        DB::raw("CASE 
                    WHEN $userRole = 0 OR $userRole = 1 THEN 0 
                    WHEN tbl_sanluong_kiemdinh.KiemDinh_DonGia IS NULL OR tbl_sanluong_kiemdinh.KiemDinh_DonGia = '' THEN 0 
                    ELSE tbl_sanluong_kiemdinh.KiemDinh_DonGia 
                 END as SanLuong_Gia"),
        'tbl_sanluong_kiemdinh.KiemDinh_NoiDung as SanLuong_TenHangMuc', 
        DB::raw("1 as SoLuong"), 
        DB::raw("'Đã thi công' as TrangThai"))
        ->where('tbl_sanluong_kiemdinh.KiemDinh_MaTram', $ma_tram)
        ->whereIn(DB::raw("DATE_FORMAT(STR_TO_DATE(tbl_sanluong_kiemdinh.KiemDinh_Ngay, '%d/%m/%Y'), '%d%m%Y')"), $days);

    // Apply user level filter
    if (!empty($sohopdong)) {

        $sanluongDataQuery->where('tbl_hopdong.HopDong_SoHopDong', $sohopdong);
        $sanluongKiemdinhDataQuery->where('tbl_hopdong.HopDong_SoHopDong', $sohopdong);
    }
    if ($userRole == 0 or $userRole == 1) {
        $sanluongDataQuery
        ->join('tbl_hinhanh', 'tbl_hinhanh.ma_tram', 'tbl_sanluong.SanLuong_Tram')
        ->where('tbl_hinhanh.user_id', $userId);
        $sanluongThaolapDataQuery
        ->join('tbl_hinhanh', 'tbl_hinhanh.ma_tram', 'tbl_sanluong_thaolap.ThaoLap_MaTram')
        ->where('tbl_hinhanh.user_id', $userId);
        $sanluongKiemdinhDataQuery->where('User_Id', $userId);
    }
    // if ($userRole != 3) {
    //     $sanluongDataQuery->where('tbl_tram.khu_vuc', $userKhuVuc);
    //     $sanluongThaolapDataQuery->where('tbl_tram.khu_vuc', $userKhuVuc);
    //     $sanluongKiemdinhDataQuery->where('tbl_tram.khu_vuc', $userKhuVuc);
    // }
    $sanluongData = $sanluongDataQuery->simplePaginate($perPage, ['*'], 'sanluong_page');
    $sanluongThaolapData = $sanluongThaolapDataQuery->simplePaginate($perPage, ['*'], 'sanluong_thaolap_page');
    $sanluongKiemdinhData = $sanluongKiemdinhDataQuery->simplePaginate($perPage, ['*'], 'kiemdinh_page');

    // Merging data from all tables
    $allData = new Collection;
    $allData = $allData->merge($sanluongData->items());
    $allData = $allData->merge($sanluongThaolapData->items());
    $allData = $allData->merge($sanluongKiemdinhData->items());

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
        $userId = $request->session()->get('userid');
        $userRole = $request->session()->get('role');
        $userKhuVuc = DB::table('tbl_user')->where('user_id', $userId)->value('user_khuvuc');

        $query = DB::table('tbl_hinhanh')
            ->leftJoin('tbl_tram', "tbl_hinhanh.ma_tram", '=', 'tbl_tram.ma_tram')
            ->where('tbl_hinhanh.ma_tram', $ma_tram)
            ->whereIn(DB::raw("DATE_FORMAT(STR_TO_DATE(thoi_gian_chup, '%d%m%Y'), '%d%m%Y')"), $days)
            ->select('ten_hang_muc', 'ten_anh_chuan_bi', 'ten_anh_da_xong');

        // $userid = $request->session()->get('role');
        // if (in_array($userRole, [0, 1])) {
        //     $query->where('user_id', $userRole);
        // }
        if ($userRole == 0 or $userRole == 1) {
            $query->where('user_id', $userId);
        }
        // if ($userRole != 3) {
        //     $query->where('tbl_tram.khu_vuc', $userKhuVuc);
        // }

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
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TinhSanLuongFilterController extends Controller
{
    public function indexTramFilter(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        $userId = $request->session()->get('userid');
        $userRole = $request->session()->get('role');
        $userKhuVuc = DB::table('tbl_user')->where('user_id', $userId)->value('user_khuvuc');

        // Lấy thông tin ngày
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
        // $userKhuVucCondition = '';
        // if ($userRole !== 3) {
        //     $userKhuVucCondition = "AND tbl_tram.khu_vuc = '$userKhuVuc'";
        // }

        $page = request()->get('page', 1);
        $perPage = 100; // Number of items per page

        // TODO: Nếu cùng mt, hđ, hm nhưng khác giá thì sao?? some sl đã có từ trc nhưng cột +1, giá+0
        if ($userRole != 0 && $userRole != 1) {
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
                DB::raw('SUM(tbl_sanluong.SanLuong_Gia) as SanLuong_Gia')
            )
            ->whereNot('ten_hinh_anh_da_xong', "")
            ->whereRaw("1 $dayCondition $searchCondition $searchConditionHopDong $searchConditionKhuVuc")
            ->groupBy('tbl_sanluong.SanLuong_Tram', 'khu_vuc', 'tbl_tram.ma_tinh', 'tbl_hopdong.HopDong_SoHopDong')
            // ->orderBy('tbl_sanluong.SanLuong_Tram', 'asc')
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
                DB::raw('SUM(ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon) as SanLuong_Gia')
            )
            ->whereRaw("1 $thaoLapDayCondition $searchCondition2 $searchConditionHopDong $searchConditionKhuVuc")
            ->groupBy('tbl_sanluong_thaolap.ThaoLap_MaTram', 'tbl_tram.khu_vuc', 'tbl_tram.ma_tinh', 'tbl_hopdong.HopDong_SoHopDong')
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
                DB::raw('SUM(KiemDinh_DonGia) as SanLuong_Gia')
            )
            ->whereRaw("1 $kiemDinhDayCondition $searchCondition3 $searchConditionHopDong $searchConditionKhuVuc")
            ->groupBy('tbl_sanluong_kiemdinh.KiemDinh_MaTram', 'tbl_tram.khu_vuc', 'tbl_tram.ma_tinh', 'tbl_hopdong.HopDong_SoHopDong')
            // ->orderBy('tbl_sanluong.SanLuong_Tram', 'asc')
            ->get();
            //TODO: thêm userRole

            $hinhanhLeftData = DB::table('tbl_hinhanh')
                ->distinct()
                ->select(DB::raw('
                    LEFT(tbl_hinhanh.ma_tram, 3) as ma_tinh,
                    UPPER(tbl_hinhanh.ma_tram) as SanLuong_Tram,
                    HopDong_SoHopDong,
                    0 as SanLuong_Gia,
                    FirstTram.khu_vuc
                '))
                ->whereRaw("1 $hinhAnhDayCondition $searchCondition4 $searchConditionHopDong $searchConditionKhuVuc2")
                ->whereNotIn('tbl_hinhanh.ma_tram', $sanluongData->pluck('SanLuong_Tram'))
                ->join(DB::raw('(
                    SELECT 
                        ma_tram,
                        khu_vuc,
                        ROW_NUMBER() OVER (PARTITION BY ma_tram) as rn
                    FROM tbl_tram
                ) AS FirstTram'), function ($join) {
                    $join->on('tbl_hinhanh.ma_tram', '=', 'FirstTram.ma_tram')
                        ->where('FirstTram.rn', '=', 1);
                })
                ->leftJoin('tbl_sanluong', 'tbl_hinhanh.ma_tram', 'tbl_sanluong.SanLuong_Tram')
                ->leftJoin('tbl_hopdong', 'tbl_sanluong.HopDong_Id', 'tbl_hopdong.HopDong_Id')
                ->orderBy('SanLuong_Tram')
                ->get();


            $mergedData = $sanluongData->merge($thaolapData)->merge($kiemdinhData)->merge($hinhanhLeftData)->sortBy('SanLuong_Tram');
        } else {
            $mergedData = DB::table('tbl_sanluong')
            ->leftJoin('tbl_tram', function ($join) {
                $join->on('tbl_sanluong.SanLuong_Tram', '=', 'tbl_tram.ma_tram')
                     ->on('tbl_sanluong.HopDong_Id', '=', 'tbl_tram.hopdong_id')
                     ->whereExists(function ($query) {
                         $query->select(DB::raw(1))
                               ->from('tbl_hinhanh')
                               ->whereColumn('tbl_hinhanh.ma_tram', 'tbl_sanluong.SanLuong_Tram');
                               //TODO: loc cho nay
                     });
            })
            ->join('tbl_hopdong', 'tbl_sanluong.HopDong_Id', '=', 'tbl_hopdong.HopDong_Id')
            ->select(
                'tbl_sanluong.SanLuong_Tram',
                DB::raw('COALESCE(tbl_tram.khu_vuc, (SELECT khu_vuc FROM tbl_tram WHERE tbl_sanluong.SanLuong_Tram = tbl_tram.ma_tram LIMIT 1)) AS khu_vuc'),
                'tbl_tram.ma_tinh',
                'tbl_hopdong.HopDong_SoHopDong',
                DB::raw('0 as SanLuong_Gia')
            )
            ->whereNot('ten_hinh_anh_da_xong', "")
            ->whereRaw("1 $dayCondition $searchCondition $searchConditionHopDong $searchConditionKhuVuc")
            ->groupBy('tbl_sanluong.SanLuong_Tram', 'khu_vuc', 'tbl_tram.ma_tinh', 'tbl_hopdong.HopDong_SoHopDong')
            ->orderBy('tbl_sanluong.SanLuong_Tram', 'asc')
            ->get();
        }
        
        
        // if ($userRole == 0 || $userRole == 1) {
        //     $mergedData = $mergedData->filter(function ($item) use ($userId) {
        //         return DB::table('tbl_hinhanh')
        //             ->where('ma_tram', $item->SanLuong_Tram)
        //             ->where('user_id', $userId)
        //             ->exists();
        //     });
        // }

        $khuVucData = $mergedData->groupBy('khu_vuc')->map(function ($items, $khu_vuc) {
            return [
                'ten_khu_vuc' => $khu_vuc,
                'so_tram' => $items->count(), // Số lượng trạm trong khu vực
                'tong_san_luong' => $items->sum('SanLuong_Gia') // Tổng giá trị sản lượng
            ];
        })->values()->toArray();

        $currentPageItems = $mergedData->slice(($page - 1) * $perPage, $perPage)->values();

        $pagedData = new LengthAwarePaginator(
            $currentPageItems, // Các mục của trang hiện tại
            $mergedData->count(), // Tổng số mục
            $perPage, // Số mục mỗi trang
            $page, // Trang hiện tại
            ['path' => request()->url(), 'query' => request()->query()] // Đường dẫn và query hiện tại
        );
        $hopdongs = DB::table('tbl_hopdong')
            ->select('HopDong_Id', 'HopDong_SoHopDong', 'HopDong_TenHopDong')
            ->get()->keyBy('HopDong_Id')->toArray();


        return view('thong_ke.thongke_tram_filter', compact('pagedData', 'khuVucData', 'days', 'searchMaTram', 'searchHopDong', 'searchKhuVuc', 'hopdongs'));
    }
}

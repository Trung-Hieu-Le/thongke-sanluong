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
        $dayCondition = count($days) > 0 ? "AND SanLuong_Ngay IN (" . implode(',', $days) . ")" : "";
        $hinhAnhDayCondition = count($days) > 0 ? "AND thoi_gian_chup IN ('" . implode("','", $days) . "')" : '';
        $thaoLapDayCondition = count($days) > 0 ? "AND DATE_FORMAT(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y'), '%d%m%Y') IN (" . implode(',', $days) . ")" : "";
        $kiemDinhDayCondition = count($days) > 0 ? "AND DATE_FORMAT(STR_TO_DATE(KiemDinh_Ngay, '%d/%m/%Y'), '%d%m%Y') IN (" . implode(',', $days) . ")" : "";
        $searchCondition = !empty($searchMaTram) ? "AND SanLuong_Tram LIKE '%$searchMaTram%'" : "";
        $searchCondition2 = !empty($searchMaTram) ? "AND ThaoLap_MaTram LIKE '%$searchMaTram%'" : "";
        $searchCondition3 = !empty($searchMaTram) ? "AND KiemDinh_MaTram LIKE '%$searchMaTram%'" : "";
        $searchCondition4 = !empty($searchMaTram) ? "AND ma_tram LIKE '%$searchMaTram%'" : '';
        $searchConditionHopDong = !empty($searchHopDong) ? "AND tbl_hopdong.HopDong_SoHopDong LIKE '%$searchHopDong%'" : "";
        $searchConditionKhuVuc = !empty($searchKhuVuc) ? "AND tbl_tinh.ten_khu_vuc LIKE '%$searchKhuVuc%'" : "";
        $userKhuVucCondition = '';
        // if ($userRole !== 3) {
        //     $userKhuVucCondition = "AND tbl_tinh.ten_khu_vuc = '$userKhuVuc'";
        // }

        $page = request()->get('page', 1);
        $perPage = 100; // Number of items per page

        // Truy vấn dữ liệu chi tiết từ ba bảng
        $sanluongData = DB::table(DB::raw("
            (
                SELECT 
                    LEFT(SanLuong_Tram, 3) as ma_tinh,
                    SanLuong_Tram,
                    tbl_hopdong.HopDong_SoHopDong,
                    SUM(
                        CASE 
                            WHEN $userRole IN (0, 1) THEN 0 
                            ELSE SanLuong_Gia 
                        END
                    ) as SanLuong_Gia,
                    tbl_tinh.ten_khu_vuc
                FROM (
                    SELECT 
                        UPPER(SanLuong_Tram) as SanLuong_Tram,
                        MAX(SanLuong_Gia) as SanLuong_Gia,  -- Chỉ lấy 1 giá trị SanLuong_Gia cho mỗi trạm và hạng mục
                        HopDong_Id
                    FROM 
                        tbl_sanluong
                    WHERE ten_hinh_anh_da_xong <> ''
                        $dayCondition
                    GROUP BY 
                        SanLuong_Tram,
                        SanLuong_TenHangMuc,  -- Nhóm theo trạm và hạng mục
                        HopDong_Id
                    ORDER BY 
                        SanLuong_Tram,
                        SanLuong_TenHangMuc
                ) AS subquery_sanluong
                JOIN tbl_tinh ON LEFT(subquery_sanluong.SanLuong_Tram, 3) = tbl_tinh.ma_tinh
                LEFT JOIN tbl_hopdong ON subquery_sanluong.HopDong_Id = tbl_hopdong.HopDong_Id
                WHERE 1
                    $searchCondition
                    $searchConditionHopDong
                    $searchConditionKhuVuc
                    $userKhuVucCondition
                GROUP BY SanLuong_Tram, tbl_tinh.ten_khu_vuc, tbl_hopdong.HopDong_SoHopDong
            ) as sanluong_subquery
        "))
        ->select('ma_tinh', 'SanLuong_Tram', 'HopDong_SoHopDong', DB::raw('SUM(SanLuong_Gia) as SanLuong_Gia'), 'ten_khu_vuc')
        ->groupBy('ma_tinh', 'SanLuong_Tram', 'ten_khu_vuc', 'HopDong_SoHopDong')
        ->orderBy('SanLuong_Tram', 'asc')
        ->get();

        $thaoLapKiemDinhData = DB::table(DB::raw("
            (
                SELECT 
                    LEFT(ThaoLap_MaTram, 3) as ma_tinh,
                    ThaoLap_MaTram as SanLuong_Tram,
                    tbl_hopdong.HopDong_SoHopDong,
                    SUM(
                        CASE 
                            WHEN $userRole IN (0, 1) THEN 0 
                            ELSE ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon 
                        END
                    ) as SanLuong_Gia,
                    tbl_tinh.ten_khu_vuc
                FROM tbl_sanluong_thaolap
                JOIN tbl_tinh ON LEFT(tbl_sanluong_thaolap.ThaoLap_MaTram, 3) = tbl_tinh.ma_tinh
                LEFT JOIN tbl_hopdong ON tbl_sanluong_thaolap.HopDong_Id = tbl_hopdong.HopDong_Id
                WHERE 1
                $thaoLapDayCondition
                $searchCondition2
                $searchConditionHopDong
                $searchConditionKhuVuc
                $userKhuVucCondition
                GROUP BY ThaoLap_MaTram, tbl_tinh.ten_khu_vuc, tbl_hopdong.HopDong_SoHopDong

                UNION ALL
            
                SELECT 
                    LEFT(KiemDinh_MaTram, 3) as ma_tinh,
                    KiemDinh_MaTram as SanLuong_Tram,
                    tbl_hopdong.HopDong_SoHopDong,
                    SUM(
                        CASE 
                            WHEN $userRole IN (0, 1) THEN 0 
                            ELSE KiemDinh_DonGia 
                        END
                    ) as SanLuong_Gia,
                    tbl_tinh.ten_khu_vuc
                FROM tbl_sanluong_kiemdinh
                JOIN tbl_tinh ON LEFT(tbl_sanluong_kiemdinh.KiemDinh_MaTram, 3) = tbl_tinh.ma_tinh
                LEFT JOIN tbl_hopdong ON tbl_sanluong_kiemdinh.HopDong_Id = tbl_hopdong.HopDong_Id
                WHERE 1
                $kiemDinhDayCondition
                $searchCondition3
                $searchConditionHopDong
                $searchConditionKhuVuc
                $userKhuVucCondition
                GROUP BY KiemDinh_MaTram, tbl_tinh.ten_khu_vuc, tbl_hopdong.HopDong_SoHopDong
            ) as thaolap_kiemdinh_subquery
        "))
        ->select('ma_tinh', 'SanLuong_Tram', 'HopDong_SoHopDong', DB::raw('SUM(SanLuong_Gia) as SanLuong_Gia'), 'ten_khu_vuc')
        ->groupBy('ma_tinh', 'SanLuong_Tram', 'ten_khu_vuc', 'HopDong_SoHopDong')
        ->orderBy('SanLuong_Tram', 'asc')
        ->get();

        $hinhanhLeftData = DB::table('tbl_hinhanh')
        ->distinct()
        ->select(DB::raw('LEFT(ma_tram, 3) as ma_tinh,
                UPPER(ma_tram) as SanLuong_Tram,
                "" as HopDong_SoHopDong,
                0 as SanLuong_Gia,
                tbl_tinh.ten_khu_vuc'))
        // ->when($hinhAnhDayCondition || $searchCondition4, function ($query) use ($hinhAnhDayCondition, $searchCondition4) {
        //     if ($hinhAnhDayCondition && $searchCondition4) {
        //         return $query->whereRaw("($hinhAnhDayCondition) AND ($searchCondition4)");
        //     } elseif ($hinhAnhDayCondition) {
        //         return $query->whereRaw($hinhAnhDayCondition);
        //     } elseif ($searchCondition4) {
        //         return $query->whereRaw($searchCondition4);
        //     }
        // })
        ->whereRaw("1 $hinhAnhDayCondition $searchCondition4 $searchConditionHopDong $searchConditionKhuVuc $userKhuVucCondition")
        ->whereNotIn('ma_tram', $sanluongData->pluck('SanLuong_Tram'))
        ->join('tbl_tinh', DB::raw('LEFT(ma_tram, 3)'), 'tbl_tinh.ma_tinh')
        ->leftJoin('tbl_sanluong', 'ma_tram', 'tbl_sanluong.SanLuong_Tram')
        ->leftJoin('tbl_hopdong', 'tbl_sanluong.HopDong_Id', 'tbl_hopdong.HopDong_Id')
        ->orderBy('SanLuong_Tram')
        ->get();


        $mergedData = $sanluongData->merge($thaoLapKiemDinhData)->merge($hinhanhLeftData)->sortBy('SanLuong_Tram');
        if ($userRole == 0 || $userRole == 1) {
            $mergedData = $mergedData->filter(function ($item) use ($userId) {
                return DB::table('tbl_hinhanh')
                    ->where('ma_tram', $item->SanLuong_Tram)
                    ->where('user_id', $userId)
                    ->exists();
            });
        }
        
        $khuVucData = $mergedData->groupBy('ten_khu_vuc')->map(function ($items, $khu_vuc) {
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

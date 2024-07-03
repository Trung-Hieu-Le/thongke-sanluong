<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SanLuongTramController extends Controller
{
    public function indexTram(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        $maTinhChose = $request->ma_tinh;
        return view('thong_ke.thongke_tram', compact('maTinhChose'));
    }
    public function thongKeTram(Request $request)
    {
        $maTinh = $request->ma_tinh;
        $ngayChon = $request->input('ngay');
        if (is_null($ngayChon) || $ngayChon === '') {
            $ngayChon = date('Y-m-d');
        }

        $results = DB::table('tbl_sanluong')
            ->select(
                'SanLuong_Tram',
                DB::raw("
                SUM(CASE WHEN DATE(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = DATE('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as ngay,
                SUM(CASE WHEN WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = WEEK('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as tuan,
                SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = MONTH('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as thang,
                SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = QUARTER('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as quy,
                SUM(CASE WHEN YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = YEAR('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as nam
            ")
            )
            ->where('SanLuong_Tram', 'LIKE', "$maTinh%")
            ->groupBy('SanLuong_Tram')
            ->orderBy('SanLuong_Tram')
            ->get();

        return response()->json($results);
    }

    public function thongKeTramTongQuat(Request $request)
{
    $maTinh = $request->ma_tinh;
    $currentYear = date('Y');

    // Truy vấn SQL để lấy tất cả dữ liệu cần thiết trong một lần từ 3 bảng
    $query = "
        SELECT
            SanLuong_Tram,
            SUM(SanLuong_Gia) as total_nam,
            SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 1 THEN SanLuong_Gia ELSE 0 END) as total_quy_1,
            SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 2 THEN SanLuong_Gia ELSE 0 END) as total_quy_2,
            SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 3 THEN SanLuong_Gia ELSE 0 END) as total_quy_3,
            SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 4 THEN SanLuong_Gia ELSE 0 END) as total_quy_4,
            SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 1 THEN SanLuong_Gia ELSE 0 END) as total_thang_1,
            SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 2 THEN SanLuong_Gia ELSE 0 END) as total_thang_2,
            SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 3 THEN SanLuong_Gia ELSE 0 END) as total_thang_3,
            SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 4 THEN SanLuong_Gia ELSE 0 END) as total_thang_4,
            SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 5 THEN SanLuong_Gia ELSE 0 END) as total_thang_5,
            SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 6 THEN SanLuong_Gia ELSE 0 END) as total_thang_6,
            SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 7 THEN SanLuong_Gia ELSE 0 END) as total_thang_7,
            SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 8 THEN SanLuong_Gia ELSE 0 END) as total_thang_8,
            SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 9 THEN SanLuong_Gia ELSE 0 END) as total_thang_9,
            SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 10 THEN SanLuong_Gia ELSE 0 END) as total_thang_10,
            SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 11 THEN SanLuong_Gia ELSE 0 END) as total_thang_11,
            SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = 12 THEN SanLuong_Gia ELSE 0 END) as total_thang_12
        FROM (
            SELECT
                SanLuong_Tram,
                SanLuong_Gia,
                SanLuong_Ngay
            FROM tbl_sanluong
            WHERE SanLuong_Tram LIKE ? AND YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = ?
            UNION ALL
            SELECT
                SanLuong_Tram,
                SanLuong_Gia,
                SanLuong_Ngay
            FROM tbl_sanluong_khac
            WHERE SanLuong_Tram LIKE ? AND YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = ?
            UNION ALL
            SELECT
                ThaoLap_MaTram as SanLuong_Tram,
                ThaoLap_SanLuong as SanLuong_Gia,
                ThaoLap_Ngay as SanLuong_Ngay
            FROM tbl_sanluong_thaolap
            WHERE ThaoLap_MaTram LIKE ? AND YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = ?
        ) as subquery
        GROUP BY SanLuong_Tram
        ORDER BY SanLuong_Tram
    ";

    $bindings = [
        "$maTinh%", $currentYear,
        "$maTinh%", $currentYear,
        "$maTinh%", $currentYear
    ];

    $data = DB::select($query, $bindings);

    // Xử lý kết quả
    $results = [];
    foreach ($data as $row) {
        $results[] = [
            'ma_tram' => $row->SanLuong_Tram,
            'tong_san_luong' => [
                'nam' => round($row->total_nam, 4),
                'quy_1' => round($row->total_quy_1, 4),
                'quy_2' => round($row->total_quy_2, 4),
                'quy_3' => round($row->total_quy_3, 4),
                'quy_4' => round($row->total_quy_4, 4),
                'thang_1' => round($row->total_thang_1, 4),
                'thang_2' => round($row->total_thang_2, 4),
                'thang_3' => round($row->total_thang_3, 4),
                'thang_4' => round($row->total_thang_4, 4),
                'thang_5' => round($row->total_thang_5, 4),
                'thang_6' => round($row->total_thang_6, 4),
                'thang_7' => round($row->total_thang_7, 4),
                'thang_8' => round($row->total_thang_8, 4),
                'thang_9' => round($row->total_thang_9, 4),
                'thang_10' => round($row->total_thang_10, 4),
                'thang_11' => round($row->total_thang_11, 4),
                'thang_12' => round($row->total_thang_12, 4),
            ],
        ];
    }

    return response()->json($results);
}


    //TODO: chuyển qua lại giữa các trang sẽ chuyển cả days
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
        // dd($days);

        $query = DB::table('tbl_sanluong')
            ->where('SanLuong_Tram', $ma_tram)
            ->select('SanLuong_Ngay', 'SanLuong_TenHangMuc', 'SanLuong_Gia', DB::raw('1 as SoLuong'));

        if (count($days) > 0) {
            $query->whereIn('SanLuong_Ngay', $days);
        }

        $data = $query->simplePaginate(100);

        return view('san_luong.sanluong_tram_view', compact('data', 'ma_tram', 'days'));
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


    // public function getHinhAnhTram(Request $request)
    // {
    //     $ma_tram = $request->ma_tram;
    //     $daysString = $request->input('days', date('dmY'));
    //     $days = [];

    //     if (!empty($daysString)) {
    //         $days = explode(',', $daysString);
    //         $days = array_map(function ($day) {
    //             return str_replace('-', '', $day);
    //         }, $days);
    //     }

    //     $query = DB::table('tbl_hinhanh')
    //         ->where('ma_tram', $ma_tram);

    //     if (count($days) > 0) {
    //         $query->whereIn('thoi_gian_chup', $days);
    //     }

    //     $query->select('ten_hang_muc', 'ten_anh_chuan_bi', 'ten_anh_da_xong');

    //     $userid = $request->session()->get('userid');
    //     if (in_array($userid, [0, 1])) {
    //         $query->where('user_id', $userid);
    //     }

    //     $rawData = $query->get();
    //     $groupedData = [];

    //     foreach ($rawData as $item) {
    //         if (!isset($groupedData[$item->ten_hang_muc])) {
    //             $groupedData[$item->ten_hang_muc] = [
    //                 'ten_hang_muc' => $item->ten_hang_muc,
    //                 'anh_chuan_bi' => [],
    //                 'anh_da_xong' => [],
    //             ];
    //         }

    //         if (!empty($item->ten_anh_chuan_bi)) {
    //             $groupedData[$item->ten_hang_muc]['anh_chuan_bi'][] = $item->ten_anh_chuan_bi;
    //         }

    //         if (!empty($item->ten_anh_da_xong)) {
    //             $groupedData[$item->ten_hang_muc]['anh_da_xong'][] = $item->ten_anh_da_xong;
    //         }
    //     }

    //     return response()->json(array_values($groupedData));
    // }



    // Thêm sản lượng theo ngày
    public function indexSanLuong()
    {
        $data = DB::table('tbl_sanluong_khac')->simplePaginate(100);
        return view('san_luong.sanluong_view', compact('data'));
    }
    public function addSanLuong(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        $hopdongs = DB::table('tbl_hopdong')
            ->select('HopDong_Id', 'HopDong_SoHopDong', 'HopDong_TenHopDong')
            ->get()->toArray();
        return view('san_luong.sanluong_add', compact('hopdongs'));
    }

    // Lưu dữ liệu vào cơ sở dữ liệu
    public function handleAddSanLuong(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        $date = $request->SanLuong_Ngay
            ? Carbon::createFromFormat('Y-m-d', $request->input('SanLuong_Ngay'))->format('dmY')
            : date('dmY');
        DB::table('tbl_sanluong_khac')->insert([
            'SanLuong_Tram' => $request->SanLuong_Tram,
            'SanLuong_Ngay' => $date,
            'SanLuong_Gia' => $request->SanLuong_Gia,
        ]);

        return redirect()->route('sanluongkhac.add')->with('success', 'Thêm sản lượng thành công.');
    }
    public function editSanLuong(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }

        $sanLuong = DB::table('tbl_sanluong_khac')->where('SanLuong_Id', $request->id)->first();

        if (!$sanLuong) {
            return redirect()->route('sanluongkhac.index')->with('error', 'Không tìm thấy sản lượng');
        }

        $ngayFormatted = Carbon::createFromFormat('dmY', $sanLuong->SanLuong_Ngay)->format('Y-m-d');
        $sanLuong->SanLuong_Ngay = $ngayFormatted;

        return view('san_luong.sanluong_edit', compact('sanLuong'));
    }

    public function handleEditSanLuong(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        $date = $request->SanLuong_Ngay
            ? Carbon::createFromFormat('Y-m-d', $request->input('SanLuong_Ngay'))->format('dmY')
            : date('dmY');
        DB::table('tbl_sanluong_khac')->where('SanLuong_Id', $request->id)->update([
            'SanLuong_Tram' => $request->SanLuong_Tram,
            'SanLuong_Gia' => $request->SanLuong_Gia,
            'SanLuong_Ngay' => $date,
        ]);

        return redirect()->route('sanluongkhac.index')->with('success', 'Cập nhật sản lượng thành công');
    }
    public function deleteSanLuong($id)
    {
        if (!session()->has('username')) {
            return redirect('login');
        }
        DB::table('tbl_sanluong_khac')->where('SanLuong_Id', $id)->delete();
        
        return redirect()->route('sanluongkhac.index')->with('success', 'Xóa sản lượng thành công');
    }
}

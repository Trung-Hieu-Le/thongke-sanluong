<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ThongKeController extends Controller
{
    public function indexKhuVuc(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('/login');
        }
        $role = session('role');
        $userId = session('userid');
        $userKhuVuc = null;
        if ($role != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', $userId)
                ->value('user_khuvuc');
        }
        $khuVucs = DB::table('tbl_tinh')
            ->distinct()
            ->orderBy('ten_khu_vuc')
            ->pluck('ten_khu_vuc');
        // dd($khuVucs);
        $hopDongs = DB::table('tbl_hopdong')->select('HopDong_Id', 'HopDong_SoHopDong')->get();
        $doiTacs = DB::table('tbl_user')->select('user_id', 'user_name')->get();
        return view('thong_ke.thongke_tongquat', compact('hopDongs', 'khuVucs', 'doiTacs'));    
    }
    public function indexTinh(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        $role = session('role');
        $userId = session('userid');
        $userKhuVuc = null;
        
        $khuVucListQuery = DB::table('tbl_tinh')
            ->distinct()
            // ->where('ten_khu_vuc', $userKhuVuc)
            ->select('ten_khu_vuc')
            ->orderBy('ten_khu_vuc');
        if ($role != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', $userId)
                ->value('user_khuvuc');
            $khuVucListQuery->where('ten_khu_vuc', $userKhuVuc);
        }
        $khuVucList=$khuVucListQuery->get()->toArray();
        return view('thong_ke.thongke_tinh', compact('khuVucList'));
    }
    public function indexTram(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        $maTinhChose = $request->ma_tinh;
        return view('thong_ke.thongke_tram', compact('maTinhChose'));
    }
    public function indexLinhVuc(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('/login');
        }
        return view('thong_ke.thongke_linhvuc');
    }

    public function thongKeKhuVuc(Request $request)
    {
        // Lấy tham số từ request
        $timeFormat = $request->input('time_format');
        $currentYear = $request->input('nam', date('Y'));
        $currentMonth = $request->input('thang', date('n'));
        $currentQuarter = ceil($currentMonth / 3);
        $hopDongId = $request->input('hop_dong');
        $role = session('role');
        $userId = $request->input('user'); // Lấy user từ request
        $role = session('role');
        $userKhuVuc = null;
        if ($role != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', session('userid'))
                ->value('user_khuvuc');
        }
        // dd($userKhuVuc);

        $khuVucs = DB::table('tbl_tinh')
            ->distinct()
            ->orderBy('ten_khu_vuc')
            ->pluck('ten_khu_vuc');

        $results = [];

        foreach ($khuVucs as $khuVuc) {
            if ($role != 3 && $khuVuc != $userKhuVuc) {
                continue;
            }
    
            $maTinhs = DB::table('tbl_tinh')
                ->where('ten_khu_vuc', $khuVuc)
                ->pluck('ma_tinh')
                ->toArray();

            $kpi_quy = DB::table('tbl_kpi_quy')
                ->where('ten_khu_vuc', $khuVuc)
                ->where('year', $currentYear)
                ->where('noi_dung', 'Tổng sản lượng')
                ->select('kpi_quy_1', 'kpi_quy_2', 'kpi_quy_3', 'kpi_quy_4')
                ->first();

            $kpi_nam = $kpi_quy->kpi_quy_1 + $kpi_quy->kpi_quy_2 + $kpi_quy->kpi_quy_3 + $kpi_quy->kpi_quy_4;

            $kpi_thang = [];
            for ($i = 1; $i <= 3; $i++) {
                $kpi_thang[$i] = $kpi_quy->kpi_quy_1 / 3;
                $kpi_thang[$i + 3] = $kpi_quy->kpi_quy_2 / 3;
                $kpi_thang[$i + 6] = $kpi_quy->kpi_quy_3 / 3;
                $kpi_thang[$i + 9] = $kpi_quy->kpi_quy_4 / 3;
            }
            $whereClauseSanLuong = "";
            $whereClauseThaoLap = "";
            switch ($timeFormat) {
                case 'ngay':
                    $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') = CURRENT_DATE()";
                    $whereClauseThaoLap = "STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') = CURRENT_DATE()";
                    $kpi = null; // KPI ngày không xác định trong ví dụ
                    break;
                case 'tuan':
                    $whereClauseSanLuong = "WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = WEEK(CURRENT_DATE())";
                    $whereClauseThaoLap = "WEEK(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = WEEK(CURRENT_DATE())";
                    $kpi = null; // KPI tuần không xác định trong ví dụ
                    break;
                case 'thang':
                    $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentMonth";
                    $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear AND MONTH(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentMonth";
                    $kpi = isset($kpi_thang[$currentMonth]) ? $kpi_thang[$currentMonth] : null;
                    break;
                case 'quy':
                    $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentQuarter";
                    $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear AND QUARTER(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentQuarter";
                    $kpi = $kpi_quy ? $kpi_quy->{'kpi_quy_' . $currentQuarter} : null;
                    break;
                case 'nam':
                    $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear";
                    $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear";
                    $kpi = $kpi_nam;
                    break;
                default:
                    return response()->json(['error' => 'Thời gian không hợp lệ']);
            }

            // Lấy dữ liệu từ bảng tbl_sanluong
            $sanluongQuery = DB::table('tbl_sanluong')
                ->select('SanLuong_Tram', 'SanLuong_Ngay', 'SanLuong_Gia')
                ->whereRaw($whereClauseSanLuong)
                ->where('ten_hinh_anh_da_xong', '!=', '')
                ->where(function ($query) use ($maTinhs) {
                    foreach ($maTinhs as $maTinh) {
                        $query->orWhere('SanLuong_Tram', 'LIKE', "$maTinh%");
                    }
                });
            if (!empty($hopDongId)) {
                $sanluongQuery->where('HopDong_Id', $hopDongId);
            }
            $sanluongData = $sanluongQuery->get();

            // Lấy dữ liệu từ bảng tbl_sanluong_khac
            //TODO: Hợp đồng của sản lượng khác
            $sanluongKhacData = null;
            if (empty($hopDongId)) {
                $sanluongKhacQuery = DB::table('tbl_sanluong_khac')
                ->select('SanLuong_Tram', 'SanLuong_Ngay', 'SanLuong_Gia')
                ->whereRaw($whereClauseSanLuong)
                ->where('SanLuong_KhuVuc', $khuVuc);
                if (!empty($userId)) {
                    $sanluongKhacQuery->where('user_id', $userId);
                }
                $sanluongKhacData = $sanluongKhacQuery->get();      
            }

            // Lấy dữ liệu từ bảng tbl_sanluong_thaolap
            $thaolapQuery = DB::table('tbl_sanluong_thaolap')
                ->select(
                    'ThaoLap_MaTram as SanLuong_Tram',
                    DB::raw("STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') as SanLuong_Ngay"),
                    DB::raw("
                    ThaoLap_Anten * DonGia_Anten +
                    ThaoLap_RRU * DonGia_RRU +
                    ThaoLap_TuThietBi * DonGia_TuThietBi +
                    ThaoLap_CapNguon * DonGia_CapNguon as SanLuong_Gia
                ")
                )
                ->whereRaw($whereClauseThaoLap)
                ->where(function ($query) use ($maTinhs) {
                    foreach ($maTinhs as $maTinh) {
                        $query->orWhere('ThaoLap_MaTram', 'LIKE', "$maTinh%");
                    }
                });
            if (!empty($hopDongId)) {
                $thaolapQuery->where('HopDong_Id', $hopDongId);
            }
            $sanluongThaolapData = $thaolapQuery->get();

            $combinedData = $sanluongData->merge($sanluongKhacData)->merge($sanluongThaolapData);
            $combinedData = $combinedData->map(function ($item) {
                $item->SanLuong_Gia = floatval($item->SanLuong_Gia);
                return $item;
            });
            $total = $combinedData->sum('SanLuong_Gia');

            // Thêm kết quả vào mảng
            $results[] = [
                'ten_khu_vuc' => $khuVuc,
                'total' => round($total / 1e9, 1),
                'kpi' => $kpi !== null ? round($kpi, 1) : 0
            ];
        }

        return response()->json($results);
    }
    //TODO: thêm phần hợp đồng và user
    public function thongKeXuTheKhuVuc(Request $request)
{
    // Lấy tham số từ request
    $timeFormat = $request->input('time_format');
    $ngayChon = $request->input('ngay_chon', date('Y-m-d')); // mặc định là ngày hiện tại
    $hopDongId = $request->input('hop_dong');
    $userId = $request->input('user_id');
    $role = session('role');
    $userKhuVuc = null;

    if ($role != 3) {
        $userKhuVuc = DB::table('tbl_user')
            ->where('user_id', $userId)
            ->value('user_khuvuc');
    }

    $khuVucs = DB::table('tbl_tinh')
        ->distinct()
        ->orderBy('ten_khu_vuc')
        ->pluck('ten_khu_vuc');

    $results = [];

    foreach ($khuVucs as $khuVuc) {
        if ($role != 3 && $khuVuc != $userKhuVuc) {
            continue;
        }

        $maTinhs = DB::table('tbl_tinh')
            ->where('ten_khu_vuc', $khuVuc)
            ->pluck('ma_tinh')
            ->toArray();

        // Khởi tạo mảng lưu kết quả chi tiết
        $detailedResults = [];

        // Thiết lập điều kiện thời gian
        $currentYear = date('Y', strtotime($ngayChon));
        $currentMonth = date('n', strtotime($ngayChon));
        $currentQuarter = ceil($currentMonth / 3);
        $dayOfWeek = date('N', strtotime($ngayChon));
        $startOfWeek = date('Y-m-d', strtotime("$ngayChon -" . ($dayOfWeek - 1) . " days"));
        $endOfWeek = date('Y-m-d', strtotime("$startOfWeek +6 days"));

        switch ($timeFormat) {
            case 'tuan':
                for ($i = 0; $i < 7; $i++) {
                    $date = date('Y-m-d', strtotime("$startOfWeek +$i days"));
                    $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') = '$date'";
                    $whereClauseThaoLap = "STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') = '$date'";
                    $result = $this->getSanLuongData($khuVuc, $maTinhs, $whereClauseSanLuong, $whereClauseThaoLap, $hopDongId, $userId);
                    $result['time_period'] = date('Y-m-d', strtotime($date));
                    $detailedResults[] = $result;
                }
                break;
            case 'thang':
                for ($i = 1; $i <= 12; $i++) {
                    $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $i";
                    $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear AND MONTH(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $i";
                    $result = $this->getSanLuongData($khuVuc, $maTinhs, $whereClauseSanLuong, $whereClauseThaoLap, $hopDongId, $userId);
                    $result['time_period'] = $i; // Month
                    $detailedResults[] = $result;
                }
                break;
            case 'quy':
                for ($i = 1; $i <= 4; $i++) {
                    $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $i";
                    $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear AND QUARTER(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $i";
                    $result = $this->getSanLuongData($khuVuc, $maTinhs, $whereClauseSanLuong, $whereClauseThaoLap, $hopDongId, $userId);
                    $result['time_period'] = $i; // Quarter
                    $detailedResults[] = $result;
                }
                break;
            case 'nam':
                $years = DB::table('tbl_sanluong')
                    ->selectRaw('YEAR(STR_TO_DATE(SanLuong_Ngay, "%d%m%Y")) as year')
                    ->orderBy('year', 'asc')
                    ->distinct()
                    ->pluck('year');
                foreach ($years as $year) {
                    $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $year";
                    $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $year";
                    $result = $this->getSanLuongData($khuVuc, $maTinhs, $whereClauseSanLuong, $whereClauseThaoLap, $hopDongId, $userId);
                    $result['time_period'] = $year; // Year
                    $detailedResults[] = $result;
                }
                break;
            default:
                return response()->json(['error' => 'Thời gian không hợp lệ']);
        }

        // Tính tổng sản lượng và thêm kết quả vào mảng
        $total = array_reduce($detailedResults, function ($carry, $item) {
            return $carry + $item['total'];
        }, 0);

        $results[] = [
            'ten_khu_vuc' => $khuVuc,
            'total' => round($total / 1e9, 1),
            'details' => $detailedResults
        ];
    }

    return response()->json($results);
}

// Hàm phụ để lấy dữ liệu sản lượng
private function getSanLuongData($khuVuc, $maTinhs, $whereClauseSanLuong, $whereClauseThaoLap, $hopDongId, $userId)
{
    // Lấy dữ liệu từ bảng tbl_sanluong
    $sanluongQuery = DB::table('tbl_sanluong')
        ->select('SanLuong_Tram', 'SanLuong_Ngay', 'SanLuong_Gia')
        ->whereRaw($whereClauseSanLuong)
        ->where('ten_hinh_anh_da_xong', '!=', '')
        ->where(function ($query) use ($maTinhs) {
            foreach ($maTinhs as $maTinh) {
                $query->orWhere('SanLuong_Tram', 'LIKE', "$maTinh%");
            }
        });
    if (!empty($hopDongId)) {
        $sanluongQuery->where('HopDong_Id', $hopDongId);
    }
    $sanluongData = $sanluongQuery->get();

    // Lấy dữ liệu từ bảng tbl_sanluong_khac
    // TODO: Hợp đồng của sản lượng khác
    $sanluongKhacData = null;
    if (empty($hopDongId)) {
        $sanluongKhacQuery = DB::table('tbl_sanluong_khac')
            ->select('SanLuong_Tram', 'SanLuong_Ngay', 'SanLuong_Gia')
            ->whereRaw($whereClauseSanLuong)
            ->where('SanLuong_KhuVuc', $khuVuc);
            if (!empty($userId)) {
                $sanluongKhacQuery->where('user_id', $userId);
            }
            $sanluongKhacData = $sanluongKhacQuery->get(); 
    }

    // Lấy dữ liệu từ bảng tbl_sanluong_thaolap
    $thaolapQuery = DB::table('tbl_sanluong_thaolap')
        ->select(
            'ThaoLap_MaTram as SanLuong_Tram',
            DB::raw("STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') as SanLuong_Ngay"),
            DB::raw("
                ThaoLap_Anten * DonGia_Anten +
                ThaoLap_RRU * DonGia_RRU +
                ThaoLap_TuThietBi * DonGia_TuThietBi +
                ThaoLap_CapNguon * DonGia_CapNguon as SanLuong_Gia
            ")
        )
        ->whereRaw($whereClauseThaoLap)
        ->where(function ($query) use ($maTinhs) {
            foreach ($maTinhs as $maTinh) {
                $query->orWhere('ThaoLap_MaTram', 'LIKE', "$maTinh%");
            }
        });
    if (!empty($hopDongId)) {
        $thaolapQuery->where('HopDong_Id', $hopDongId);
    }
    $sanluongThaolapData = $thaolapQuery->get();

    // Kết hợp dữ liệu từ 3 bảng
    $sanluongData = $sanluongData->concat($sanluongThaolapData);
    if (!empty($sanluongKhacData)) {
        $sanluongData = $sanluongData->concat($sanluongKhacData);
    }

    // Tính tổng sản lượng
    $total = $sanluongData->reduce(function ($carry, $item) {
        $gia = is_numeric($item->SanLuong_Gia) ? floatval($item->SanLuong_Gia) : 0;
        return $carry + $gia;
    }, 0);

    return ['total' => $total];
}


    public function thongKeTinh(Request $request)
    {
        // $khuVuc = $request->input('khu_vuc');
        $ngayChon = $request->input('ngay');

        if (is_null($ngayChon) || $ngayChon === '') {
            $ngayChon = date('Y-m-d');
        }

        $role = session('role');
        $userId = session('userid');
        $userKhuVuc = null;
        if ($role != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', $userId)
                ->value('user_khuvuc');
        }
        $khuVuc = $role == 3 ? $request->input('khu_vuc') : $userKhuVuc;
        $maTinhs = DB::table('tbl_tinh')
            ->where('ten_khu_vuc', $khuVuc)
            ->pluck('ma_tinh');

        $results = [];
        foreach ($maTinhs as $maTinh) {
            $query = "
                SELECT
                    SUM(CASE WHEN DATE(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = DATE('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as ngay,
                    SUM(CASE WHEN WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = WEEK('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as tuan,
                    SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = MONTH('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as thang,
                    SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = QUARTER('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as quy,
                    SUM(CASE WHEN YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = YEAR('$ngayChon') THEN SanLuong_Gia ELSE 0 END) as nam
                FROM tbl_sanluong
                WHERE SanLuong_Tram LIKE '$maTinh%'
                AND ten_hinh_anh_da_xong NOT LIKE ''
                UNION ALL
                SELECT
                    SUM(CASE WHEN DATE(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = DATE('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as ngay,
                    SUM(CASE WHEN WEEK(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = WEEK('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as tuan,
                    SUM(CASE WHEN MONTH(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = MONTH('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as thang,
                    SUM(CASE WHEN QUARTER(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = QUARTER('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as quy,
                    SUM(CASE WHEN YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = YEAR('$ngayChon') THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END) as nam
                FROM tbl_sanluong_thaolap
                WHERE ThaoLap_MaTram LIKE '$maTinh%'
            ";

            $totals = DB::select($query);

            $combinedTotals = (object) [
                'ngay' => 0,
                'tuan' => 0,
                'thang' => 0,
                'quy' => 0,
                'nam' => 0
            ];

            foreach ($totals as $total) {
                $combinedTotals->ngay += $total->ngay;
                $combinedTotals->tuan += $total->tuan;
                $combinedTotals->thang += $total->thang;
                $combinedTotals->quy += $total->quy;
                $combinedTotals->nam += $total->nam;
            }

            $results[] = [
                'ma_tinh' => $maTinh,
                'totals' => [
                    'ngay' => round($combinedTotals->ngay),
                    'tuan' => round($combinedTotals->tuan),
                    'thang' => round($combinedTotals->thang),
                    'quy' => round($combinedTotals->quy),
                    'nam' => round($combinedTotals->nam)
                ]
            ];
        }

        return response()->json($results);
    }

    public function thongKeTinhTongQuat(Request $request)
    {
        $role = session('role');
        $userId = session('userid');
        $userKhuVuc = null;
        if ($role != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', $userId)
                ->value('user_khuvuc');
        }

        $khuVuc = $role == 3 ? $request->input('khu_vuc') : $userKhuVuc;
        // $khuVuc = $request->input('khu_vuc');
        $currentYear = date('Y');

        $maTinhs = DB::table('tbl_tinh')
            ->where('ten_khu_vuc', $khuVuc)
            ->pluck('ma_tinh');

        $results = [];

        foreach ($maTinhs as $maTinh) {
            $query = "
            SELECT
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
                    SanLuong_Gia,
                    SanLuong_Ngay
                FROM tbl_sanluong
                WHERE SanLuong_Tram LIKE ? AND YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = ?
                AND ten_hinh_anh_da_xong NOT LIKE ''
                UNION ALL
                SELECT
                    ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon as SanLuong_Gia,
                    ThaoLap_Ngay as SanLuong_Ngay
                FROM tbl_sanluong_thaolap
                WHERE ThaoLap_MaTram LIKE ? AND YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = ?
            ) as subquery
        ";

            $bindings = [
                "$maTinh%", $currentYear,
                "$maTinh%", $currentYear,
            ];

            $data = DB::select($query, $bindings);

            foreach ($data as $row) {
                $results[] = [
                    'ma_tinh' => $maTinh,
                    'tong_san_luong' => [
                        'nam' => round($row->total_nam),
                        'quy_1' => round($row->total_quy_1),
                        'quy_2' => round($row->total_quy_2),
                        'quy_3' => round($row->total_quy_3),
                        'quy_4' => round($row->total_quy_4),
                        'thang_1' => round($row->total_thang_1),
                        'thang_2' => round($row->total_thang_2),
                        'thang_3' => round($row->total_thang_3),
                        'thang_4' => round($row->total_thang_4),
                        'thang_5' => round($row->total_thang_5),
                        'thang_6' => round($row->total_thang_6),
                        'thang_7' => round($row->total_thang_7),
                        'thang_8' => round($row->total_thang_8),
                        'thang_9' => round($row->total_thang_9),
                        'thang_10' => round($row->total_thang_10),
                        'thang_11' => round($row->total_thang_11),
                        'thang_12' => round($row->total_thang_12),
                    ],
                ];
            }
        }

        return response()->json($results);
    }
    public function thongKeTram(Request $request)
    {
        $maTinh = $request->ma_tinh;
        $ngayChon = $request->input('ngay');
        if (is_null($ngayChon) || $ngayChon === '') {
            $ngayChon = date('Y-m-d');
        }
        $role = session('role');
        $userId = session('userid');
        $userKhuVuc = null;
        if ($role != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', $userId)
                ->value('user_khuvuc');
            $tinhKhuVuc = DB::table('tbl_tinh')
                ->where('ma_tinh', $maTinh)
                ->value('ten_khu_vuc');
            if ($tinhKhuVuc != $userKhuVuc) {
                return response()->json([]); // Trả về dữ liệu trống nếu không khớp
            }
        }           

        $query = "
        SELECT
            SanLuong_Tram,
            ROUND(SUM(CASE WHEN DATE(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = DATE(?) THEN SanLuong_Gia ELSE 0 END)) as ngay,
            ROUND(SUM(CASE WHEN WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = WEEK(?) THEN SanLuong_Gia ELSE 0 END)) as tuan,
            ROUND(SUM(CASE WHEN MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = MONTH(?) THEN SanLuong_Gia ELSE 0 END)) as thang,
            ROUND(SUM(CASE WHEN QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = QUARTER(?) THEN SanLuong_Gia ELSE 0 END)) as quy,
            ROUND(SUM(CASE WHEN YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = YEAR(?) THEN SanLuong_Gia ELSE 0 END)) as nam
        FROM tbl_sanluong
        WHERE SanLuong_Tram LIKE ?
        AND ten_hinh_anh_da_xong NOT LIKE ''
        GROUP BY SanLuong_Tram
        UNION ALL
        SELECT
            ThaoLap_MaTram as SanLuong_Tram,
            ROUND(SUM(CASE WHEN DATE(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = DATE(?) THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END)) as ngay,
            ROUND(SUM(CASE WHEN WEEK(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = WEEK(?) THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END)) as tuan,
            ROUND(SUM(CASE WHEN MONTH(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = MONTH(?) THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END)) as thang,
            ROUND(SUM(CASE WHEN QUARTER(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = QUARTER(?) THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END)) as quy,
            ROUND(SUM(CASE WHEN YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = YEAR(?) THEN ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon ELSE 0 END)) as nam
        FROM tbl_sanluong_thaolap
        WHERE ThaoLap_MaTram LIKE ?
        GROUP BY SanLuong_Tram
        ORDER BY SanLuong_Tram
    ";

        $bindings = [
            $ngayChon, $ngayChon, $ngayChon, $ngayChon, $ngayChon,
            "$maTinh%",
            $ngayChon, $ngayChon, $ngayChon, $ngayChon, $ngayChon,
            "$maTinh%"
        ];

        $results = DB::select($query, $bindings);

        return response()->json($results);
    }

    public function thongKeTramTongQuat(Request $request)
    {
        $maTinh = $request->ma_tinh;
        $currentYear = date('Y');
        $role = session('role');
        $userId = session('userid');
        $userKhuVuc = null;
        if ($role != 3) {
            $userKhuVuc = DB::table('tbl_user')
                ->where('user_id', $userId)
                ->value('user_khuvuc');
            $tinhKhuVuc = DB::table('tbl_tinh')
                ->where('ma_tinh', $maTinh)
                ->value('ten_khu_vuc');
            if ($tinhKhuVuc != $userKhuVuc) {
                return response()->json([]); // Trả về dữ liệu trống nếu không khớp
            }
        } 

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
            AND ten_hinh_anh_da_xong NOT LIKE ''
            UNION ALL
            SELECT
                ThaoLap_MaTram as SanLuong_Tram,
                ThaoLap_Anten * DonGia_Anten + ThaoLap_RRU * DonGia_RRU + ThaoLap_TuThietBi * DonGia_TuThietBi + ThaoLap_CapNguon * DonGia_CapNguon as SanLuong_Gia,
                ThaoLap_Ngay as SanLuong_Ngay
            FROM tbl_sanluong_thaolap
            WHERE ThaoLap_MaTram LIKE ? AND YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = ?
        ) as subquery
        GROUP BY SanLuong_Tram
        ORDER BY SanLuong_Tram
    ";

        $bindings = [
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
                    'nam' => round($row->total_nam),
                    'quy_1' => round($row->total_quy_1),
                    'quy_2' => round($row->total_quy_2),
                    'quy_3' => round($row->total_quy_3),
                    'quy_4' => round($row->total_quy_4),
                    'thang_1' => round($row->total_thang_1),
                    'thang_2' => round($row->total_thang_2),
                    'thang_3' => round($row->total_thang_3),
                    'thang_4' => round($row->total_thang_4),
                    'thang_5' => round($row->total_thang_5),
                    'thang_6' => round($row->total_thang_6),
                    'thang_7' => round($row->total_thang_7),
                    'thang_8' => round($row->total_thang_8),
                    'thang_9' => round($row->total_thang_9),
                    'thang_10' => round($row->total_thang_10),
                    'thang_11' => round($row->total_thang_11),
                    'thang_12' => round($row->total_thang_12),
                ],
            ];
        }

        return response()->json($results);
    }
    public function indexChiTietChart(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('/login');
        }
        return view('thong_ke.chitiet_bieudo');
    }

    //TODO: Số liệu? không khớp với thống kê tổng quát (có thể từ tbl_sanluong_thaolap không có kv)
    public function thongKeLinhVuc(Request $request)
{
    $timeFormat = $request->input('time_format');
    $currentYear = $request->input('nam', date('Y'));
    $currentMonth = $request->input('thang', date('n'));
    $currentQuarter = ceil($currentMonth / 3);
    $userRole = session('role');
    $userId = session('userid');
    $userKhuVuc = null;
    if ($userRole != 3) {
        $userKhuVuc = DB::table('tbl_user')
            ->where('user_id', $userId)
            ->value('user_khuvuc');
    }

    $whereClauseSanLuong = "";
    $whereClauseThaoLap = "";
    switch ($timeFormat) {
        case 'ngay':
            $whereClauseSanLuong = "STR_TO_DATE(SanLuong_Ngay, '%d%m%Y') = CURRENT_DATE()";
            $whereClauseThaoLap = "STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y') = CURRENT_DATE()";
            break;
        case 'tuan':
            $whereClauseSanLuong = "WEEK(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = WEEK(CURRENT_DATE())";
            $whereClauseThaoLap = "WEEK(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = WEEK(CURRENT_DATE())";
            break;
        case 'thang':
            $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND MONTH(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentMonth";
            $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear AND MONTH(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentMonth";
            break;
        case 'quy':
            $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear AND QUARTER(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentQuarter";
            $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear AND QUARTER(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentQuarter";
            break;
        case 'nam':
            $whereClauseSanLuong = "YEAR(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')) = $currentYear";
            $whereClauseThaoLap = "YEAR(STR_TO_DATE(ThaoLap_Ngay, '%d/%m/%Y')) = $currentYear";
            break;
        default:
            return response()->json(['error' => 'Thời gian không hợp lệ']);
    }

    $khuVucs = DB::table('tbl_tinh')
        ->distinct()
        ->pluck('ten_khu_vuc');

    $results = [];

    foreach ($khuVucs as $khuVuc) {
        if ($userRole != 3 && $khuVuc != $userKhuVuc) {
            continue;
        }

        $maTinhs = DB::table('tbl_tinh')
            ->where('ten_khu_vuc', $khuVuc)
            ->pluck('ma_tinh');

        $sanluongDataQuery = DB::table('tbl_sanluong')
            ->join('tbl_tinh', 'tbl_sanluong.SanLuong_Tram', 'LIKE', DB::raw("CONCAT(tbl_tinh.ma_tinh, '%')"))
            ->select(DB::raw("IFNULL(CAST(SanLuong_Gia AS UNSIGNED), 0) as SanLuong_Gia"))
            ->whereRaw($whereClauseSanLuong)
            ->where('ten_hinh_anh_da_xong', '<>', '')
            ->whereIn('tbl_tinh.ma_tinh', $maTinhs);
        $sanluongData = $sanluongDataQuery->get();

        $sanluongThaolapDataQuery = DB::table('tbl_sanluong_thaolap')
            ->join('tbl_tinh', 'tbl_sanluong_thaolap.ThaoLap_MaTram', 'LIKE', DB::raw("CONCAT(tbl_tinh.ma_tinh, '%')"))
            ->select(
                DB::raw("
                IFNULL(
                    ThaoLap_Anten * DonGia_Anten +
                    ThaoLap_RRU * DonGia_RRU +
                    ThaoLap_TuThietBi * DonGia_TuThietBi +
                    ThaoLap_CapNguon * DonGia_CapNguon, 0
                ) as SanLuong_Gia
            ")
            )
            ->whereRaw($whereClauseThaoLap)
            ->whereIn('tbl_tinh.ma_tinh', $maTinhs);
        $sanluongThaolapData = $sanluongThaolapDataQuery->get();

        $totalEC = $sanluongData->sum('SanLuong_Gia') + $sanluongThaolapData->sum('SanLuong_Gia');

        $sanluongKhacDataQuery = DB::table('tbl_sanluong_khac')
            ->select('SanLuong_TenHangMuc', DB::raw('SUM(SanLuong_Gia) as total'))
            ->whereRaw($whereClauseSanLuong)
            ->where('SanLuong_KhuVuc', $khuVuc)
            ->groupBy('SanLuong_TenHangMuc');
        $sanluongKhacData = $sanluongKhacDataQuery->get();

        $results[$khuVuc]['EC'] = [
            'ten_linh_vuc' => 'EC',
            'total' => round($totalEC / 1e9, 1),
            'kpi' => 0
        ];

        foreach ($sanluongKhacData as $row) {
            $results[$khuVuc][$row->SanLuong_TenHangMuc] = [
                'ten_linh_vuc' => $row->SanLuong_TenHangMuc,
                'total' => round($row->total / 1e9, 1),
                'kpi' => 0
            ];
        }
    }

    $kpiDataQuery = DB::table('tbl_kpi_quy')
        ->select('ten_khu_vuc', 'noi_dung', 'kpi_quy_1', 'kpi_quy_2', 'kpi_quy_3', 'kpi_quy_4')
        ->where('year', $currentYear);
    if ($userRole != 3) {
        $kpiDataQuery->where('ten_khu_vuc', $userKhuVuc);
    }
    $kpiData = $kpiDataQuery->get();

    foreach ($kpiData as $kpi) {
        $kpiValue = 0;
        switch ($timeFormat) {
            case 'thang':
                $kpiValue = round(($kpi->{'kpi_quy_' . $currentQuarter} / 3), 1);
                break;
            case 'quy':
                $kpiValue = round($kpi->{'kpi_quy_' . $currentQuarter}, 1);
                break;
            case 'nam':
                $kpiValue = round($kpi->kpi_quy_1 + $kpi->kpi_quy_2 + $kpi->kpi_quy_3 + $kpi->kpi_quy_4, 1);
                break;
        }

        if (isset($results[$kpi->ten_khu_vuc][$kpi->noi_dung])) {
            $results[$kpi->ten_khu_vuc][$kpi->noi_dung]['kpi'] += $kpiValue;
        }
    }

    foreach ($results as &$khuVucData) {
        foreach ($khuVucData as &$result) {
            $result['kpi'] = round($result['kpi'], 1);
        }
    }

    $finalResults = [];
    foreach ($results as $khuVuc => $data) {
        foreach ($data as $item) {
            $item['khu_vuc'] = $khuVuc;
            $finalResults[] = $item;
        }
    }

    // Sort final results by khu_vuc in ascending order
    usort($finalResults, function($a, $b) {
        return strcmp($a['khu_vuc'], $b['khu_vuc']);
    });

    return response()->json($finalResults);
}


}

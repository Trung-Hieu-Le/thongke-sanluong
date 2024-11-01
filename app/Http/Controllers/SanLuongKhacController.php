<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SanLuongKhacController extends Controller
{
    public function indexSanLuong(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }

        $daysString = $request->input('days', '');
        $days = [];
        if (!empty($daysString)) {
            $days = explode(',', $daysString);
            $days = array_map(function ($day) {
                return Carbon::createFromFormat('d-m-Y', $day)->format('dmY');
            }, $days);
        }
        $search = $request->input('search', '');
        $users= DB::table('tbl_user')
            ->select('user_id', 'user_name')
            ->get()->keyBy('user_id');

        $query = DB::table('tbl_sanluong_khac');
        if (count($days) > 0) {
            $query->whereIn('SanLuong_Ngay', $days);
        }
        if ($search) {
            $query->where('SanLuong_TenHangMuc', 'like', '%' . $search . '%');
        }
        $data = $query->simplePaginate(100);

        return view('san_luong.sanluong_view', compact('data', 'days', 'search','users'));
    }

    public function addSanLuong(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        $hopdongs = DB::table('tbl_hopdong')
            ->select('HopDong_Id', 'HopDong_SoHopDong', 'HopDong_TenHopDong')
            ->get()->toArray();
        $khuvucs = DB::table('tbl_sanluongkhac_noidung')->select('khu_vuc')->distinct()->get();
        return view('san_luong.sanluong_add', compact('hopdongs', 'khuvucs'));
    }
    public function getNoiDungByKhuVuc($khuVuc)
    {
        $noidungs = DB::table('tbl_sanluongkhac_noidung')->where('khu_vuc', $khuVuc)->get();
        return response()->json($noidungs);
    }

    public function handleAddSanLuong(Request $request)
    {
        if (!$request->session()->has('username') || !$request->session()->has('userid')) {
            return redirect('login');
        }
        $date = $request->SanLuong_Ngay
            ? Carbon::createFromFormat('Y-m-d', $request->SanLuong_Ngay)->format('dmY')
            : date('dmY');
        // dd($request);
        $userid = session('userid');
        DB::table('tbl_sanluong_khac')->insert([
            // 'SanLuong_Tram' => $request->SanLuong_Tram,
            'SanLuong_Ngay' => $date,
            'SanLuong_Gia' => $request->SanLuong_Gia,
            // 'HopDong_Id' => $request->HopDong_Id,
            'SanLuong_TenHangMuc' => $request->SanLuong_TenHangMuc,
            'SanLuong_KhuVuc' => $request->khu_vuc,
            'user_id' => $userid,
            'thoi_gian_them' => now('GMT+7'),
            'thoi_gian_sua' => now('GMT+7')
        ]);
        $updateTongHopSanLuong=$this->updateSanLuongKhacTableTongHopSanLuong();

        return redirect()->route('sanluongkhac.add')->with('success', 'Thêm sản lượng thành công.');
    }
    public function editSanLuong(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }

        $sanLuong = DB::table('tbl_sanluong_khac')->where('SanLuong_Id', $request->id)->first();
        $hopdongs = DB::table('tbl_hopdong')->get();
        $khuvucs = DB::table('tbl_sanluongkhac_noidung')->select('khu_vuc')->distinct()->get();
        $noidungs = DB::table('tbl_sanluongkhac_noidung')->where('khu_vuc', $sanLuong->SanLuong_KhuVuc)->get();
        if (!$sanLuong) {
            return redirect()->route('sanluongkhac.index')->with('error', 'Không tìm thấy sản lượng');
        }

        $ngayFormatted = Carbon::createFromFormat('dmY', $sanLuong->SanLuong_Ngay)->format('Y-m-d');
        $sanLuong->SanLuong_Ngay = $ngayFormatted;

        return view('san_luong.sanluong_edit', compact('sanLuong', 'hopdongs', 'khuvucs', 'noidungs'));
    }

    public function handleEditSanLuong(Request $request)
    {
        if (!$request->session()->has('username') || !$request->session()->has('userid')) {
            return redirect('login');
        }
        $date = $request->SanLuong_Ngay
            ? Carbon::createFromFormat('Y-m-d', $request->SanLuong_Ngay)->format('dmY')
            : date('dmY');
            // dd($request);
        // $userid = session('userid');
        try {
            DB::table('tbl_sanluong_khac')->where('SanLuong_Id', $request->id)->update([
                // 'SanLuong_Tram' => $request->SanLuong_Tram,
                'SanLuong_Ngay' => $date,
                'SanLuong_Gia' => $request->SanLuong_Gia,
                // 'HopDong_Id' => $request->HopDong_Id,
                'SanLuong_TenHangMuc' => $request->SanLuong_TenHangMuc,
                'SanLuong_KhuVuc' => $request->khu_vuc,
                // 'user_id' => $userid,
                'thoi_gian_sua' => now('GMT+7')
            ]);
            $updateTongHopSanLuong=$this->updateSanLuongKhacTableTongHopSanLuong();
        } catch (\Exception $e) {
            return redirect()->route('sanluongkhac.index')->with('fail', 'Cập nhật sản lượng thất bại');
        }

        return redirect()->route('sanluongkhac.index')->with('success', 'Cập nhật sản lượng thành công');
    }
    public function deleteSanLuong($id)
    {
        if (!session()->has('username')) {
            return redirect('login');
        }
        DB::table('tbl_sanluong_khac')->where('SanLuong_Id', $id)->delete();
        $updateTongHopSanLuong=$this->updateSanLuongKhacTableTongHopSanLuong();

        return redirect()->route('sanluongkhac.index')->with('success', 'Xóa sản lượng thành công');
    }

    private function updateSanLuongKhacTableTongHopSanLuong()
    {
        $years = [2023, date('Y')];
        $months = range(1, 12);
        // $ngayChon = $request->input('ngay_chon', date('Y-m-d'));
        // $year = date('Y', strtotime($ngayChon));
        // $month = date('m', strtotime($ngayChon));
        // $day = date('d', strtotime($ngayChon));
        DB::table('tbl_tonghop_sanluong')
                ->whereNot('linh_vuc','EC')
                ->where('ma_tinh','')
                ->delete();
        foreach ($years as $year) {
            foreach ($months as $month) {
                $formattedMonth = str_pad($month, 2, '0', STR_PAD_LEFT);
                $sanluongKhacData = DB::table('tbl_sanluong_khac')
                ->select(
                    DB::raw("UPPER(LEFT(SanLuong_Tram, 3)) as ma_tinh"),
                    DB::raw("DATE_FORMAT(STR_TO_DATE(SanLuong_Ngay, '%d%m%Y'), '%d') as day"),
                    'SanLuong_TenHangMuc as linh_vuc',
                    DB::raw("SUM(SanLuong_Gia) as total_sanluong"),
                    'SanLuong_KhuVuc as khu_vuc'
                )
                ->whereYear(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), $year)
                ->whereMonth(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), $month)
                    // ->whereDate(DB::raw("STR_TO_DATE(SanLuong_Ngay, '%d%m%Y')"), '=', $ngayChon)
                    ->groupBy('ma_tinh', 'day', 'SanLuong_TenHangMuc', 'SanLuong_KhuVuc')
                    ->get();

                $combinedData = [];

                foreach ($sanluongKhacData as $data) {
                    $key = "{$data->ma_tinh}-{$data->linh_vuc}-{$year}-{$formattedMonth}";
                    if (!isset($combinedData[$key])) {
                        $combinedData[$key] = [
                            'khu_vuc' => $data->khu_vuc ?? '',
                            'linh_vuc' => $data->linh_vuc,
                            'ma_tinh' => $data->ma_tinh ?? '',
                            'year' => $year,
                            'month' => $formattedMonth,
                            'SanLuong_Ngay_01' => 0,
                            'SanLuong_Ngay_02' => 0,
                            'SanLuong_Ngay_03' => 0,
                            'SanLuong_Ngay_04' => 0,
                            'SanLuong_Ngay_05' => 0,
                            'SanLuong_Ngay_06' => 0,
                            'SanLuong_Ngay_07' => 0,
                            'SanLuong_Ngay_08' => 0,
                            'SanLuong_Ngay_09' => 0,
                            'SanLuong_Ngay_10' => 0,
                            'SanLuong_Ngay_11' => 0,
                            'SanLuong_Ngay_12' => 0,
                            'SanLuong_Ngay_13' => 0,
                            'SanLuong_Ngay_14' => 0,
                            'SanLuong_Ngay_15' => 0,
                            'SanLuong_Ngay_16' => 0,
                            'SanLuong_Ngay_17' => 0,
                            'SanLuong_Ngay_18' => 0,
                            'SanLuong_Ngay_19' => 0,
                            'SanLuong_Ngay_20' => 0,
                            'SanLuong_Ngay_21' => 0,
                            'SanLuong_Ngay_22' => 0,
                            'SanLuong_Ngay_23' => 0,
                            'SanLuong_Ngay_24' => 0,
                            'SanLuong_Ngay_25' => 0,
                            'SanLuong_Ngay_26' => 0,
                            'SanLuong_Ngay_27' => 0,
                            'SanLuong_Ngay_28' => 0,
                            'SanLuong_Ngay_29' => 0,
                            'SanLuong_Ngay_30' => 0,
                            'SanLuong_Ngay_31' => 0,
                        ];
                    }
                    $combinedData[$key]["SanLuong_Ngay_{$data->day}"] += $data->total_sanluong;
                }
                //dd($combinedData);

                foreach ($combinedData as $data) {
                    $existingData = DB::table('tbl_tonghop_sanluong')
                    ->select('id', 'linh_vuc')
                        ->where('ma_tinh', $data['ma_tinh'])
                        ->where('linh_vuc', $data['linh_vuc'])
                        ->where('year', $data['year'])
                        ->where('month', $data['month'])
                        ->first();  
                    if ($existingData) {
                        DB::table('tbl_tonghop_sanluong')
                        ->where('id',
                            $existingData->id
                        )
                        ->update($data);
                    } else {
                        DB::table('tbl_tonghop_sanluong')->insert($data);
                    }
                }
            }
        }
    }
}

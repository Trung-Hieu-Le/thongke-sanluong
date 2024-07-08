<?php

namespace App\Http\Controllers;

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

        $daysString = $request->input('days', date('d-m-Y'));
        $days = [];
        if (!empty($daysString)) {
            $days = explode(',', $daysString);
            $days = array_map(function ($day) {
                return Carbon::createFromFormat('d-m-Y', $day)->format('dmY');
            }, $days);
        }
        $search = $request->input('search', '');

        $query = DB::table('tbl_sanluong_khac');
        if (count($days) > 0) {
            $query->whereIn('SanLuong_Ngay', $days);
        }
        if ($search) {
            $query->where('SanLuong_Tram', 'like', '%' . $search . '%');
        }
        $data = $query->simplePaginate(100);

        return view('san_luong.sanluong_view', compact('data', 'days', 'search'));
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
            'HopDong_Id' => $request->HopDong_Id,
            'SanLuong_TenHangMuc' => $request->ten_hang_muc
        ]);

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
        $noidungs = DB::table('tbl_sanluongkhac_noidung')->where('khu_vuc', $sanLuong->khu_vuc)->get();
        if (!$sanLuong) {
            return redirect()->route('sanluongkhac.index')->with('error', 'Không tìm thấy sản lượng');
        }

        $ngayFormatted = Carbon::createFromFormat('dmY', $sanLuong->SanLuong_Ngay)->format('Y-m-d');
        $sanLuong->SanLuong_Ngay = $ngayFormatted;

        return view('san_luong.sanluong_edit', compact('sanLuong', 'hopdongs', 'khuvucs', 'noidungs'));
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
            'HopDong_Id' => $request->HopDong_Id,
            'SanLuong_TenHangMuc' => $request->ten_hang_muc
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

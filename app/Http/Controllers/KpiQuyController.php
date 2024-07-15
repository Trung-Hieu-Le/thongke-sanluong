<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KpiQuyController extends Controller
{
    public function indexKpiQuy(){
        $kpi = DB::table('tbl_kpi_quy')
            // ->select('ten_khu_vuc', 'noi_dung', 'year', 'kpi_quy_1', 'kpi_quy_2', 'kpi_quy_3', 'kpi_quy_4')
            ->orderBy('ten_khu_vuc')
            ->orderBy('year')
            ->get()->toArray();

    // $data = [];
    // foreach ($kpi as $row) {
    //     $key = $row->ten_khu_vuc . '-' . $row->year;
    //     if (!isset($data[$key])) {
    //         $data[$key] = [
    //             'ten_khu_vuc' => $row->ten_khu_vuc,
    //             'noi_dung' => $row->noi_dung,
    //             'year' => $row->year,
    //             'quarters' => [null, null, null, null]
    //         ];
    //     }
    //     $data[$key]['quarters'][$row->quarter - 1] = $row->kpi_quy;
    // }
    return view('kpi.kpi_quy_view', compact('kpi'));
    }
    // Thêm sản lượng theo ngày
    public function addKpiQuy(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        $khuVucList = DB::table('tbl_sanluongkhac_noidung')
            ->distinct()
            ->select('khu_vuc')
            ->orderBy('khu_vuc')
            ->get()->toArray();
        return view('kpi.kpi_quy_add', compact('khuVucList'));
    }

    // Lưu dữ liệu vào cơ sở dữ liệu
    public function handleAddKpiQuy(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        DB::table('tbl_kpi_quy')->insert([
            'ten_khu_vuc' => $request->khu_vuc,
            'year' => $request->year,
            'noi_dung' => $request->noi_dung,
            'kpi_quy_1' => $request->kpi_quy_1,
            'kpi_quy_2' => $request->kpi_quy_2,
            'kpi_quy_3' => $request->kpi_quy_3,
            'kpi_quy_4' => $request->kpi_quy_4,
        ]);

        return redirect()->route('kpiquy.add')->with('success', 'Thêm KPI thành công.');
    }
    public function editKpiQuy(Request $request){
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        $kpiData = DB::table('tbl_kpi_quy')
        ->where('id', $request->id)
        ->first();

        $khuVucList = DB::table('tbl_sanluongkhac_noidung')
            ->distinct()
            ->select('khu_vuc')
            ->orderBy('khu_vuc')
            ->get()->toArray();
        $noidungs = DB::table('tbl_sanluongkhac_noidung')->where('khu_vuc', $kpiData->ten_khu_vuc)->get();
        // dd($noidungs);
        return view('kpi.kpi_quy_edit', compact('kpiData', 'khuVucList', 'noidungs'));
    }
    public function handleEditKpiQuy(Request $request){
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        DB::table('tbl_kpi_quy')
            ->where('id', $request->id)
            // ->where('year', $request->input('year'))
            ->update([
                'ten_khu_vuc' => $request->khu_vuc,
                'year' => $request->year,
                'noi_dung' => $request->noi_dung,
                'kpi_quy_1' => $request->kpi_quy_1,
                'kpi_quy_2' => $request->kpi_quy_2,
                'kpi_quy_3' => $request->kpi_quy_3,
                'kpi_quy_4' => $request->kpi_quy_4,
            ]);
        return redirect()->route('kpiquy.index')->with('success', 'KPI đã được cập nhật');
    }
    public function deleteKpiQuy(Request $request)
    {
        if (!session()->has('username')) {
            return redirect('login');
        }
        DB::table('tbl_kpi_quy')
        ->where('id', $request->id)
        ->delete();
        
        return redirect()->route('kpiquy.index')->with('success', 'Xóa KPI thành công');
    }
}

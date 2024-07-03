<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KpiQuyController extends Controller
{
    public function indexKpiQuy(){
        $kpi = DB::table('tbl_kpi_quy')
        ->select('ten_khu_vuc', 'year', 'quarter', 'kpi_quy')
        ->orderBy('ten_khu_vuc')
        ->orderBy('year')
        ->orderBy('quarter')
        ->get();

    $data = [];
    foreach ($kpi as $row) {
        $key = $row->ten_khu_vuc . '-' . $row->year;
        if (!isset($data[$key])) {
            $data[$key] = [
                'ten_khu_vuc' => $row->ten_khu_vuc,
                'year' => $row->year,
                'quarters' => [null, null, null, null]
            ];
        }
        $data[$key]['quarters'][$row->quarter - 1] = $row->kpi_quy;
    }
    return view('kpi.kpi_quy_view', compact('data'));
    }
    // Thêm sản lượng theo ngày
    public function addKpiQuy(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        $khuVucList = DB::table('tbl_tinh')
            ->distinct()
            ->select('ten_khu_vuc')
            ->orderBy('ten_khu_vuc')
            ->get()->toArray();
        return view('kpi.kpi_quy_add', compact('khuVucList'));
    }

    // Lưu dữ liệu vào cơ sở dữ liệu
    public function handleAddKpiQuy(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        // Tạo dữ liệu mới
        for ($quarter = 1; $quarter <= 4; $quarter++) {
            DB::table('tbl_kpi_quy')->insert([
                'ten_khu_vuc' => $request->ten_khu_vuc,
                'year' => $request->year,
                'quarter' => $quarter,
                'kpi_quy' => $request->{'kpi_quy_' . $quarter},
            ]);
        }

        return redirect()->route('kpiquy.add')->with('success', 'Thêm KPI thành công.');
    }
    public function editKpiQuy(Request $request){
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        $khuvuc = $request->input('khuvuc');
        $nam = $request->input('nam');
        $kpiRecords = DB::table('tbl_kpi_quy')
        ->where('ten_khu_vuc', $khuvuc)
        ->where('year', $nam)
        ->get();

        if ($kpiRecords->isEmpty()) {
            return redirect()->route('kpiquy.index')->with('error', 'Không tìm thấy KPI');
        }

        $kpiData = [
            'ten_khu_vuc' => $khuvuc,
            'year' => $nam,
            'kpi_quy_1' => $kpiRecords->where('quarter', 1)->first()->kpi_quy ?? '',
            'kpi_quy_2' => $kpiRecords->where('quarter', 2)->first()->kpi_quy ?? '',
            'kpi_quy_3' => $kpiRecords->where('quarter', 3)->first()->kpi_quy ?? '',
            'kpi_quy_4' => $kpiRecords->where('quarter', 4)->first()->kpi_quy ?? '',
        ];
    
        return view('kpi.kpi_quy_edit', compact('kpiData'));
    }
    public function editHandleKpiQuy(Request $request){
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        for ($quarter = 1; $quarter <= 4; $quarter++) {
            DB::table('tbl_kpi_quy')
            ->where('ten_khu_vuc', $request->input('ten_khu_vuc'))
            ->where('year', $request->input('year'))
            ->update([
                'quarter' => $quarter,
                'kpi_quy' => $request->{'kpi_quy_' . $quarter},
            ]);
        }
        return redirect()->route('kpiquy.index')->with('success', 'KPI đã được cập nhật');
    }
}

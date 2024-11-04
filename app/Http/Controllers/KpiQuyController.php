<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KpiQuyController extends Controller
{
    //TODO: KPI sản lượng kiểm định?
    public function indexKpiQuy(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        
        if ($request->has('khu_vuc') && $request->has('year')) {
            $this->checkAndInsertMissingKpi($request->khu_vuc, $request->year);
        }
        $query = DB::table('tbl_kpi_quy');
        if ($request->has('khu_vuc') && $request->khu_vuc != '') {
            $query->where('ten_khu_vuc', $request->khu_vuc);
        }
        if ($request->has('year') && $request->year != '') {
            $query->where('year', $request->year);
        }
        $kpi = $query->orderBy('ten_khu_vuc')
            ->orderBy('year')
            ->get();
        $years = range(2023, date('Y'));
        $khu_vucs = DB::table('tbl_sanluongkhac_noidung')
            ->select('khu_vuc')
            ->distinct()
            ->get();

        return view('kpi.kpi_quy_view', compact('kpi', 'years', 'khu_vucs'));
    }

    private function checkAndInsertMissingKpi($khuVuc, $year)
    {
        $noiDungs = DB::table('tbl_sanluongkhac_noidung')
            ->where('khu_vuc', $khuVuc)
            ->pluck('noi_dung')
            ->toArray();
            
            foreach ($noiDungs as $noiDung) {
            $existingKpi = DB::table('tbl_kpi_quy')
                ->where('ten_khu_vuc', $khuVuc)
                ->where('year', $year)
                ->where('noi_dung', $noiDung)
                ->first();

            if (!$existingKpi) {
                DB::table('tbl_kpi_quy')->insert([
                    'ten_khu_vuc' => $khuVuc,
                    'noi_dung' => $noiDung,
                    'year' => $year,
                    'kpi_quy_1' => '0',
                    'kpi_quy_2' => '0',
                    'kpi_quy_3' => '0',
                    'kpi_quy_4' => '0',
                    'kpi_nam' => '0',
                    'kpi_thang_1' => '0',
                    'kpi_thang_2' => '0',
                    'kpi_thang_3' => '0',
                    'kpi_thang_4' => '0',
                    'kpi_thang_5' => '0',
                    'kpi_thang_6' => '0',
                    'kpi_thang_7' => '0',
                    'kpi_thang_8' => '0',
                    'kpi_thang_9' => '0',
                    'kpi_thang_10' => '0',
                    'kpi_thang_11' => '0',
                    'kpi_thang_12' => '0'
                ]);
            }
        }
    }

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
    public function editKpiQuy(Request $request)
    {
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
    public function handleEditKpiQuy(Request $request)
    {
        if (!$request->session()->has('username')) {
            return redirect('login');
        }
        DB::table('tbl_kpi_quy')
            ->where('id', $request->id)
            // ->where('year', $request->input('year'))
            ->update([
                // 'ten_khu_vuc' => $request->khu_vuc,
                // 'year' => $request->year,
                // 'noi_dung' => $request->noi_dung,
                'kpi_quy_1' => $request->kpi_quy_1,
                'kpi_quy_2' => $request->kpi_quy_2,
                'kpi_quy_3' => $request->kpi_quy_3,
                'kpi_quy_4' => $request->kpi_quy_4,
                'kpi_thang_1' => $request->kpi_thang_1,
                'kpi_thang_2' => $request->kpi_thang_2,
                'kpi_thang_3' => $request->kpi_thang_3,
                'kpi_thang_4' => $request->kpi_thang_4,
                'kpi_thang_5' => $request->kpi_thang_5,
                'kpi_thang_6' => $request->kpi_thang_6,
                'kpi_thang_7' => $request->kpi_thang_7,
                'kpi_thang_8' => $request->kpi_thang_8,
                'kpi_thang_9' => $request->kpi_thang_9,
                'kpi_thang_10' => $request->kpi_thang_10,
                'kpi_thang_11' => $request->kpi_thang_11,
                'kpi_thang_12' => $request->kpi_thang_12,
                'kpi_nam' => $request->kpi_quy_1 + $request->kpi_quy_2 + $request->kpi_quy_3 + $request->kpi_quy_4
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

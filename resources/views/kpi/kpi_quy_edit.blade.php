@include('layouts.head_thongke')
<body>
    @include('layouts.header_thongke')
    <div class="container">
        <div class="row">
            <div class="col-12 breadcrumb-wrapper mt-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a class="simple-link" href="/">Tổng quát</a></li>
                        <li class="breadcrumb-item"><a class="simple-link" href="/kpi-quy/index">KPI quý</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Sửa KPI quý</li>
                    </ol>
                </nav>
            </div>
        </div>
        <h2>Sửa KPI quý (đơn vị: tỉ đồng)</h2>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="p-3 border">
            <form action="{{ route('kpiquy.handleEdit', ['id' => $kpiData->id]) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="form-group my-2 col-lg-5 col-md-12">
                        <label for="ten_khu_vuc">Tên khu vực:</label>
                        <select class="form-control" id="khu_vuc" name="khu_vuc" required disabled>
                            <option value="">Chọn khu vực</option>
                            @foreach($khuVucList as $khuvuc)
                                <option value="{{ $khuvuc->khu_vuc }}" {{ $khuvuc->khu_vuc == $kpiData->ten_khu_vuc ? 'selected' : '' }}>{{ $khuvuc->khu_vuc }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group my-2 col-lg-5 col-md-12">
                        <label for="noi_dung">Lĩnh vực:</label>
                        <select class="form-control" id="noi_dung" name="noi_dung" required disabled>
                            <option value="">Chọn lĩnh vực</option>
                            <option value="Tổng sản lượng" {{ $kpiData->noi_dung == 'Tổng sản lượng' ? 'selected' : '' }}>Tổng sản lượng</option>
                            <option value="EC" {{ $kpiData->noi_dung == 'EC' ? 'selected' : '' }}>EC</option>
                            @foreach($noidungs as $noidung)
                                <option value="{{ $noidung->noi_dung }}" {{ $noidung->noi_dung == $kpiData->noi_dung ? 'selected' : '' }}>{{ $noidung->noi_dung }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group my-2 col-lg-2 col-md-12">
                        <label for="year">Năm:</label>
                        <select class="form-control" id="year" name="year" required disabled>
                            @for ($i = date('Y'); $i >= 2020; $i--)
                                <option value="{{ $i }}" {{ $i == $kpiData->year ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_quy_1">Quý 1:</label>
                        <input type="text" class="form-control" id="kpi_quy_1" name="kpi_quy_1" value="{{ $kpiData->kpi_quy_1 }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>

                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_quy_2">Quý 2:</label>
                        <input type="text" class="form-control" id="kpi_quy_2" name="kpi_quy_2" value="{{ $kpiData->kpi_quy_2 }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>

                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_quy_3">Quý 3:</label>
                        <input type="text" class="form-control" id="kpi_quy_3" name="kpi_quy_3" value="{{ $kpiData->kpi_quy_3 }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>

                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_quy_4">Quý 4:</label>
                        <input type="text" class="form-control" id="kpi_quy_4" name="kpi_quy_4" value="{{ $kpiData->kpi_quy_4 }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_thang_1">Tháng 1:</label>
                        <input type="text" class="form-control" id="kpi_thang_1" name="kpi_thang_1" value="{{ $kpiData->kpi_thang_1 }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>
                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_thang_2">Tháng 2:</label>
                        <input type="text" class="form-control" id="kpi_thang_2" name="kpi_thang_2" value="{{ $kpiData->kpi_thang_2 }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>
                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_thang_3">Tháng 3:</label>
                        <input type="text" class="form-control" id="kpi_thang_3" name="kpi_thang_3" value="{{ $kpiData->kpi_thang_3 }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>
                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_thang_4">Tháng 4:</label>
                        <input type="text" class="form-control" id="kpi_thang_4" name="kpi_thang_4" value="{{ $kpiData->kpi_thang_4 }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_thang_5">Tháng 5:</label>
                        <input type="text" class="form-control" id="kpi_thang_5" name="kpi_thang_5" value="{{ $kpiData->kpi_thang_5 }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>
                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_thang_6">Tháng 6:</label>
                        <input type="text" class="form-control" id="kpi_thang_6" name="kpi_thang_6" value="{{ $kpiData->kpi_thang_6 }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>
                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_thang_7">Tháng 7:</label>
                        <input type="text" class="form-control" id="kpi_thang_7" name="kpi_thang_7" value="{{ $kpiData->kpi_thang_7 }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>
                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_thang_8">Tháng 8:</label>
                        <input type="text" class="form-control" id="kpi_thang_8" name="kpi_thang_8" value="{{ $kpiData->kpi_thang_8 }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>
                </div>
                <div class="row">
                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_thang_9">Tháng 9:</label>
                        <input type="text" class="form-control" id="kpi_thang_9" name="kpi_thang_9" value="{{ $kpiData->kpi_thang_9 }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>
                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_thang_10">Tháng 10:</label>
                        <input type="text" class="form-control" id="kpi_thang_10" name="kpi_thang_10" value="{{ $kpiData->kpi_thang_10 }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>
                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_thang_11">Tháng 11:</label>
                        <input type="text" class="form-control" id="kpi_thang_11" name="kpi_thang_11" value="{{ $kpiData->kpi_thang_11 }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>
                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_thang_12">Tháng 12:</label>
                        <input type="text" class="form-control" id="kpi_thang_12" name="kpi_thang_12" value="{{ $kpiData->kpi_thang_12 }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>
                </div>
                

                <button type="submit" class="btn btn-primary">Cập nhật</button>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('khu_vuc').addEventListener('change', function() {
            var khuVuc = this.value;
            fetch(`/sanluong-khac/noidung/${khuVuc}`)
                .then(response => response.json())
                .then(data => {
                    var noiDungSelect = document.getElementById('noi_dung');
                    noiDungSelect.innerHTML = '<option value="Tổng sản lượng">Tổng sản lượng</option><option value="EC">EC</option>';
                    data.forEach(function(noidung) {
                        var option = document.createElement('option');
                        option.value = noidung.noi_dung;
                        option.text = noidung.noi_dung;
                        noiDungSelect.appendChild(option);
                    });
                });
        });
    </script>
</body>
</html>

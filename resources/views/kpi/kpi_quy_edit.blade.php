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
        <h2>Sửa KPI quý</h2>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="p-3 border">
            <form action="{{ route('kpiquy.handleEdit') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="form-group my-2 col-lg-6 col-md-12">
                        <label for="ten_khu_vuc">Tên khu vực:</label>
                        <input type="text" class="form-control" id="ten_khu_vuc" name="ten_khu_vuc" value="{{ $kpiData['ten_khu_vuc'] }}" readonly>
                    </div>
                    <div class="form-group my-2 col-lg-6 col-md-12">
                        <label for="year">Năm:</label>
                        <select class="form-control" id="year" name="year" required>
                            @for ($i = date('Y'); $i >=2020; $i--)
                            <option value="{{ $i }}" {{ $i == $kpiData['year'] ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_quy_1">KPI Quý 1:</label>
                        <input type="text" class="form-control" id="kpi_quy_1" name="kpi_quy_1" value="{{ $kpiData['kpi_quy_1'] }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>

                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_quy_2">KPI Quý 2:</label>
                        <input type="text" class="form-control" id="kpi_quy_2" name="kpi_quy_2" value="{{ $kpiData['kpi_quy_2'] }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>

                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_quy_3">KPI Quý 3:</label>
                        <input type="text" class="form-control" id="kpi_quy_3" name="kpi_quy_3" value="{{ $kpiData['kpi_quy_3'] }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>

                    <div class="form-group my-2 col-lg-3 col-md-12">
                        <label for="kpi_quy_4">KPI Quý 4:</label>
                        <input type="text" class="form-control" id="kpi_quy_4" name="kpi_quy_4" value="{{ $kpiData['kpi_quy_4'] }}" required pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Cập nhật</button>
            </form>
        </div>
    </div>
</body>
</html>

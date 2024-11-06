@include('layouts.head_thongke')
<body>
    @include('layouts.header_thongke')
    <div class="container row">
        <div class="col-12 breadcrumb-wrapper mt-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a class="simple-link" href="/">Tổng quát</a></li>
                    <li class="breadcrumb-item active" aria-current="page">KPI Quý</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="container mt-3">
        <div class="main-content px-3">
            <div id="results">
                <div class="mb-2" style="max-width:500px;">
                    @if ( session('role') == 3)
                    <form method="GET" action="{{ route('kpiquy.index') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <select name="khu_vuc" class="form-control" required>
                                    <option value="">Chọn khu vực</option>
                                    @foreach ($khu_vucs as $khu_vuc)
                                        <option value="{{ $khu_vuc->khu_vuc }}" {{ request('khu_vuc') == $khu_vuc->khu_vuc ? 'selected' : '' }}>{{ $khu_vuc->khu_vuc }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="year" class="form-control" required>
                                    <option value="">Chọn năm</option>
                                    @foreach ($years as $year)
                                        <option value="{{ $year }}" {{ request('year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">Lọc</button>
                            </div>
                        </div>
                    </form>
                    @endif
                </div>
                @if ( session('role') == 3)
                <table class="scrollable-table mb-3">
                    <thead>
                        <tr>
                            <th>Số thứ tự</th>
                            <th>Khu vực</th>
                            <th>Lĩnh vực</th>
                            <th>Năm</th>
                            <th>KPI Năm</th>
                            <th>Quý 1</th>
                            <th>Quý 2</th>
                            <th>Quý 3</th>
                            <th>Quý 4</th>
                            <th>Tháng 1</th>
                            <th>Tháng 2</th>
                            <th>Tháng 3</th>
                            <th>Tháng 4</th>
                            <th>Tháng 5</th>
                            <th>Tháng 6</th>
                            <th>Tháng 7</th>
                            <th>Tháng 8</th>
                            <th>Tháng 9</th>
                            <th>Tháng 10</th>
                            <th>Tháng 11</th>
                            <th>Tháng 12</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $index = 1; @endphp
                        @foreach ($kpi as $row)
                        <tr>
                            <td>{{ $index++ }}</td>
                            <td>{{ $row->ten_khu_vuc }}</td>
                            <td>{{ $row->noi_dung }}</td>
                            <td>{{ $row->year }}</td>
                            <td>{{ $row->kpi_nam ?? '-' }}</td>
                            <td>{{ $row->kpi_quy_1 ?? '-' }}</td>
                            <td>{{ $row->kpi_quy_2 ?? '-' }}</td>
                            <td>{{ $row->kpi_quy_3 ?? '-' }}</td>
                            <td>{{ $row->kpi_quy_4 ?? '-' }}</td>
                            <td>{{ $row->kpi_thang_1 ?? '-' }}</td>
                            <td>{{ $row->kpi_thang_2 ?? '-' }}</td>
                            <td>{{ $row->kpi_thang_3 ?? '-' }}</td>
                            <td>{{ $row->kpi_thang_4 ?? '-' }}</td>
                            <td>{{ $row->kpi_thang_5 ?? '-' }}</td>
                            <td>{{ $row->kpi_thang_6 ?? '-' }}</td>
                            <td>{{ $row->kpi_thang_7 ?? '-' }}</td>
                            <td>{{ $row->kpi_thang_8 ?? '-' }}</td>
                            <td>{{ $row->kpi_thang_9 ?? '-' }}</td>
                            <td>{{ $row->kpi_thang_10 ?? '-' }}</td>
                            <td>{{ $row->kpi_thang_11 ?? '-' }}</td>
                            <td>{{ $row->kpi_thang_12 ?? '-' }}</td>
                            <td>
                                @if($row->noi_dung !== 'Tổng sản lượng')
                                <a href="{{ url('/kpi-quy/edit/' . $row->id) }}">
                                <button class="btn btn-primary">Sửa</button>
                                </a>
                                <a href="{{ url('/kpi-quy/delete/' . $row->id) }}" onclick="return confirm('Bạn có muốn xóa KPI này?');">
                                    <button class="btn btn-danger">Xóa</button>
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="alert alert-danger container">
                    Bạn không đủ thẩm quyền để xem KPI.
                </div>
                @endif
            </div>
        </div>
    </div>
    <style>
        #results {
            overflow-y: auto;
        }
    </style>
</body>
</html>

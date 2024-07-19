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
                <div class="d-flex justify-content-start mb-2">
                    @if ( session('role') == 3)
                        <a href="{{route('kpiquy.add')}}"><button class="btn btn-primary">Thêm</button></a>
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
                            <th>Quý 1</th>
                            <th>Quý 2</th>
                            <th>Quý 3</th>
                            <th>Quý 4</th>
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
                            <td>{{ $row->kpi_quy_1 ?? '-' }}</td>
                            <td>{{ $row->kpi_quy_2 ?? '-' }}</td>
                            <td>{{ $row->kpi_quy_3 ?? '-' }}</td>
                            <td>{{ $row->kpi_quy_4 ?? '-' }}</td>
                            <td>
                                <a href="{{ url('/kpi-quy/edit/' . $row->id) }}">
                                <button class="btn btn-primary">Sửa</button>
                                </a>
                                <a href="{{ url('/kpi-quy/delete/' . $row->id) }}" onclick="return confirm('Bạn có muốn xóa KPI này?');">
                                    <button class="btn btn-danger">Xóa</button>
                                </a>
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

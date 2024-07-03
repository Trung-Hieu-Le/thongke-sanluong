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
    {{-- TODO: phân quyền --}}
    <div class="container mt-3">
        <div class="main-content px-3">
            <div id="results">
                @if (session('role') == 2 || session('role') == 3)
                    <a href="{{route('kpiquy.add')}}"><button class="btn btn-primary">Thêm</button></a>
                @endif
                <table class="scrollable-table">
                    <thead>
                        <tr>
                            <th>Số thứ tự</th>
                            <th>Khu vực</th>
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
                        @foreach ($data as $row)
                        <tr>
                            <td>{{ $index++ }}</td>
                            <td>{{ $row['ten_khu_vuc'] }}</td>
                            <td>{{ $row['year'] }}</td>
                            <td>{{ $row['quarters'][0] ?? '-' }}</td>
                            <td>{{ $row['quarters'][1] ?? '-' }}</td>
                            <td>{{ $row['quarters'][2] ?? '-' }}</td>
                            <td>{{ $row['quarters'][3] ?? '-' }}</td>
                            <td>
                                <a href="{{ url('/kpi-quy/edit?khuvuc=' . $row['ten_khu_vuc'] . '&nam=' . $row['year']) }}">
                                <button class="btn btn-primary">Sửa</button>
                                </a>
                                <a href="{{ url('/kpi-quy/delete?khuvuc=' . $row['ten_khu_vuc'] . '&nam=' . $row['year']) }}">
                                    <button class="btn btn-danger">Xóa</button>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <style>
        .main-content {
            width: 100%;
            overflow-y: auto;
        }
        #results {
            overflow-y: auto;
        }
        .scrollable-table {
            width: 100%;
            border-collapse: collapse;
        }
        .scrollable-table th, .scrollable-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .scrollable-table th {
            background-color: #f2f2f2;
            text-align: left;
        }
    </style>
</body>
</html>

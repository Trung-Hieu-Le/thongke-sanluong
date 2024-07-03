@include('layouts.head_thongke')
<body>
    @include('layouts.header_thongke')
    <div class="container row">
        <div class="col-12 breadcrumb-wrapper mt-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a class="simple-link" href="/">Tổng quát</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Sản Lượng Khác</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="container mt-3">
        <div class="main-content px-3">
            <div id="results">
                @if (session('role') == 2 || session('role') == 3)
                    <a href="{{ route('sanluongkhac.add') }}">
                        <button class="btn btn-primary">Thêm</button>
                    </a>
                @endif
                <table class="scrollable-table">
                    <thead>
                        <tr>
                            <th>Số thứ tự</th>
                            <th>Trạm</th>
                            <th>Ngày</th>
                            <th>Giá</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $index = 1; @endphp
                        @foreach ($data as $row)
                        <tr>
                            <td>{{ $index++ }}</td>
                            <td>{{ $row->SanLuong_Tram }}</td>
                            <td>{{ $row->SanLuong_Ngay }}</td>
                            <td>{{ $row->SanLuong_Gia }}</td>
                            <td>
                                <a href="{{ url('/sanluongkhac/edit?id=' . $row->SanLuong_Id) }}">
                                    <button class="btn btn-primary">Sửa</button>
                                </a>
                                <a href="{{ url('/sanluongkhac/delete/' . $row->SanLuong_Id) }}">
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

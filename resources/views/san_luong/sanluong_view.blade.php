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
                <div class="d-flex justify-content-start mb-2">
                    @if (session('role') == 2 || session('role') == 3)
                        <a href="{{ route('sanluongkhac.add') }}">
                            <button class="btn btn-primary">Thêm</button>
                        </a>
                    @endif
                </div>
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
                        @php 
                            $index = 1;
                            use Carbon\Carbon; 
                        @endphp
                        @foreach ($data as $row)
                        <tr>
                            <td>{{ $index++ }}</td>
                            <td>{{ $row->SanLuong_Tram }}</td>
                            <td>{{ Carbon::createFromFormat('dmY', $row->SanLuong_Ngay)->format('d-m-Y') }}</td>
                            <td>{{ number_format($row->SanLuong_Gia, 3) }}</td>
                            <td>
                                <a href="{{ url('/sanluong-khac/edit/' . $row->SanLuong_Id) }}">
                                    <button class="btn btn-primary">Sửa</button>
                                </a>
                                <a href="{{ url('/sanluong-khac/delete/' . $row->SanLuong_Id) }}" onclick="return confirm('Bạn có muốn xóa Sản lượng này?');">
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
    </style>
</body>
</html>

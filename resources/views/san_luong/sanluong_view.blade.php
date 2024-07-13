@include('layouts.head_thongke')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
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
                <div class="d-flex mb-2">
                    <div>
                        <form id="filterForm" method="GET" action="{{ route('sanluongkhac.index') }}" class="form-inline">
                            @csrf
                            <div class="">
                                <div class="form-group">
                                    <input type="text" class="form-control date" name="days" placeholder="Chọn các ngày lọc" value="{{ implode(',', $days) }}">
                                    <input type="text" class="form-control" name="search" placeholder="Tìm hạng mục" value="{{ $search }}">
                                    <button class="btn btn-primary mb-1" type="submit">Lọc</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="justify-content-end ms-2">
                        @if (session('role') == 2 || session('role') == 3)
                            <a href="{{ route('sanluongkhac.add') }}">
                                <button class="btn btn-success">Thêm</button>
                            </a>
                        @endif
                    </div>
                </div>
                
                <table class="scrollable-table">
                    <thead>
                        <tr>
                            <th>Số thứ tự</th>
                            <th>Khu vực</th>
                            <th>Hạng mục</th>
                            <th>Ngày</th>
                            <th>Giá</th>
                            <th>Nhân viên</th>
                            {{-- <th>Ngày thêm</th> --}}
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
                            {{-- <td>{{ $row->SanLuong_Tram }}</td> --}}
                            <td>{{ $row->SanLuong_KhuVuc }}</td>
                            <td>{{ $row->SanLuong_TenHangMuc }}</td>
                            <td>{{ Carbon::createFromFormat('dmY', $row->SanLuong_Ngay)->format('d-m-Y') }}</td>
                            <td>{{ number_format($row->SanLuong_Gia, 3) }}</td>
                            <td>    
                                @if (isset($users[$row->user_id]))
                                    {{ $users[$row->user_id]->user_name }}
                                @else
                                    Không xác định
                                @endif
                            </td>
                            {{-- <td>{{ $row->thoi_gian_them }}</td> --}}
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
    <script>
        $(document).ready(function(){
            const selectedDays = @json($days).map(day => new Date(day.slice(4, 8) + '-' + day.slice(2, 4) + '-' + day.slice(0, 2)));
            
            $('.date').datepicker({
                multidate: true,
                format: 'dd-mm-yyyy'
            }).datepicker('setDates', selectedDays).on('changeDate', function(e) {
                const selectedDates = $(this).datepicker('getFormattedDate');
                $('input[name="days"]').val(selectedDates);
            });
        });
    </script>
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

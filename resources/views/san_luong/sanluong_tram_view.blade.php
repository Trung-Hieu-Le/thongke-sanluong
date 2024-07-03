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
                    <li class="breadcrumb-item"><a class="simple-link" href="/thongke/filter">Lọc sản lượng</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Chi tiết sản lượng trạm</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="container mt-3 d-flex flex-column">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" href="/viewsanluong/{{ $ma_tram }}?days={{ implode(',', $days) }}">Sản lượng</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/viewhinhanh/{{ $ma_tram }}?days={{ implode(',', $days) }}">Hình ảnh</a>
            </li>
        </ul>

        <div class="main-content px-3 mt-3">
            <form id="filterForm" method="GET" action="{{ url('/viewsanluong/'.$ma_tram) }}" class="form-inline mb-3">
                @csrf
                <div class="form-group">
                        <input type="text" class="form-control date" name="days" placeholder="Chọn các ngày lọc" value="{{ implode(',', $days) }}">
                        <button class="btn btn-primary mb-1" type="submit">Lọc</button>
                </div>
            </form>
            <div id="results">
                <table class="scrollable-table mb-3">
                    <thead>
                        <tr>
                            <th>Số thứ tự</th>
                            <th>Ngày</th>
                            <th>Hạng mục</th>
                            <th>Đơn giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
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
                            <td>{{ Carbon::createFromFormat('dmY', $row->SanLuong_Ngay)->format('d-m-Y') }}</td>
                            <td>{{ $row->SanLuong_TenHangMuc }}</td>
                            <td>{{ number_format($row->SanLuong_Gia, 3) }}</td>
                            <td>{{ $row->SoLuong }}</td> <!-- Default value for Số lượng -->
                            <td>{{ number_format($row->SanLuong_Gia * $row->SoLuong, 3) }}</td> <!-- Đơn giá * Số lượng (which is 1) -->
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
        #results {
            overflow-y: auto;
        }
    </style>
</body>
</html>

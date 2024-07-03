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
                    <li class="breadcrumb-item active" aria-current="page">Lọc sản lượng</li>
                </ol>
            </nav>
        </div>
        <div class="mt-3">
            <form id="filterForm" method="GET" action="{{ route('tram.filter') }}" class="form-inline">
                @csrf
                <div class="">
                    <div class="form-group row">
                        <div class="form-group col-5">
                            <input type="text" class="form-control date" name="days" placeholder="Chọn các ngày lọc" value="{{ implode(',', $days) }}">
                        </div>
                        <div class="form-group col-5">
                            <input type="text" class="form-control" name="search" placeholder="Tìm mã trạm" value="{{ $search }}">
                        </div>
                        <div class="form-group col-2">
                            <button class="btn btn-primary" type="submit">Lọc</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="container mt-3">
        <div class="main-content px-3">
            <div id="results">
                <table class="scrollable-table">
                    <thead>
                        <tr>
                            <th>Số thứ tự</th>
                            <th>Tên trạm</th>
                            <th>Khu vực</th>
                            <th>SanLuong_Gia</th>
                            <th>Tỉnh</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $index = 1; @endphp
                        @foreach ($data as $row)
                        <tr>
                            <td>{{ $index++ }}</td>
                            <td>{{ $row->SanLuong_Tram }}</td>
                            <td>{{ $row->ten_khu_vuc }}</td>
                            <td>{{ $row->SanLuong_Gia }}</td>
                            <td>{{ $row->ma_tinh }}</td>
                            <td><a class="simple-link" href="{{ url('/viewsanluong/'.$row->SanLuong_Tram.'?days='.implode(',', $days)) }}">Xem</a></td>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $data->appends(['days' => implode(',', $days)])->links() }}
                </div>
            </div>
            <div id="totalResults">
                Tổng giá trị: {{ number_format($totalGia, 3, ',', '.') }}
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
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            height: 70vh;
        }
        #results {
            flex: 1;
            overflow-y: auto;
        }
        #totalResults {
            padding: 10px;
            border-top: 1px solid #ddd;
            background-color: #f9f9f9;
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

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
                    {{-- <div class="form-group row">
                        <div class="form-group col-5">
                            <input type="text" class="form-control date" name="days" placeholder="Chọn các ngày lọc" value="{{ implode(',', $days) }}">
                        </div>
                        <div class="form-group col-5">
                            <input type="text" class="form-control" name="search" placeholder="Tìm mã trạm" value="{{ $search }}">
                        </div>
                        <div class="form-group col-2">
                            <button class="btn btn-primary" type="submit">Lọc</button>
                        </div>
                    </div> --}}
                    <div class="form-group">
                        <input type="text" class="form-control date" name="days" placeholder="Chọn các ngày lọc" value="{{ implode(',', $days) }}">
                        <input type="text" class="form-control" name="searchMaTram" placeholder="Tìm mã trạm" value="{{ $searchMaTram }}">
                        <input type="text" class="form-control" name="searchHopDong" placeholder="Tìm hợp đồng" value="{{ $searchHopDong }}">
                        <input type="text" class="form-control" name="searchKhuVuc" placeholder="Tìm khu vực" value="{{ $searchKhuVuc }}">
                        <button class="btn btn-primary mb-1" type="submit">Lọc</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="container mt-3">
        <div class="main-content px-3 mb-3">
            <div id="results">
                <table class="scrollable-table">
                    <thead>
                        <tr>
                            <th>Số thứ tự</th>
                            <th>Tên trạm</th>
                            <th>Hợp đồng</th>
                            <th>Khu vực</th>
                            <th>Thành tiền</th>
                            <th>Tỉnh</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $index = 1; @endphp
                        @foreach ($pagedData as $row)
                        <tr>
                            <td>{{ $index++ }}</td>
                            <td>{{ $row->SanLuong_Tram }}</td>
                            <td>
                                {{ $row->HopDong_SoHopDong ?? 'Không có' }}
                            </td>
                            <td>{{ $row->ten_khu_vuc }}</td>
                            <td>{{ number_format($row->SanLuong_Gia, 3) }}</td>
                            <td>{{ $row->ma_tinh }}</td>
                            <td><a class="simple-link" href="{{ url('/viewsanluong/'.$row->SanLuong_Tram.'?sohopdong='.$row->HopDong_SoHopDong.'&days='.implode(',', $days)) }}">Xem</a></td>
                        @endforeach
                    </tbody>
                </table>
                <div class="my-3">
                    {{ $pagedData->appends(['days' => implode(',', $days), 'searchMaTram' => $searchMaTram, 'searchHopDong' => $searchHopDong, 'searchKhuVuc' => $searchKhuVuc])->links() }}
                </div>
            </div>
            <div id="totalResults">
                {{-- Tổng giá trị: {{ number_format($totalGia, 3, ',', '.') }} --}}
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Tên Khu Vực</th>
                            <th>Số Trạm</th>
                            <th>Tổng Sản Lượng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalStations = 0;
                            $totalProduction = 0;
                        @endphp
                        @foreach($khuVucData as $item)
                        <tr>
                            <td>{{ $item['ten_khu_vuc'] ?? 'Khác' }}</td>
                            <td>{{ $item['so_tram'] }}</td>
                            <td>{{ number_format($item['tong_san_luong'], 3) }}</td>
                        </tr>
                        @php
                            $totalStations += $item['so_tram'];
                            $totalProduction += $item['tong_san_luong'];
                        @endphp
                    @endforeach
                    
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="text-danger">Tổng</td>
                            <td class="text-danger">{{ $totalStations }}</td>
                            <td class="text-danger">{{ number_format($totalProduction, 3) }}</td>
                        </tr>
                    </tfoot>
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
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        #results {
            flex: 1;
            overflow-y: auto;
            height: 40vh;

        }
        #totalResults {
            padding: 10px;
            border-top: 1px solid #ddd;
            background-color: #f9f9f9;
        }
    </style>
</body>
</html>

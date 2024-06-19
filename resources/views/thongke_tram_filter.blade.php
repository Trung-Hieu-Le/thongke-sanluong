@include('layouts.head_thongke')
<body>
    @include('layouts.header_thongke')
    <div class="container-fluid mt-3">
        <div class="sidebar px-2 py-4">
            <form id="filterForm" method="GET" action="{{ route('tram.filter') }}">
                @csrf
                <div class="row">
                    <div class="col-4">
                        {{-- <label for="selectMonth">Tháng:</label> --}}
                        <select class="form-control" id="selectMonth" name="month" onchange="updateURL()">
                            @for ($month = 1; $month <= 12; $month++)
                                <option value="{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}" {{ $month == $selectedMonth ? 'selected' : '' }}>
                                    Tháng {{ $month }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-4">
                        {{-- <label for="selectYear">Năm:</label> --}}
                        <select class="form-control" id="selectYear" name="year" onchange="updateURL()">
                            @for ($year = date('Y'); $year >= 2020; $year--)
                                <option value="{{ $year }}" {{ $year == $selectedYear ? 'selected' : '' }}>
                                    Năm {{ $year }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <button class="btn btn-primary submit-btn col-4 mx-3 w-auto" type="submit">Lọc</button>
                </div>
                <div id="dayCheckboxes">
                    <!-- Checkboxes for days will be added here dynamically -->
                </div>
            </form>
        </div>
        <div class="main-content px-3">
            <div id="results">
                <table class="scrollable-table">
                    <thead>
                        <tr>
                            <th>Trạm</th>
                            <th>Ngày</th>
                            <th>Giá</th>
                            <th>Hạng Mục</th>
                            <th>Ảnh đã xong</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $row)
                        <tr>
                            <td>{{ $row->SanLuong_Tram }}</td>
                            <td>{{ $row->SanLuong_Ngay }}</td>
                            <td>{{ number_format($row->SanLuong_Gia, 3, ',', '.') }}</td>
                            <td>{{ $row->SanLuong_TenHangMuc }}</td>
                            <td>{{ $row->ten_hinh_anh_da_xong }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-3">
                    {{ $data->appends(['days' => $days, 'month' => $selectedMonth, 'year' => $selectedYear])->links() }}
                </div>
            </div>
            <div id="totalResults">
                Tổng giá trị: {{ number_format($totalGia, 3, ',', '.') }}
            </div>
        </div>
    </div>
    <script>
        function updateURL() {
            const month = $('#selectMonth').val();
            const year = $('#selectYear').val();
            $('#inputMonth').val(month);
            $('#inputYear').val(year);
            window.history.replaceState(null, null, `?month=${month}&year=${year}`);
        }

        $(document).ready(function() {
            const selectedDays = @json($days);

            function fetchDays() {
                const month = $('#selectMonth').val();
                const year = $('#selectYear').val();
                if (month && year) {
                    $.ajax({
                        url: '/thongke/filter/get-day',
                        method: 'GET',
                        data: { thang: month, nam: year },
                        success: function(data) {
                            $('#dayCheckboxes').empty();
                            data.forEach(day => {
                                const isChecked = selectedDays.includes(day);
                                const checkedAttribute = isChecked ? 'checked' : '';
                                $('#dayCheckboxes').append(`
                                    <div>
                                        <input type="checkbox" id="day-${day}" name="days[]" value="${day}" ${checkedAttribute}>
                                        <label for="day-${day}">${day}</label>
                                    </div>
                                `);
                            });
                        }
                    });
                }
            }

            $('#selectMonth, #selectYear').on('change', fetchDays);

            fetchDays();
        });
    </script>
    <style>
        .container-fluid {
            display: flex;
            height: 90vh;
        }
        .sidebar {
            width: 20%;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            overflow-y: auto;
        }
        .main-content {
            width: 80%;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
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
        @media (max-width: 1000px) {
            .container-fluid {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: 10vh;
            }
            .main-content {
                width: 100%;
                height: calc(100vh - 10vh);
            }
        }
    </style>
</body>
</html>

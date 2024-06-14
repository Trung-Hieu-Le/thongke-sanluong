@include('layouts.header_thongke')
<body>
    <header class="header">
        <a href="/">
            <img src="{{ asset('images/vtk_logo.jpg') }}" alt="Logo">
        </a>
    </header>
    <h1>Thống kê sản lượng</h1>
    
    <div>
        <label for="selectMonth">Chọn tháng:</label>
        <select id="selectMonth" name="month" onchange="updateURL()">
            @for ($month = 1; $month <= 12; $month++)
                <option value="{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}" {{ $month == $selectedMonth ? 'selected' : '' }}>
                    {{ $month }}
                </option>
            @endfor
        </select>
        
        <label for="selectYear">Chọn năm:</label>
        <select id="selectYear" name="year" onchange="updateURL()">
            @for ($year = date('Y'); $year >= 2000; $year--)
                <option value="{{ $year }}" {{ $year == $selectedYear ? 'selected' : '' }}>
                    {{ $year }}
                </option>
            @endfor
        </select>
    </div>
    
    <form id="filterForm" method="GET" action="{{ route('tram.filter') }}">
        @csrf
        <input type="hidden" id="inputMonth" name="month" value="{{ $selectedMonth }}">
        <input type="hidden" id="inputYear" name="year" value="{{ $selectedYear }}">
        <div id="dayCheckboxes">
            <!-- Checkboxes for days will be added here dynamically -->
        </div>
        <button type="submit">Lọc</button>
    </form>
    
    <h2>Kết quả sản lượng</h2>
    <div id="results">
        <table id="resultsTable">
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
                    <td>{{ $row->SanLuong_Gia }}</td>
                    <td>{{ $row->SanLuong_TenHangMuc }}</td>
                    <td>{{ $row->ten_hinh_anh_da_xong }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $data->appends(['days' => $days, 'month' => $selectedMonth, 'year' => $selectedYear])->links() }}
    </div>
    
    <h2>Tổng sản lượng</h2>
    <div id="totalResults">
        Tổng giá trị: {{ $totalGia }}
    </div>
    <script>
        $(document).ready(function() {
            const selectedDays = @json($days);

            function fetchDays() {
                const month = $('#selectMonth').val();
                const year = $('#selectYear').val();
                if (month && year) {
                    $.ajax({
                        url: '{{ route('tram.filter.days') }}',
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

            function updateURL() {
                const month = $('#selectMonth').val();
                const year = $('#selectYear').val();
                $('#inputMonth').val(month);
                $('#inputYear').val(year);

                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('month', month);
                urlParams.set('year', year);
                window.history.replaceState(null, null, "?" + urlParams.toString());

                fetchDays();
            }

            $('#selectMonth, #selectYear').on('change', updateURL);

            fetchDays();
        });
    </script>
</body>
</html>

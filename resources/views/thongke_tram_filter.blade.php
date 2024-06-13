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
        <select id="selectMonth"></select>
        
        <label for="selectYear">Chọn năm:</label>
        <select id="selectYear"></select>
    </div>
    
    <form id="filterForm" method="POST" action="/thongke/filter">
        @csrf
        <div id="dayCheckboxes">
            <!-- Checkboxes for days will be added here dynamically -->
        </div>
        {{-- <input type="hidden" name="filtered" value="true"> --}}
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
        {{ $data->links() }}
    </div>
    
    <h2>Tổng sản lượng</h2>
    <div id="totalResults">
        Tổng giá trị: {{ $totalGia }}
    </div>
    <script>
        $(document).ready(function() {
            // Set default month and year to current month and year
            const currentDate = new Date();
            const currentMonth = String(currentDate.getMonth() + 1).padStart(2, '0');
            const currentYear = currentDate.getFullYear();
            
            // Populate month and year dropdowns
            for (let month = 1; month <= 12; month++) {
                const monthValue = month.toString().padStart(2, '0');
                const selected = monthValue === currentMonth ? 'selected' : '';
                $('#selectMonth').append(`<option value="${monthValue}" ${selected}>${month}</option>`);
            }
            for (let year = currentYear; year >= 2000; year--) {
                const selected = year === currentYear ? 'selected' : '';
                $('#selectYear').append(`<option value="${year}" ${selected}>${year}</option>`);
            }

            // Fetch days when month or year changes
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
                                $('#dayCheckboxes').append(`
                                    <div>
                                        <input type="checkbox" id="day-${day}" name="days[]" value="${day}">
                                        <label for="day-${day}">${day}</label>
                                    </div>
                                `);
                            });
                        }
                    });
                }
            }

            $('#selectMonth, #selectYear').on('change', fetchDays);

            // Initially fetch days for current month and year
            fetchDays();

            // On form submit, append selected checkboxes to the form
            $('#filterButton').on('click', function() {
                $('#filterForm').submit();
            });
        });
    </script>
</body>
</html>
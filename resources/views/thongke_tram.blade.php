@include('layouts.header_thongke')
<body>
    <header class="header">
        <a href="/">
            <img src="{{ asset('images/vtk_logo.jpg') }}" alt="Logo">
        </a>
        {{-- <h1>Thống kê sản lượng các Tỉnh</h1> --}}
    </header>
    <div class="container mt-3">
        {{-- <div class="breadcrumb">
            <a class="simple-link" href="/thongke">Thống kê tỉnh</a> > 
            <span>Thống kê trạm</span>
        </div> --}}
        <h2>Biểu đồ sản lượng của tỉnh {{$maTinhChose}} theo thời gian</h2>
        <center>
            <div class="mt-3">
                <span>Thống kê sản lượng các Trạm: </span>
                <span>Theo thời gian </span>
                <select class="form-control" id="thoi-gian-chon">
                    <option value="ngay">Ngày</option>
                    <option value="tuan">Tuần</option>
                    <option value="thang">Tháng</option>
                    <option value="quy">Quý</option>
                    <option value="nam">Năm</option>
                </select>
                <span>Chi tiết </span>
                <input type="date" id="ngay-chon">
            </div>
        </center>
    </div>
    
    <div class="container">
        <div class="bar-chart">
            <canvas id="chart"></canvas>
        </div>
        <h2>Bảng thống kê chi tiết của tỉnh {{$maTinhChose}}:</h2>
        <div class="scrollable-table">
            <table id="data-table">
                <thead>
                    <tr>
                        <th>Trạm</th>
                        <th>Tổng Năm</th>
                        <th>Quý 1</th>
                        <th>Quý 2</th>
                        <th>Quý 3</th>
                        <th>Quý 4</th>
                        <th>Tháng 1</th>
                        <th>Tháng 2</th>
                        <th>Tháng 3</th>
                        <th>Tháng 4</th>
                        <th>Tháng 5</th>
                        <th>Tháng 6</th>
                        <th>Tháng 7</th>
                        <th>Tháng 8</th>
                        <th>Tháng 9</th>
                        <th>Tháng 10</th>
                        <th>Tháng 11</th>
                        <th>Tháng 12</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Thêm dữ liệu từ cơ sở dữ liệu -->
                </tbody>
            </table>     
        </div>
    </div>         

    <script>
        // Khởi tạo biểu đồ
        var ctx = document.getElementById('chart').getContext('2d');
        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Tổng Sản Lượng',
                    data: [],
                    backgroundColor: '#FE504F',
                    borderColor: 'red',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false,
                        labels: {
                            color: 'white'
                        }
                    }
                }
            }
        });

        // Hàm cập nhật dữ liệu
        var maTinhChose = "<?php echo $maTinhChose; ?>";
        function updateChart(time_format, ngay_chon) {
            $.ajax({
                url: `/thongke/tinh/${maTinhChose}/all`,
                method: 'GET',
                data: { time_format: time_format, ngay: ngay_chon },
                success: function(data) {
                    var labels = [];
                    var values = [];

                    data.forEach(function(item) {
                        labels.push(item.SanLuong_Tram);
                        values.push(item.total);
                    });

                    chart.data.labels = labels;
                    chart.data.datasets[0].data = values;

                    chart.update();
                }
            });
        }
        function updateTable() {
            $.ajax({
                url: `/thongke/tinh/${maTinhChose}/tongquat`,
                method: 'GET',
                success: function(data) {
                    var tbody = $('#data-table tbody');
                    tbody.empty();

                    data.forEach(function(item) {
                        var row = `
                            <tr>
                                <td class="ma-tinh">${item.ma_tram}</td>
                                <td>${Intl.NumberFormat('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(item.tong_san_luong.nam)}</td>
                                <td>${Intl.NumberFormat('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(item.tong_san_luong.quy_1)}</td>
                                <td>${Intl.NumberFormat('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(item.tong_san_luong.quy_2)}</td>
                                <td>${Intl.NumberFormat('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(item.tong_san_luong.quy_3)}</td>
                                <td>${Intl.NumberFormat('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(item.tong_san_luong.quy_4)}</td>
                                <td>${Intl.NumberFormat('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(item.tong_san_luong.thang_1)}</td>
                                <td>${Intl.NumberFormat('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(item.tong_san_luong.thang_2)}</td>
                                <td>${Intl.NumberFormat('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(item.tong_san_luong.thang_3)}</td>
                                <td>${Intl.NumberFormat('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(item.tong_san_luong.thang_4)}</td>
                                <td>${Intl.NumberFormat('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(item.tong_san_luong.thang_5)}</td>
                                <td>${Intl.NumberFormat('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(item.tong_san_luong.thang_6)}</td>
                                <td>${Intl.NumberFormat('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(item.tong_san_luong.thang_7)}</td>
                                <td>${Intl.NumberFormat('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(item.tong_san_luong.thang_8)}</td>
                                <td>${Intl.NumberFormat('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(item.tong_san_luong.thang_9)}</td>
                                <td>${Intl.NumberFormat('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(item.tong_san_luong.thang_10)}</td>
                                <td>${Intl.NumberFormat('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(item.tong_san_luong.thang_11)}</td>
                                <td>${Intl.NumberFormat('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(item.tong_san_luong.thang_12)}</td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                }
            });
        }

        $('#thoi-gian-chon').on('change', function() {
            var selectedTimeFrame = $(this).val();
            var selectedNgay = $('#ngay-chon').val();
            updateChart(selectedTimeFrame, selectedNgay);
            updateTable();
        });

        $('#ngay-chon').on('change', function() {
            var selectedTimeFrame = $('#thoi-gian-chon').val();
            var selectedNgay = $(this).val();
            updateChart(selectedTimeFrame, selectedNgay);
        });

        $(document).ready(function() {
            var initialTimeFrame = 'ngay'; // Thời gian mặc định là ngày
            $('#thoi-gian-chon').val(initialTimeFrame);
            updateChart(initialTimeFrame, null);
            updateTable();
        });

        // Cập nhật biểu đồ và bảng theo thời gian
        setInterval(function() {
            var selectedTimeFrame = $('#thoi-gian-chon').val();
            var selectedNgay = $('#ngay-chon').val();
            updateChart(selectedTimeFrame, selectedNgay);
            updateTable();
        }, 3600000);
    
    </script>
</body>
</html>

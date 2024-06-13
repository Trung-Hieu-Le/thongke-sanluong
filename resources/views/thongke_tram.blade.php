@include('layouts.header_thongke')
<body>
    <header class="header">
        <a href="/">
            <img src="{{ asset('images/vtk_logo.jpg') }}" alt="Logo">
        </a>
        {{-- <h1>Thống kê sản lượng các Tỉnh</h1> --}}
    </header>
    <div class="container mt-3">
        <div class="row">
            <div class="col-12 breadcrumb-wrapper mt-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a class="simple-link" href="/">Tổng quát</a></li>
                        <li class="breadcrumb-item"><a class="simple-link" href="/thongke/khuvuc">Khu vực</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Tỉnh</li>
                    </ol>
                </nav>
            </div>
        </div>
        {{-- <div class="breadcrumb">
            <a class="simple-link" href="/thongke">Thống kê tỉnh</a> > 
            <span>Thống kê trạm</span>
        </div> --}}
        <h2>Biểu đồ sản lượng của tỉnh {{$maTinhChose}} theo thời gian</h2>
        <center>
            <div class="mt-3">
                <span>Thống kê sản lượng các Trạm: </span>
                {{-- <span>Theo thời gian </span>
                <select class="form-control" id="thoi-gian-chon">
                    <option value="ngay">Ngày</option>
                    <option value="tuan">Tuần</option>
                    <option value="thang">Tháng</option>
                    <option value="quy">Quý</option>
                    <option value="nam">Năm</option>
                </select> --}}
                <span>Chi tiết ngày </span>
                <input type="date" id="ngay-chon" value="{{ date('Y-m-d') }}">
            </div>
        </center>
    </div>
    
    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-md-12">
                <div class="bar-chart">
                    <canvas id="chart-ngay"></canvas>
                    <p class="text-center">Thống kê theo ngày</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="bar-chart">
                    <canvas id="chart-tuan"></canvas>
                    <p class="text-center">Thống kê theo tuần</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="bar-chart">
                    <canvas id="chart-thang"></canvas>
                    <p class="text-center">Thống kê theo tháng</p>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="bar-chart">
                    <canvas id="chart-quy"></canvas>
                    <p class="text-center">Thống kê theo quý</p>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="bar-chart">
                    <canvas id="chart-nam"></canvas>
                    <p class="text-center">Thống kê theo năm</p>
                </div>
            </div>
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
        function createChart(ctx) {
            return new Chart(ctx, {
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
        }

        // Initialize all charts
        var charts = {
            ngay: createChart(document.getElementById('chart-ngay').getContext('2d')),
            tuan: createChart(document.getElementById('chart-tuan').getContext('2d')),
            thang: createChart(document.getElementById('chart-thang').getContext('2d')),
            quy: createChart(document.getElementById('chart-quy').getContext('2d')),
            nam: createChart(document.getElementById('chart-nam').getContext('2d'))
        };

        // Hàm cập nhật dữ liệu
        var maTinhChose = "<?php echo $maTinhChose; ?>";
        function updateChart(time_format, ngay_chon) {
            console.log(time_format, ngay_chon);
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

                    charts[time_format].data.labels = labels;
                    charts[time_format].data.datasets[0].data = values;
                    charts[time_format].update();
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

        $('#ngay-chon').on('change', function() {
            // var selectedTimeFrame = $('#thoi-gian-chon').val();
            var selectedNgay = $('#ngay-chon').val();
            // updateChart(selectedTimeFrame, selectedNgay);
            updateChart('ngay', selectedNgay);
            updateChart('tuan', selectedNgay);
            updateChart('thang', selectedNgay);
            updateChart('quy', selectedNgay);
            updateChart('nam', selectedNgay);
        });

        $(document).ready(function() {
            updateChart('ngay', null);
            updateChart('tuan', null);
            updateChart('thang', null);
            updateChart('quy', null);
            updateChart('nam', null);
            updateTable();
        });

        // Cập nhật biểu đồ và bảng theo thời gian
        setInterval(function() {
            // var selectedTimeFrame = $('#thoi-gian-chon').val();
            var selectedNgay = $('#ngay-chon').val();
            // updateChart(selectedTimeFrame, selectedNgay);
            updateChart('ngay', selectedNgay);
            updateChart('tuan', selectedNgay);
            updateChart('thang', selectedNgay);
            updateChart('quy', selectedNgay);
            updateChart('nam', selectedNgay);
            updateTable();
        }, 3600000);
    
    </script>
</body>
</html>

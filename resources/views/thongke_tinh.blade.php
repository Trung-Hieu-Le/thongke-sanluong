@include('layouts.header_thongke')
<body>
    <header class="header">
        <a href="/">
            <img src="{{ asset('images/vtk_logo.jpg') }}" alt="Logo">
        </a>
    </header>
    <div class="container mt-3">
        <div class="row">
            <div class="col-12 breadcrumb-wrapper mt-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a class="simple-link" href="/">Tổng quát</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Khu vực</li>
                    </ol>
                </nav>
            </div>
        </div>
        <h2>Biểu đồ sản lượng của khu vực theo thời gian</h2>
        <center>
            <div class="mt-3">
                <span>Thống kê sản lượng các Tỉnh: </span>
                <span>Theo khu vực </span>
                <select class="form-control" id="khu-vuc-chon">
                    @foreach($khuVucList as $khuVuc)
                        <option value="{{ $khuVuc->ten_khu_vuc }}">{{ $khuVuc->ten_khu_vuc }}</option>
                    @endforeach
                </select>
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
        <h2>Bảng thống kê chi tiết của khu vực:</h2>
        <div class="scrollable-table">
            <table id="data-table">
                <thead>
                    <tr>
                        <th>Tỉnh</th>
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

        function updateChart(time_format, khu_vuc, ngay_chon) {
            console.log(time_format, ngay_chon);
            $.ajax({
                url: `/thongke/khuvuc/all`,
                method: 'GET',
                data: { time_format: time_format, khu_vuc: khu_vuc, ngay: ngay_chon },
                success: function(data) {
                    var labels = [];
                    var values = [];

                    data.forEach(function(item) {
                        labels.push(item.ma_tinh);
                        values.push(item.total);
                    });

                    charts[time_format].data.labels = labels;
                    charts[time_format].data.datasets[0].data = values;
                    charts[time_format].update();
                }
            });
        }

        function updateTable(khu_vuc) {
            $.ajax({
                url: '/thongke/khuvuc/tongquat',
                method: 'GET',
                data: { khu_vuc: khu_vuc },
                success: function(data) {
                    var tbody = $('#data-table tbody');
                    tbody.empty();

                    data.forEach(function(item) {
                        var row = `
                            <tr>
                                <td class="ma-tinh"><a class="simple-link" href="/thongke/tinh/${item.ma_tinh}">${item.ma_tinh}</a></td>
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

        $('#khu-vuc-chon').on('change', function() {
            var selectedKhuVuc = $(this).val();
            // var selectedTimeFrame = $('#thoi-gian-chon').val();
            var selectedNgay = $('#ngay-chon').val();
            updateChart('ngay', selectedKhuVuc, selectedNgay);
            updateChart('tuan', selectedKhuVuc, selectedNgay);
            updateChart('thang', selectedKhuVuc, selectedNgay);
            updateChart('quy', selectedKhuVuc, selectedNgay);
            updateChart('nam', selectedKhuVuc, selectedNgay);            
            updateTable(selectedKhuVuc);
        });

        $('#ngay-chon').on('change', function() {
            var selectedKhuVuc = $('#khu-vuc-chon').val();
            // var selectedTimeFrame = $('#thoi-gian-chon').val();
            var selectedNgay = $(this).val();
            updateChart('ngay', selectedKhuVuc, selectedNgay);
            updateChart('tuan', selectedKhuVuc, selectedNgay);
            updateChart('thang', selectedKhuVuc, selectedNgay);
            updateChart('quy', selectedKhuVuc, selectedNgay);
            updateChart('nam', selectedKhuVuc, selectedNgay);

          
        });

        $(document).ready(function() {
            var initialKhuVuc = $('#khu-vuc-chon').val();
            // var initialTimeFrame = 'ngay'; 
            $('#khu-vuc-chon').val(initialKhuVuc);
            // $('#thoi-gian-chon').val(initialTimeFrame);
            // updateChart(initialTimeFrame, initialKhuVuc, null);
            updateChart('ngay', initialKhuVuc, null);
            updateChart('tuan', initialKhuVuc, null);
            updateChart('thang', initialKhuVuc, null);
            updateChart('quy', initialKhuVuc, null);
            updateChart('nam', initialKhuVuc, null);
            updateTable(initialKhuVuc);
        });

        setInterval(function() {
            var selectedKhuVuc = $('#khu-vuc-chon').val();
            // var selectedTimeFrame = $('#thoi-gian-chon').val();
            var selectedNgay = $('#ngay-chon').val();
            updateChart('ngay', selectedKhuVuc, selectedNgay);
            updateChart('tuan', selectedKhuVuc, selectedNgay);
            updateChart('thang', selectedKhuVuc, selectedNgay);
            updateChart('quy', selectedKhuVuc, selectedNgay);
            updateChart('nam', selectedKhuVuc, selectedNgay);            
            updateTable(selectedKhuVuc);
        }, 3600000);

    </script>
</body>
</html>

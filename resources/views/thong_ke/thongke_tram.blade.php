@include('layouts.head_thongke')
<body>
    @include('layouts.header_thongke')
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
    
    @if (session('role') == 3 || session('role') == 2)
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
    @else
    <div class="container">
        <hr>
        <div class="alert alert-danger">
            Bạn không đủ thẩm quyền để xem thống kê.
        </div>
    </div>
    @endif
      

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

        // Hàm cập nhật dữ liệu biểu đồ
var maTinhChose = "<?php echo $maTinhChose; ?>";
function updateCharts(ngay_chon) {
    $.ajax({
        url: `/thongke/tinh/${maTinhChose}/all`,
        method: 'GET',
        data: { ma_tinh: maTinhChose, ngay: ngay_chon },
        success: function(data) {
            var labels = data.map(item => item.SanLuong_Tram);
            var chartData = {
                ngay: data.map(item => item.ngay),
                tuan: data.map(item => item.tuan),
                thang: data.map(item => item.thang),
                quy: data.map(item => item.quy),
                nam: data.map(item => item.nam)
            };

            // Cập nhật từng biểu đồ
            Object.keys(charts).forEach(time_format => {
                charts[time_format].data.labels = labels;
                charts[time_format].data.datasets[0].data = chartData[time_format];
                charts[time_format].update();
            });
        }
    });
}

// Hàm cập nhật bảng dữ liệu
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
                        <td>${formatNumber(item.tong_san_luong.nam)}</td>
                        <td>${formatNumber(item.tong_san_luong.quy_1)}</td>
                        <td>${formatNumber(item.tong_san_luong.quy_2)}</td>
                        <td>${formatNumber(item.tong_san_luong.quy_3)}</td>
                        <td>${formatNumber(item.tong_san_luong.quy_4)}</td>
                        <td>${formatNumber(item.tong_san_luong.thang_1)}</td>
                        <td>${formatNumber(item.tong_san_luong.thang_2)}</td>
                        <td>${formatNumber(item.tong_san_luong.thang_3)}</td>
                        <td>${formatNumber(item.tong_san_luong.thang_4)}</td>
                        <td>${formatNumber(item.tong_san_luong.thang_5)}</td>
                        <td>${formatNumber(item.tong_san_luong.thang_6)}</td>
                        <td>${formatNumber(item.tong_san_luong.thang_7)}</td>
                        <td>${formatNumber(item.tong_san_luong.thang_8)}</td>
                        <td>${formatNumber(item.tong_san_luong.thang_9)}</td>
                        <td>${formatNumber(item.tong_san_luong.thang_10)}</td>
                        <td>${formatNumber(item.tong_san_luong.thang_11)}</td>
                        <td>${formatNumber(item.tong_san_luong.thang_12)}</td>
                    </tr>
                `;
                tbody.append(row);
            });
        }
    });
}
function formatNumber(number) {
    return Math.round(number).toLocaleString('de-DE', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

// Event listener cho ngày chọn
$('#ngay-chon').on('change', function() {
    var selectedNgay = $('#ngay-chon').val();
    updateCharts(selectedNgay);
});

// Khởi tạo khi trang được tải
$(document).ready(function() {
    updateCharts(null);
    updateTable();
});

// Cập nhật biểu đồ và bảng theo thời gian
setInterval(function() {
    var selectedNgay = $('#ngay-chon').val();
    updateCharts(selectedNgay);
    updateTable();
}, 3600000);

    
    </script>
</body>
</html>

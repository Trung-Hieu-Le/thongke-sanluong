<!DOCTYPE html>
<html>
<head>
    @include('layouts.head_thongke')
</head>
<body>
    @include('layouts.header_thongke')
    <div class="mt-3">
        <div class="container">
            <div class="align-items-center">
                <div class="row">
                    <div class="col-lg-5 col-md-12">
                        <div class="row">
                            <div class="col-lg-6">
                                <h6>Tổng sản lượng năm</h6>
                                <p id="totalYear">0 VNĐ</p>
                            </div>
                            <div class="col-lg-6">
                                <h6>Sản lượng tháng</h6>
                                <p id="totalMonth">0 VNĐ</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7 col-md-12 row">
                        <div class="col-lg-3">
                            <h6>Khoảng thời gian</h6>
                            <select id="timeFormat" class="form-control me-2">
                                {{-- <option value="ngay">Ngày</option> --}}
                                <option value="tuan">Tuần</option>
                                <option value="thang">Tháng</option>
                                <option value="quy">Quý</option>
                                <option value="nam">Năm</option>
                            </select>
                        </div>
                        <div class="col-lg-9">
                            <h6>Thời gian</h6>
                            <select id="selectDay" class="form-control me-2">
                                <!-- Days will be populated by JavaScript -->
                            </select>
                            <select id="selectMonth" class="form-control me-2">
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $i == date('n') ? 'selected' : '' }}>Tháng {{ $i }}</option>
                                @endfor
                            </select>
                            <select id="selectQuarter" class="form-control me-2">
                                @for ($i = 1; $i <= 4; $i++)
                                    <option value="{{ $i }}" {{ $i == ceil(date('m')/3) ? 'selected' : '' }}>Quý {{ $i }}</option>
                                @endfor
                            </select>
                            <select id="selectYear" class="form-control me-2">
                                @for ($year = 2020; $year <= date('Y'); $year++)
                                    <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>Năm {{ $year }}</option>
                                @endfor
                            </select>
                            
                        </div>
                    </div>
                </div>
            </div>
            {{-- <hr> --}}
        </div>
        @if (session('role') == 3 || session('role') == 2)
        <div class="container-fluid">
            <hr>
            <div class="first-canvas row">
                @foreach ($khuVucs as $khuVuc)
                    <div class="col-lg-3 col-md-6 chart-container">
                        <h6>{{ $khuVuc }}</h6>
                        <canvas id="chart-{{ $khuVuc }}"></canvas>
                    </div>
                @endforeach
            </div>
            <hr>
            <div class="second-canvas row">
                <div class="d-flex align-items-center">
                    <h6>Biểu đồ xu thế sản lượng</h6>
                    <select id="selectUser" class="form-control me-2">
                        <option value="">Tất cả đối tác</option>
                        @foreach ($doiTacs as $doiTac)
                            <option value="{{ $doiTac->user_id }}">{{ $doiTac->user_name }}</option>
                        @endforeach
                    </select>
                    <select id="selectHopDong" class="form-control me-2">
                        <option value="">Tất cả hợp đồng</option>
                        @foreach ($hopDongs as $hopDong)
                            <option value="{{ $hopDong->HopDong_Id }}">{{ $hopDong->HopDong_SoHopDong }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-lg-7 col-md-12">
                    <canvas id="lineChart"></canvas>
                </div>
                <div class="col-lg-5 col-md-12">
                    <div class="d-flex justify-content-end align-items-center legend-container my-2" style="font-size: 12px;">
                        <div class="legend-item me-2">
                            Chú thích: &nbsp;<span style="background-color: #EE4266; display: inline-block; width: 15px; height: 15px;"></span> <=40%
                        </div>
                        <div class="legend-item me-2">
                            <span style="background-color: #FFD23F; display: inline-block; width: 15px; height: 15px;"></span> <=70%
                        </div>
                        <div class="legend-item me-2">
                            <span style="background-color: #337357; display: inline-block; width: 15px; height: 15px;"></span> <=100%
                        </div>
                        <div class="legend-item">
                            <span style="background-color: #5E1675; display: inline-block; width: 15px; height: 15px;"></span> >100%
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-12">
                        <i class="fa fa-search-plus ml-2" aria-hidden="true" onclick="viewDetail('thang')"></i>                        
                        <canvas id="barChartXuThe"></canvas>
                        <div class="table-container mt-lg-3" id="tableXuThe"></div>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="alert alert-danger container">
            Bạn không đủ thẩm quyền để xem thống kê.
        </div>
        @endif
    </div>
    {{-- TODO: updateDay khi change tháng, năm --}}
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <script>
        function updateDaySelect() {
            const month = parseInt(document.getElementById('selectMonth').value);
            const year = parseInt(document.getElementById('selectYear').value);
            const daysInMonth = new Date(year, month, 0).getDate(); // Get days in the selected month
    
            const daySelect = document.getElementById('selectDay');
            daySelect.innerHTML = ''; // Clear previous options
    
            const today = new Date();
            const currentDay = today.getDate();
            const currentMonth = today.getMonth() + 1; // getMonth() returns 0-indexed month
            const currentYear = today.getFullYear();
    
            for (let day = 1; day <= daysInMonth; day++) {
                const option = document.createElement('option');
                option.value = day;
                option.text = `Ngày ${day}`;
                if (day === currentDay && month === currentMonth && year === currentYear) {
                    option.selected = true;
                }
                daySelect.appendChild(option);
            }
        }
    
        // Function to create a bar chart
        function createBarChart(ctx) {
            if (ctx.chart) {
                ctx.chart.destroy();
            }
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
    
        // Function to create a line chart
        function createLineChart(ctx) {
            if (ctx.chart) {
                ctx.chart.destroy();
            }
            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: []
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
                            display: true,
                            labels: {
                                color: 'black'
                            }
                        }
                    }
                }
            });
        }
    
        // Initialize all charts
        const barCharts = {};
        @foreach ($khuVucs as $khuVuc)
            barCharts["{{ $khuVuc }}"] = createBarChart(document.getElementById('chart-{{ $khuVuc }}').getContext('2d'));
        @endforeach
        const lineChart = createLineChart(document.getElementById('lineChart').getContext('2d'));
        const barChartXuThe = createBarChart(document.getElementById('barChartXuThe').getContext('2d'));

    
        function updateAllCharts(time_format, ngay_chon, hop_dong, user) {
            const khuVucs = {!! json_encode($khuVucs) !!};
            // Update bar charts
            khuVucs.forEach(khu_vuc => {
                $.ajax({
                    url: `/thongke/khuvuc/all`,
                    method: 'GET',
                    data: { khu_vuc: khu_vuc, time_format: time_format, ngay: ngay_chon },
                    success: function(data) {
                        const labels = data.map(item => item.ma_tinh);
                        const chartData = data.map(item => item.totals[time_format]);
    
                        const chart = barCharts[khu_vuc];
                        chart.data.labels = labels;
                        chart.data.datasets[0].data = chartData;
                        chart.update();
                    }
                });
            });
    
            // Update line chart
            $.ajax({
                url: `/thongke/xuthe/all`,
                method: 'GET',
                data: { time_format: time_format, ngay_chon: ngay_chon, hop_dong: hop_dong, user: user },
                success: function(data) {
                    // Collect all unique time periods
                    const labelsSet = new Set();
                    data.forEach(item => {
                        item.details.forEach(detail => {
                            labelsSet.add(detail.time_period);
                        });
                    });

                    const labels = Array.from(labelsSet); // Convert to array and sort
                    const datasets = [];
                    const colors = [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                        '#9966FF', '#FF9F40', '#FF6384', '#36A2EB', '#FFCE56',
                        '#4BC0C0', '#9966FF', '#FF9F40', '#FF6384', '#36A2EB'
                    ];

                    data.forEach((item, index) => {
                        const khuVucData = labels.map(label => {
                            const detail = item.details.find(detail => detail.time_period === label);
                            return detail ? detail.total : 0;
                        });

                        datasets.push({
                            label: item.ten_khu_vuc,
                            data: khuVucData,
                            borderColor: colors[index % colors.length],
                            backgroundColor: colors[index % colors.length],
                            fill: false
                        });
                    });

                    lineChart.data.labels = labels;
                    lineChart.data.datasets = datasets;
                    lineChart.update();
                }
            });
            $.ajax({
                url: `/thongke/all`,
                method: 'GET',
                data: { time_format: time_format, ngay_chon: ngay_chon, hop_dong: hop_dong, user: user },
                success: function(data) {
                    const labels = data.map(item => item.ten_khu_vuc);
                    const dataKPI = data.map(item => item.kpi);
                    const dataTotal = data.map(item => item.total);

                    const chart = barChartXuThe;
                    chart.data.labels = labels;
                    chart.data.datasets[0].data = dataTotal;
                    chart.update();

                    const tableRows = data.map((item, index) => {
                        const percentage = item.kpi ? ((item.total / item.kpi) * 100).toFixed(1) : 'N/A';
                        return `
                            <tr>
                                <td>${percentage}%</td>
                                <td>${item.ten_khu_vuc}</td>
                                <td>${item.kpi.toFixed(1)}</td>
                                <td>${item.total.toFixed(1)}</td>
                            </tr>
                        `;
                    }).join('');

                    const totalKPI = dataKPI.reduce((acc, curr) => acc + curr, 0);
                    const totalTotal = dataTotal.reduce((acc, curr) => acc + curr, 0);
                    const totalPercentage = totalKPI ? ((totalTotal / totalKPI) * 100).toFixed(1) : 'N/A';

                    const totalRow = `
                        <tr>
                            <td><strong>${totalPercentage}%</strong></td>
                            <td><strong>Tổng cộng</strong></td>
                            <td><strong>${totalKPI.toFixed(1)}</strong></td>
                            <td><strong>${totalTotal.toFixed(1)}</strong></td>
                        </tr>
                    `;

                    document.getElementById('tableXuThe').innerHTML = `
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Tỷ lệ</th>
                                    <th>Khu vực</th>
                                    <th>KPI</th>
                                    <th>Thực hiện</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${tableRows}
                                ${totalRow}
                            </tbody>
                        </table>
                    `;
                }
            });
        }
    
        function getFormattedDate() {
            const day = document.getElementById('selectDay').value;
            const month = document.getElementById('selectMonth').value;
            const year = document.getElementById('selectYear').value;
            return `${year}-${month}-${day}`;
        }
    
        $('#timeFormat, #selectDay, #selectMonth, #selectQuarter, #selectYear, #selectHopDong, #selectUser').on('change', function() {
            const selectedTimeFormat = $('#timeFormat').val();
            const formattedDate = getFormattedDate();
            const hop_dong = $('#selectHopDong').val(); 
            const user = $('#selectUser').val();
            const quarter = $('#selectQuarter').val();
            updateAllCharts(selectedTimeFormat, formattedDate, hop_dong, user);
        });
    
        $(document).ready(function() {
            updateDaySelect(); // Update days based on the current month and year
            const initialTimeFormat = $('#timeFormat').val();
            const formattedDate = getFormattedDate();
            const hop_dong = $('#selectHopDong').val(); 
            const user = $('#selectUser').val();
            const quarter = $('#selectQuarter').val();
            updateAllCharts(initialTimeFormat, formattedDate, hop_dong, user);
        });
    
        setInterval(function() {
            const selectedTimeFormat = $('#timeFormat').val();
            const formattedDate = getFormattedDate();
            const hop_dong = $('#selectHopDong').val();
            const user = $('#selectUser').val();
            const quarter = $('#selectQuarter').val();
            updateAllCharts(selectedTimeFormat, formattedDate, hop_dong, user);
        }, 3600000);
    </script>
    
</body>
</html>

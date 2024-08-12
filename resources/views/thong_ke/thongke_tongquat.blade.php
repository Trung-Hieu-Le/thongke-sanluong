<!DOCTYPE html>
<html>
<head>
    @include('layouts.head_thongke')
</head>
<body>
    @include('layouts.header_thongke')
    <div class="mt-3">
        <div class="container-fluid">
            <div class="align-items-center">
                <div class="row">
                    <div class="col-xl-6 col-md-12">
                        <div class="row" style="height: fit-content">
                            <div class="col-6 d-flex align-items-center">
                                <div class="me-2">
                                    <h6 class="text-secondary mb-0" style="font-size: 12px;">TỔNG SẢN LƯỢNG NĂM</h6>
                                    <span id="totalYearValue" class="fw-bold fs-2 fs-md-6">0</span><br>
                                    <span id="totalKpiYear" class="text-secondary ms-2" style="font-size: 12px;">Tổng KPI: 0 tỉ đồng</span>
                                </div>
                                <div class="d-flex flex-column align-items-center">
                                    <span id="totalYearUnit" class="text-secondary" style="font-size: 12px;">VNĐ</span>
                                    <span id="yearKPI" class="bg-light text-success fw-bold p-1 m-0 rounded-pill" style="font-size: 12px;">100%</span>
                                </div>
                            </div>
                            <div class="col-6 d-flex align-items-center">
                                <div class="me-2">
                                    <h6 class="text-secondary mb-0" style="font-size: 12px;">SẢN LƯỢNG THÁNG</h6>
                                    <span id="totalMonthValue" class="fw-bold fs-2 fs-md-6">0</span><br>
                                    <span id="totalKpiMonth" class="text-secondary ms-2" style="font-size: 12px;">Tổng KPI: 0 tỉ đồng</span>
                                </div>
                                <div class="d-flex flex-column align-items-center">
                                    <span id="totalMonthUnit" class="text-secondary" style="font-size: 12px;">VNĐ</span>
                                    <p id="monthKPI" class="bg-light text-success fw-bold p-1 m-0 rounded-pill" style="font-size: 12px;">100%</p>
                                </div>
                            </div>
                        </div>                        
                    </div>
                    <div class="col-xl-6 col-md-12 row">
                        <div class="col-3">
                            <h6 class="text-secondary mb-xl-3" style="font-size: 12px;">LOẠI BIỂU ĐỒ</h6>
                            <select id="timeFormat" class="form-control form-select form-select-sm me-2">
                                {{-- <option value="ngay">Ngày</option> --}}
                                <option value="tuan">Tuần</option>
                                <option value="thang" selected>Tháng</option>
                                <option value="quy">Quý</option>
                                <option value="nam">Năm</option>
                            </select>
                        </div>
                        <div class="col-9">
                            <h6 class="text-secondary mb-xl-3" style="font-size: 12px;">THỜI GIAN</h6>
                            <select id="selectDay" class="form-control form-select form-select-sm me-1">
                                <!-- Days will be populated by JavaScript -->
                            </select>
                            <select id="selectMonth" class="form-control form-select form-select-sm me-1">
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $i == date('n') ? 'selected' : '' }}>Tháng {{ $i }}</option>
                                @endfor
                            </select>
                            <select id="selectQuarter" class="form-control form-select form-select-sm me-1">
                                @for ($i = 1; $i <= 4; $i++)
                                    <option value="{{ $i }}" {{ $i == ceil(date('m')/3) ? 'selected' : '' }}>Quý {{ $i }}</option>
                                @endfor
                            </select>
                            <select id="selectYear" class="form-control form-select form-select-sm me-1">
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
            {{-- <hr> --}}
            <div class="d-flex align-items-center mt-2">
                <h6>Sản lượng khu vực:</h6>
                <div class="d-flex">
                    <div>
                        <select id="selectUser" class="form-control form-select form-select-sm ms-2 me-2 mb-2">
                            <option value="">Tất cả đối tác</option>
                            @foreach ($doiTacs as $doiTac)
                            <option value="{{ $doiTac->user_id }}">{{ $doiTac->user_name }}</option>
                            @endforeach
                        </select>
                        <select id="selectHopDong" class="form-control form-select form-select-sm me-2 mb-2">
                            <option value="">Tất cả hợp đồng</option>
                            @foreach ($hopDongs as $hopDong)
                            <option value="{{ $hopDong->HopDong_Id }}">{{ $hopDong->HopDong_SoHopDong }}</option>
                            @endforeach
                        </select>
                        <select id="selectLinhVuc" class="form-control form-select form-select-sm me-2 mb-2">
                            <option value="">Tất cả lĩnh vực</option>
                            {{-- <option value="EC">EC</option> --}}
                            @foreach ($linhVucs as $linhVuc)
                            <option value="{{ $linhVuc->noi_dung }}">{{ $linhVuc->noi_dung }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="startDate" class="form-label ms-2 text-secondary"style="font-size: 15px;">Ngày bắt đầu: &nbsp;</label>
                        <input type="date" id="startDate" class="form-control form-control-sm mb-2">
                        <label for="endDate" class="form-label ms-2 text-secondary"style="font-size: 15px;">Ngày kết thúc: &nbsp;</label>
                        <input type="date" id="endDate" class="form-control form-control-sm mb-2">
                    </div>
                </div>
            </div>
                
            <div class="container-fluid first-canvas row mb-2 px-xl-5">
                @foreach ($khuVucs as $khuVuc)
                    <div class="col-lg-3 col-md-6">
                        <div class="">
                            <div class="shadow p-2 bg-body rounded mb-2">
                                <h6 class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <span>{{ $khuVuc }}: <span id="total-{{ $khuVuc }}" class="fw-bold"></span>
                                        <span class="text-secondary ms-2" style="font-size: 12px;">VNĐ</span></span>
                                        <div class="text-secondary text-start mt-1" style="font-size: 12px;">
                                            Tổng KPI: <span id="totalKpi-{{ $khuVuc }}"></span>
                                        </div>
                                    </div>
                                    <span id="kpi-{{ $khuVuc }}" class="bg-light text-success fw-bold p-1 m-0 rounded-pill" style="font-size: 12px;">100%</span>
                                </h6>
                                <div style="min-width: 150px;">
                                    <canvas id="chart-{{ $khuVuc }}"></canvas>
                                </div>
                                <div id="timeFormatText-{{ $khuVuc }}" class="text-center">Thống kê theo tháng</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            {{-- <hr> --}}
            <div class="second-canvas">
                <div class="d-flex align-items-center">
                    <h6>Biểu đồ xu thế sản lượng:</h6>
                    
                </div>
                <div class="container-fluid row px-xl-5">
                    <div class="col-lg-7 col-md-12">
                        <div class="shadow p-2 my-2 bg-body rounded" style="min-height: 280px;">
                            <canvas id="lineChart"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-12">
                        <div class="shadow p-2 my-2 bg-body rounded" style="min-height: 280px;">
                            <div>
                                <ul class="nav nav-tabs" id="chartTableTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="chart-tab" data-bs-toggle="tab" data-bs-target="#chart-wrapper" type="button" role="tab" aria-controls="chart-wrapper" aria-selected="true">Biểu đồ</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="table-tab" data-bs-toggle="tab" data-bs-target="#tableXuThe" type="button" role="tab" aria-controls="tableXuThe" aria-selected="false">Bảng</button>
                                    </li>
                                    <div class="d-flex justify-content-end align-items-center legend-container my-2" style="font-size: 12px;">
                                        <div class="legend-item me-2">
                                            &emsp; Chú thích: &nbsp;<span style="background-color: #EE4266; display: inline-block; width: 15px; height: 15px;"></span> <=40%
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
                                </ul>
                                <div class="tab-content mt-2" id="chartTableContent">
                                    <div class="tab-pane fade show active" id="chart-wrapper" role="tabpanel" aria-labelledby="chart-tab">
                                        <div style="min-height: 220px;">
                                            <canvas id="barChartXuThe"></canvas>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="tableXuThe" role="tabpanel" aria-labelledby="table-tab">
                                        <div class="table-container mt-lg-3">
                                            <!-- Bảng nội dung sẽ được thêm vào đây -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
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
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <script>
        function populateDays() {
            const month = parseInt(selectMonth.value);
            const year = parseInt(selectYear.value);
            const daysInMonth = new Date(year, month, 0).getDate(); // Get days in the selected month
            const currentSelectedDay = parseInt(selectDay.value) || 1; // Preserve currently selected day
            selectDay.innerHTML = ''; // Clear previous options

            for (let day = 1; day <= daysInMonth; day++) {
                const option = document.createElement('option');
                option.value = day;
                option.text = `Ngày ${day}`;
                if (day === currentSelectedDay || (day === daysInMonth && currentSelectedDay > daysInMonth)) {
                    option.selected = true;
                }
                selectDay.appendChild(option);
            }
        }
        function updateDateRange() {
            const format = timeFormat.value;
            const selectedDay = parseInt(selectDay.value, 10);
            const selectedMonth = parseInt(selectMonth.value, 10);
            const selectedQuarter = parseInt(selectQuarter.value, 10);
            const selectedYear = parseInt(selectYear.value, 10);
            let start, end;
            if (format === 'tuan') {
                if (selectedDay >= 1 && selectedDay <= 7) {
                    start = new Date(selectedYear, selectedMonth - 1, 1);
                    end = new Date(selectedYear, selectedMonth - 1, 7);
                } else if (selectedDay >= 8 && selectedDay <= 14) {
                    start = new Date(selectedYear, selectedMonth - 1, 8);
                    end = new Date(selectedYear, selectedMonth - 1, 14);
                } else if (selectedDay >= 15 && selectedDay <= 21) {
                    start = new Date(selectedYear, selectedMonth - 1, 15);
                    end = new Date(selectedYear, selectedMonth - 1, 21);
                } else if (selectedDay >= 22) {
                    start = new Date(selectedYear, selectedMonth - 1, 22);
                    end = new Date(selectedYear, selectedMonth, 0); // Last day of the month
                }
            } else if (format === 'thang') {
                start = new Date(selectedYear, selectedMonth - 1, 1);
                end = new Date(selectedYear, selectedMonth, 0);
            } else if (format === 'quy') {
                start = new Date(selectedYear, (selectedQuarter - 1) * 3, 1);
                end = new Date(selectedYear, selectedQuarter * 3, 0);
            } else if (format === 'nam') {
                start = new Date(selectedYear, 0, 1);
                end = new Date(selectedYear, 11, 31);
            } else if (format === 'ngay') {
                start = new Date(selectedYear, selectedMonth - 1, selectedDay);
                end = new Date(selectedYear, selectedMonth - 1, selectedDay);
            }
            if (start > end) {
            //TODO: sửa phần alert này
                alert('Ngày bắt đầu phải nhỏ hơn hoặc bằng ngày kết thúc');
                return;
            }
            startDate.min = formatDate(start);
            startDate.max = formatDate(end);
            endDate.min = formatDate(start);
            endDate.max = formatDate(end);
            startDate.value = formatDate(start);
            endDate.value = formatDate(end);
        }
        function formatDate(date) {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${year}-${month}-${day}`;
        }
        document.addEventListener('DOMContentLoaded', function () {
            const timeFormat = document.getElementById('timeFormat');
            const selectDay = document.getElementById('selectDay');
            const selectMonth = document.getElementById('selectMonth');
            const selectQuarter = document.getElementById('selectQuarter');
            const selectYear = document.getElementById('selectYear');
            const startDate = document.getElementById('startDate');
            const endDate = document.getElementById('endDate');
            const khuVucs = {!! json_encode($khuVucs) !!};

            function updateVisibility() {
                const format = timeFormat.value;

                selectDay.style.display = (format === 'tuan') ? 'inline-block' : 'none';
                selectMonth.style.display = (format === 'thang' || format === 'tuan') ? 'inline-block' : 'none';
                selectQuarter.style.display = (format === 'quy') ? 'inline-block' : 'none';
                selectYear.style.display = (format !== 'ngay') ? 'inline-block' : 'none';
                
                if (format === 'tuan' || format === 'thang') {
                    populateDays();
                }
                updateStatisticsText(format);
            }
            function updateStatisticsText(format) {
                let text;
                switch (format) {
                    case 'tuan':
                        text = 'Thống kê theo tuần';
                        break;
                    case 'thang':
                        text = 'Thống kê theo tháng';
                        break;
                    case 'quy':
                        text = 'Thống kê theo quý';
                        break;
                    case 'nam':
                        text = 'Thống kê theo năm';
                        break;
                    default:
                        text = 'Thống kê theo năm';
                }

                khuVucs.forEach(khuVuc => {
                    document.getElementById(`timeFormatText-${khuVuc}`).textContent = text;
                });
            }

            timeFormat.addEventListener('change', updateVisibility);
            timeFormat.addEventListener('change', updateDateRange);
            selectDay.addEventListener('change', updateDateRange);
            selectMonth.addEventListener('change', updateDateRange);
            selectQuarter.addEventListener('change', updateDateRange);
            selectYear.addEventListener('change', updateDateRange);

            updateVisibility();
            updateDateRange();
        });
    </script>
    <script>
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
                    maintainAspectRatio: false,
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value, index, values) {
                                    return (value / 1e9).toFixed(2);
                                }
                            }
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
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                padding: 5 // Khoảng cách từ lề bên trái đến các nhãn trục x
                            },
                            offset: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                color: 'black'
                            }
                        },
                        datalabels: {
                            align: 'end',
                            anchor: 'end',
                            color: 'black',
                            formatter: function(value, context) {
                                return value;
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels, legendMargin]
            });
        }
        const ctx = document.getElementById('barChartXuThe').getContext('2d');
        const legendMargin = {
                id: 'legendMargin',
                beforeInit(chart, legend, options) {
                    const fitValue=chart.legend.fit;
                    chart.legend.fit = function fit() {
                        fitValue.bind(chart.legend)();
                        return this.height +=30;
                    }
                }
            }
        const barChartXuThe = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'KPI',
                        data: [],
                        backgroundColor: '#1B5EBE'
                    },
                    {
                        label: 'Thực hiện',
                        data: [],
                        backgroundColor: [] // This will be updated
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (context.dataset.label === 'Thực hiện') {
                                    const index = context.dataIndex;
                                    const percentage = context.chart.data.datasets[0].data[index] ? ((context.raw / context.chart.data.datasets[0].data[index]) * 100).toFixed(2) : 'N/A';
                                    return `${context.dataset.label}: ${context.raw} (${percentage}%)`;
                                } else {
                                    return `${context.dataset.label}: ${context.raw}`;
                                }
                            }
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        formatter: (value, context) => {
                            if (context.dataset.label === 'Thực hiện') {
                                const index = context.dataIndex;
                                const percentage = context.chart.data.datasets[0].data[index] ? ((value / context.chart.data.datasets[0].data[index]) * 100).toFixed(2) : 'N/A';
                                return `${value} \n${percentage}%`;
                            } else {
                                return value;
                            }
                        },
                        color: '#000',
                        font: {
                            size: 10
                        },
                    }
                }
            },
            plugins: [ChartDataLabels, legendMargin]
        });
    </script>
    <script>
        function updateAllCharts(time_format, ngay_chon, hop_dong, user, linh_vuc, startDate, endDate) {
            const khuVucs = {!! json_encode($khuVucs) !!};
            // Update bar charts
            khuVucs.forEach(khu_vuc => {
                $.ajax({
                    url: `/thongke/khuvuc/all`,
                    method: 'GET',
                    data: { khu_vuc: khu_vuc, time_format: time_format, ngay_chon: ngay_chon, start_date: startDate, end_date: endDate, linh_vuc: linh_vuc },
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
                data: { time_format: time_format, ngay_chon: ngay_chon, hop_dong: hop_dong, user: user, linh_vuc: linh_vuc, start_date: startDate, end_date: endDate },
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
                data: { time_format: time_format, ngay_chon: ngay_chon, hop_dong: hop_dong, user: user, linh_vuc: linh_vuc, start_date: startDate, end_date: endDate },
                success: function(data) {
                    const labels = data.map(item => item.ten_khu_vuc);
                    const dataKPI = data.map(item => item.kpi);
                    const dataTotal = data.map(item => item.total);

                    const totalKPI = dataKPI.reduce((acc, curr) => acc + curr, 0);
                    const totalTotal = dataTotal.reduce((acc, curr) => acc + curr, 0);
                    const totalPercentage = totalKPI ? ((totalTotal / totalKPI) * 100).toFixed(2) : 'N/A';
                    labels.push('Tổng cộng');
                    dataKPI.push(parseFloat(totalKPI.toFixed(2)));  // Ensure number type
                    dataTotal.push(parseFloat(totalTotal.toFixed(2)));  // Ensure number type   
                    console.log(dataTotal, dataKPI);

                    const chart = barChartXuThe;
                    chart.data.labels = labels;
                    
                    // Ensure datasets are initialized
                    if (chart.data.datasets.length < 2) {
                        chart.data.datasets = [
                            {
                                label: 'KPI',
                                data: dataKPI,
                                backgroundColor: '#1B5EBE'
                            },
                            {
                                label: 'Thực hiện',
                                data: dataTotal,
                                backgroundColor: dataTotal.map((total, index) => {
                                    let percentage = dataKPI[index] ? (total / dataKPI[index] * 100).toFixed(2) : 'N/A';                                    
                                    if (percentage > 100) return '#5E1675'; // Purple
                                    if (percentage > 70) return '#337357'; // Green
                                    if (percentage > 40) return '#FFD23F'; // Yellow
                                    return '#EE4266'; // Red
                                })
                            }
                        ];
                    } else {
                        chart.data.datasets[0].data = dataKPI;
                        chart.data.datasets[1].data = dataTotal;
                        
                        // Update background colors based on percentage
                        chart.data.datasets[1].backgroundColor = dataTotal.map((total, index) => {
                            let percentage = dataKPI[index] ? (total / dataKPI[index] * 100).toFixed(2) : 'N/A';
                            if (percentage > 100) return '#5E1675'; // Purple
                            if (percentage > 70) return '#337357'; // Green
                            if (percentage > 40) return '#FFD23F'; // Yellow
                            return '#EE4266'; // Red
                        });
                    }

                    chart.update();

                    const tableRows = data.map((item, index) => {
                        let percentage = item.kpi ? ((item.total / item.kpi) * 100).toFixed(2) : 'N/A';
                        return `
                            <tr>
                                <td>${percentage}%</td>
                                <td>${item.ten_khu_vuc}</td>
                                <td>${item.kpi.toFixed(2)}</td>
                                <td>${item.total.toFixed(2)}</td>
                            </tr>
                        `;
                    }).join('');


                    const totalRow = `
                        <tr>
                            <td><strong>${totalPercentage}%</strong></td>
                            <td><strong>Tổng cộng</strong></td>
                            <td><strong>${totalKPI.toFixed(2)}</strong></td>
                            <td><strong>${totalTotal.toFixed(2)}</strong></td>
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
        function updateTotals(time_format, start_date, end_date) {
            const ngay_chon = getFormattedDate();
            $.ajax({
                url: `/thongke/tong-thang-nam`,
                method: 'GET',
                data: { ngay_chon: ngay_chon, time_format: time_format, start_date: start_date, end_date:end_date },
                success: function(data) {
                    const totalYear = data.totalYear;
                    const totalMonth = data.totalMonth;
                    const kpiYear = data.kpiYear;
                    const kpiMonth = data.kpiMonth;
                    const totalKpiYear = data.totalKpiYear;
                    const totalKpiMonth = data.totalKpiMonth;   

                    document.getElementById('totalYearValue').textContent = number_format(totalYear, 0, ',', '.');
                    document.getElementById('totalMonthValue').textContent = number_format(totalMonth, 0, ',', '.');
                    document.getElementById('yearKPI').textContent = kpiYear + "%";
                    document.getElementById('monthKPI').textContent = kpiMonth + "%";
                    document.getElementById('totalKpiYear').textContent = "Tổng KPI: " + number_format(totalKpiYear, 2, ',', '.') + " tỉ đồng";
                    document.getElementById('totalKpiMonth').textContent = "Tổng KPI: " + number_format(totalKpiMonth, 2, ',', '.') + " tỉ đồng";

                    data.details.forEach(detail => {
                        document.getElementById(`total-${detail.khuVuc}`).textContent = number_format(detail.total, 0, ',', '.');
                        document.getElementById(`kpi-${detail.khuVuc}`).textContent = detail.kpi + "%";
                        document.getElementById(`totalKpi-${detail.khuVuc}`).textContent = number_format(detail.totalKpi, 2, ',', '.') + " tỉ đồng";
                    });
                }
            });
        }
    </script>
    <script>
    // Define the number_format function
    function number_format(number, decimals, dec_point, thousands_sep) {
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function (n, prec) {
                var k = Math.pow(10, prec);
                return '' + (Math.round(n * k) / k).toFixed(prec);
            };
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }

        // Initialize all charts
        const barCharts = {};
        @foreach ($khuVucs as $khuVuc)
            barCharts["{{ $khuVuc }}"] = createBarChart(document.getElementById('chart-{{ $khuVuc }}').getContext('2d'));
        @endforeach
        const lineChart = createLineChart(document.getElementById('lineChart').getContext('2d'));
      
        function getFormattedDate() {
            const day = document.getElementById('selectDay').value;
            const month = document.getElementById('selectMonth').value;
            const year = document.getElementById('selectYear').value;
            return `${year}-${month}-${day}`;
        }

        function getStartEndDate() {
            const start_date = document.getElementById('startDate').value;
            const end_date = document.getElementById('endDate').value;
            return `start_date=${start_date}&end_date=${end_date}`;
        }

        $('#timeFormat, #selectDay, #selectMonth, #selectQuarter, #selectYear, #selectHopDong, #selectUser, #selectLinhVuc').on('change', function() {
            const selectedTimeFormat = $('#timeFormat').val();
            const formattedDate = getFormattedDate();
            const hop_dong = $('#selectHopDong').val(); 
            const user = $('#selectUser').val();
            const linh_vuc = $('#selectLinhVuc').val();
            const quarter = $('#selectQuarter').val();
            updateDateRange();
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();
            populateDays();
            updateTotals(selectedTimeFormat, startDate, endDate);
            updateAllCharts(selectedTimeFormat, formattedDate, hop_dong, user, linh_vuc, startDate, endDate);
        });
        $('#startDate, #endDate').on('change', function() {
            const selectedTimeFormat = $('#timeFormat').val();
            const formattedDate = getFormattedDate();
            const hop_dong = $('#selectHopDong').val(); 
            const user = $('#selectUser').val();
            const linh_vuc = $('#selectLinhVuc').val();
            const quarter = $('#selectQuarter').val();
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();
            populateDays();
            updateTotals(selectedTimeFormat, startDate, endDate);
            updateAllCharts(selectedTimeFormat, formattedDate, hop_dong, user, linh_vuc, startDate, endDate);
        });

    
        $(document).ready(function() {
            populateDays(); // Update days based on the current month and year
            const initialTimeFormat = $('#timeFormat').val();
            const formattedDate = getFormattedDate();
            const hop_dong = $('#selectHopDong').val(); 
            const user = $('#selectUser').val();
            const linh_vuc = $('#selectLinhVuc').val();
            const quarter = $('#selectQuarter').val();
            updateDateRange();
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();
            updateTotals(initialTimeFormat, startDate, endDate);
            updateAllCharts(initialTimeFormat, formattedDate, hop_dong, user, linh_vuc, startDate, endDate);
        });
    
        setInterval(function() {
            const selectedTimeFormat = $('#timeFormat').val();
            const formattedDate = getFormattedDate();
            const hop_dong = $('#selectHopDong').val();
            const user = $('#selectUser').val();
            const linh_vuc = $('#selectLinhVuc').val();
            const quarter = $('#selectQuarter').val();
            updateDateRange();
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();
            updateTotals(selectedTimeFormat, startDate, endDate);
            updateAllCharts(selectedTimeFormat, formattedDate, hop_dong, user, linh_vuc, startDate, endDate);
        }, 3600000);
    </script>
    
    <style>
        .nav-tabs .nav-link {
            cursor: pointer;
        }

        .table-container {
            display: none;
        }

        .tab-content .tab-pane {
            display: none;
        }

        .tab-content .tab-pane.active {
            display: block;
        }
    </style>
</body>
</html>

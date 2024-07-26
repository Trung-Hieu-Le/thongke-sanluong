<!DOCTYPE html>
<html>
<head>
    @include('layouts.head_thongke')
</head>
<body>
    @include('layouts.header_thongke')
    <div class="mt-4">
        <div class="container-fluid">
            <div class="align-items-center">
                <div class="row">
                    <div class="col-lg-6 col-md-12">
                        <div class="row">
                            <div class="col-6 d-flex align-items-center">
                                <div class="me-2">
                                    <h6 class="text-secondary mb-1" style="font-size: 12px;">TỔNG SẢN LƯỢNG NĂM</h6>
                                    <p id="totalYearValue" class="fw-bold fs-2 fs-md-6">0</p>
                                </div>
                                <div class="d-flex flex-column align-items-center">
                                    <span id="totalYearUnit" class="text-secondary" style="font-size: 12px;">VNĐ</span>
                                    <p id="yearKPI" class="bg-light text-success fw-bold p-1 m-0 rounded-pill" style="font-size: 12px;">100%</p>
                                </div>
                            </div>
                            <div class="col-6 d-flex align-items-center">
                                <div class="me-2">
                                    <h6 class="text-secondary" style="font-size: 12px;">SẢN LƯỢNG THÁNG</h6>
                                    <p id="totalMonthValue" class="fw-bold fs-2 fs-md-6">0</p>
                                </div>
                                <div class="d-flex flex-column align-items-center">
                                    <span id="totalMonthUnit" class="text-secondary" style="font-size: 12px;">VNĐ</span>
                                    <p id="monthKPI" class="bg-light text-success fw-bold p-1 m-0 rounded-pill" style="font-size: 12px;">100%</p>
                                </div>
                            </div>
                        </div>                        
                    </div>
                    <div class="col-lg-6 col-md-12 row">
                        <div class="col-3">
                            <h6 class="text-secondary" style="font-size: 12px;">Khoảng thời gian</h6>
                            <select id="timeFormat" class="form-control form-select me-2">
                                {{-- <option value="ngay">Ngày</option> --}}
                                <option value="tuan">Tuần</option>
                                <option value="thang">Tháng</option>
                                <option value="quy">Quý</option>
                                <option value="nam">Năm</option>
                            </select>
                        </div>
                        <div class="col-9">
                            <h6 class="text-secondary" style="font-size: 12px;">Thời gian</h6>
                            <select id="selectDay" class="form-control form-select me-1">
                                <!-- Days will be populated by JavaScript -->
                            </select>
                            <select id="selectMonth" class="form-control form-select me-1">
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ $i == date('n') ? 'selected' : '' }}>Tháng {{ $i }}</option>
                                @endfor
                            </select>
                            <select id="selectQuarter" class="form-control form-select me-1" hidden>
                                @for ($i = 1; $i <= 4; $i++)
                                    <option value="{{ $i }}" {{ $i == ceil(date('m')/3) ? 'selected' : '' }}>Quý {{ $i }}</option>
                                @endfor
                            </select>
                            <select id="selectYear" class="form-control form-select me-1">
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
            <h6>Sản lượng khu vực:</h6>
            <div class="container first-canvas row mb-2">
                @foreach ($khuVucs as $khuVuc)
                    <div class="col-lg-3 col-md-6">
                        <div class="shadow p-2 bg-body rounded mb-2">
                            <h6>{{ $khuVuc }}</h6>
                            <canvas id="chart-{{ $khuVuc }}"></canvas>
                        </div>
                    </div>
                @endforeach
            </div>
            {{-- <hr> --}}
            <div class="second-canvas">
                <div class="d-flex align-items-center">
                    <h6>Biểu đồ xu thế sản lượng:</h6>
                    <select id="selectUser" class="form-control form-select ms-2 me-2">
                        <option value="">Tất cả đối tác</option>
                        @foreach ($doiTacs as $doiTac)
                            <option value="{{ $doiTac->user_id }}">{{ $doiTac->user_name }}</option>
                        @endforeach
                    </select>
                    <select id="selectHopDong" class="form-control form-select me-2">
                        <option value="">Tất cả hợp đồng</option>
                        @foreach ($hopDongs as $hopDong)
                            <option value="{{ $hopDong->HopDong_Id }}">{{ $hopDong->HopDong_SoHopDong }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="container row">
                    <div class="col-lg-7 col-md-12">
                        <div class="shadow p-2 my-2 bg-body rounded">
                            <canvas id="lineChart"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-12">
                        <div class="shadow p-2 my-2 bg-body rounded">
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
                            <div>
                                {{-- TODO: View detail --}}
                                {{-- <i class="fa fa-search-plus ml-2" aria-hidden="true" onclick="viewDetail('thang')"></i>                         --}}
                                <div id="chart-wrapper">
                                    <canvas id="barChartXuThe"></canvas>
                                </div>
                                <div class="table-container mt-lg-3" id="tableXuThe"></div>
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
    {{-- TODO: updateDay khi change tháng, năm --}}
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
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
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value, index, values) {
                                    return (value / 1e9).toFixed(1);
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


        // Initialize all charts
        const barCharts = {};
        @foreach ($khuVucs as $khuVuc)
            barCharts["{{ $khuVuc }}"] = createBarChart(document.getElementById('chart-{{ $khuVuc }}').getContext('2d'));
        @endforeach
        const lineChart = createLineChart(document.getElementById('lineChart').getContext('2d'));
      

    
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
                                    const percentage = dataKPI[index] ? (total / dataKPI[index] * 100).toFixed(2) : 'N/A';
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
                            const percentage = dataKPI[index] ? (total / dataKPI[index] * 100).toFixed(2) : 'N/A';
                            if (percentage > 100) return '#5E1675'; // Purple
                            if (percentage > 70) return '#337357'; // Green
                            if (percentage > 40) return '#FFD23F'; // Yellow
                            return '#EE4266'; // Red
                        });
                    }

                    chart.update();

                    const tableRows = data.map((item, index) => {
                        const percentage = item.kpi ? ((item.total / item.kpi) * 100).toFixed(2) : 'N/A';
                        return `
                            <tr>
                                <td>${percentage}%</td>
                                <td>${item.ten_khu_vuc}</td>
                                <td>${item.kpi.toFixed(2)}</td>
                                <td>${item.total.toFixed(2)}</td>
                            </tr>
                        `;
                    }).join('');

                    const totalKPI = dataKPI.reduce((acc, curr) => acc + curr, 0);
                    const totalTotal = dataTotal.reduce((acc, curr) => acc + curr, 0);
                    const totalPercentage = totalKPI ? ((totalTotal / totalKPI) * 100).toFixed(2) : 'N/A';

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
    
        function getFormattedDate() {
            const day = document.getElementById('selectDay').value;
            const month = document.getElementById('selectMonth').value;
            const year = document.getElementById('selectYear').value;
            return `${year}-${month}-${day}`;
        }

        function updateTotals() {
            const ngay_chon = getFormattedDate();
            $.ajax({
                url: `/thongke/tong-thang-nam`,
                method: 'GET',
                data: { ngay_chon: ngay_chon },
                success: function(data) {
                    const totalYear = data.totalYear;
                    const totalMonth = data.totalMonth;
                    const kpiYear = data.kpiYear;
                    const kpiMonth = data.kpiMonth;

                    document.getElementById('totalYearValue').textContent = number_format(totalYear, 0, ',', '.');
                    document.getElementById('totalMonthValue').textContent = number_format(totalMonth, 0, ',', '.');
                    document.getElementById('yearKPI').textContent = kpiYear + "%";
                    document.getElementById('monthKPI').textContent = kpiMonth + "%";
                }
            });
        }
    
        $('#timeFormat, #selectDay, #selectMonth, #selectQuarter, #selectYear, #selectHopDong, #selectUser').on('change', function() {
            updateTotals();
            const selectedTimeFormat = $('#timeFormat').val();
            const formattedDate = getFormattedDate();
            const hop_dong = $('#selectHopDong').val(); 
            const user = $('#selectUser').val();
            const quarter = $('#selectQuarter').val();
            updateAllCharts(selectedTimeFormat, formattedDate, hop_dong, user);
        });
    
        $(document).ready(function() {
            updateDaySelect(); // Update days based on the current month and year
            updateTotals();
            const initialTimeFormat = $('#timeFormat').val();
            const formattedDate = getFormattedDate();
            const hop_dong = $('#selectHopDong').val(); 
            const user = $('#selectUser').val();
            const quarter = $('#selectQuarter').val();
            updateAllCharts(initialTimeFormat, formattedDate, hop_dong, user);
        });
    
        setInterval(function() {
            updateTotals();
            const selectedTimeFormat = $('#timeFormat').val();
            const formattedDate = getFormattedDate();
            const hop_dong = $('#selectHopDong').val();
            const user = $('#selectUser').val();
            const quarter = $('#selectQuarter').val();
            updateAllCharts(selectedTimeFormat, formattedDate, hop_dong, user);
        }, 3600000);
    </script>
    {{-- <style>
        #chart-wrapper {
          display: inline-block;
          position: relative;
          width: 100%;
        }
      </style> --}}
</body>
</html>

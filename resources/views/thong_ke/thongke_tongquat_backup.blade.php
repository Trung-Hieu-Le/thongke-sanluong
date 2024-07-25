@include('layouts.head_thongke')
<body>
    @include('layouts.header_thongke')
    <div class=" mt-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <h2 class="text-center mb-0">Thống kê tổng quát &nbsp;</h2>
                    <select id="selectMonth" class="form-control me-2">
                        @for ($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ $i == date('n') ? 'selected' : '' }}>Tháng {{ $i }}</option>
                        @endfor
                    </select>
                    <select id="selectYear" class="form-control me-2">
                        @for ($year = 2020; $year <= date('Y'); $year++)
                            <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>Năm {{ $year }}</option>
                        @endfor
                    </select>
                    <select id="selectHopDong" class="form-control me-2">
                        <option value="">Tất cả hợp đồng</option>
                        @foreach ($hopDongs as $hopDong)
                            <option value="{{ $hopDong->HopDong_Id }}">{{ $hopDong->HopDong_SoHopDong }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- <a href="/thongke/khuvuc/" class="simple-link h4 mb-0">Thống kê theo khu vực</a> --}}
            </div>
            {{-- <div class="dropdown">
                <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                  Xem chi tiết
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                  <li><a class="dropdown-item" href="/thongke/khuvuc/">Thống kê khu vực</a></li>
                  <li><a class="dropdown-item" href="/thongke/filter/">Lọc theo ngày</a></li>
                </ul>
            </div> --}}
            <hr>
        </div>

        @if (session('role') == 3 || session('role') == 2 ) 
        <div class="container">
            <div class="d-flex justify-content-end align-items-center legend-container my-2" style="font-size: 15px;">
                <div class="legend-item me-2">
                    Chú thích: &nbsp;<span style="background-color: #EE4266; display: inline-block; width: 18px; height: 18px;"></span> <=40%
                </div>
                <div class="legend-item me-2">
                    <span style="background-color: #FFD23F; display: inline-block; width: 18px; height: 18px;"></span> <=70%
                </div>
                <div class="legend-item me-2">
                    <span style="background-color: #337357; display: inline-block; width: 18px; height: 18px;"></span> <=100%
                </div>
                <div class="legend-item">
                    <span style="background-color: #5E1675; display: inline-block; width: 18px; height: 18px;"></span> >100%
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-12">
                    <h5 class="d-flex justify-content-between align-items-center">Thống kê theo Tháng <br>(đơn vị tính: tỉ đồng)
                        <i class="fa fa-search-plus ml-2" aria-hidden="true" onclick="viewDetail('thang')"></i>
                    </h5>
                    <canvas id="barChartThang"></canvas>
                    <div class="table-container mt-lg-3" id="thangTable"></div>
                </div>
                <div class="col-lg-4 col-md-12">
                    <h5 class="d-flex justify-content-between align-items-center">Thống kê theo Quý <br>(đơn vị tính: tỉ đồng)
                        <i class="fa fa-search-plus ml-2" aria-hidden="true" onclick="viewDetail('quy')"></i>
                    </h5>
                    <canvas id="barChartQuy"></canvas>
                    <div class="table-container mt-lg-3" id="quyTable"></div>
                </div>
                <div class="col-lg-4 col-md-12">
                    <h5 class="d-flex justify-content-between align-items-center">Thống kê theo Năm <br>(đơn vị tính: tỉ đồng)
                        <i class="fa fa-search-plus ml-2" aria-hidden="true" onclick="viewDetail('nam')"></i>
                    </h5>
                    <canvas id="barChartNam"></canvas>
                    <div class="table-container mt-lg-3" id="namTable"></div>
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
        console.log("Script thành công");
        let barChartThang, barChartQuy, barChartNam;
    
        async function fetchData(thoiGian, thang, nam, hopDongId) {
            const response = await fetch(`/thongke/all?time_format=${thoiGian}&thang=${thang}&nam=${nam}&hop_dong=${hopDongId}`);
            return response.json();
        }
    
        function createBarChart(ctx, labels, dataKPI, dataTotal, tableId) {
            if (ctx.chart) {
                ctx.chart.destroy();
            }
            const backgroundColors = dataTotal.map((total, index) => {
                const percentage = dataKPI[index] ? (total / dataKPI[index] * 100).toFixed(1) : 'N/A';
                if (percentage > 100) return '#5E1675'; // Purple
                if (percentage > 70) return '#337357'; // Green
                if (percentage > 40) return '#FFD23F'; // Yellow
                return '#EE4266'; // Red
            });
            const legendMargin = {
                id: 'legendMargin',
                beforeInit(chart, legend, options) {
                    const fitValue=chart.legend.fit;
                    chart.legend.fit = function fit() {
                        fitValue.bind(chart.legend)();
                        return this.height +=15;
                    }
                }
            }
            const newChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'KPI',
                            data: dataKPI,
                            backgroundColor: '#1B5EBE'
                        },
                        {
                            label: 'Thực hiện',
                            data: dataTotal,
                            backgroundColor: backgroundColors
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
                                const percentage = dataKPI[index] ? ((dataTotal[index] / dataKPI[index]) * 100).toFixed(1) : 'N/A';
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
                            const percentage = dataKPI[index] ? ((value / dataKPI[index]) * 100).toFixed(1) : 'N/A';
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
            ctx.chart = newChart;
    
            const tableRows = dataTotal.map((total, index) => {
                const kpi = dataKPI[index];
                const percentage = kpi ? ((total / kpi) * 100).toFixed(1) : 'N/A';
                return `
                    <tr>
                        <td>${percentage}%</td>
                        <td>${labels[index]}</td>
                        <td>${kpi.toFixed(1)}</td>
                        <td>${total.toFixed(1)}</td>
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
    
            document.getElementById(tableId).innerHTML = `
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
    
        async function renderCharts() {
            const thang = document.getElementById('selectMonth').value;
            const nam = document.getElementById('selectYear').value;
            const hopDongId = document.getElementById('selectHopDong').value;
            const dataThang = await fetchData('thang', thang, nam, hopDongId);
            const dataQuy = await fetchData('quy', thang, nam, hopDongId);
            const dataNam = await fetchData('nam', thang, nam, hopDongId);
            const labelsThang = dataThang.map(item => item.ten_khu_vuc);
            const kpiThang = dataThang.map(item => item.kpi);
            const totalThang = dataThang.map(item => item.total);
            const labelsQuy = dataQuy.map(item => item.ten_khu_vuc);
            const kpiQuy = dataQuy.map(item => item.kpi);
            const totalQuy = dataQuy.map(item => item.total);
            const labelsNam = dataNam.map(item => item.ten_khu_vuc);
            const kpiNam = dataNam.map(item => item.kpi);
            const totalNam = dataNam.map(item => item.total);
    
            if (barChartThang) {
                barChartThang.destroy();
            }
            if (barChartQuy) {
                barChartQuy.destroy();
            }
            if (barChartNam) {
                barChartNam.destroy();
            }
            barChartThang = createBarChart(document.getElementById('barChartThang').getContext('2d'), labelsThang, kpiThang, totalThang, 'thangTable');
            barChartQuy = createBarChart(document.getElementById('barChartQuy').getContext('2d'), labelsQuy, kpiQuy, totalQuy, 'quyTable');
            barChartNam = createBarChart(document.getElementById('barChartNam').getContext('2d'), labelsNam, kpiNam, totalNam, 'namTable');
        }
        function viewDetail(thoiGian) {
            const month = document.getElementById('selectMonth').value;
            const year = document.getElementById('selectYear').value;
            const hopDongId = document.getElementById('selectHopDong').value;
            window.location.href = `/chi-tiet-chart?type=tongquat&time-format=${thoiGian}&thang=${month}&nam=${year}&hop_dong=${hopDongId}`;
        }
    
        document.getElementById('selectMonth').addEventListener('change', renderCharts);
        document.getElementById('selectYear').addEventListener('change', renderCharts);
        document.getElementById('selectHopDong').addEventListener('change', renderCharts);

        setInterval(renderCharts, 3600000); // Update every hour
        renderCharts(); // Initial render
    </script>
    <style>
        .legend-container {
            display: flex;
            justify-content: center;
            margin-top: 1rem;
        }
        .legend-item {
            display: flex;
            align-items: center;
        }
        .legend-item span {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 5px;
        }
        h5.d-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .fa-search-plus {
            cursor: pointer;
            margin-left: 10px;
        }
    </style>
</body>
</html>

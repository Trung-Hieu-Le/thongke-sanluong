@include('layouts.head_thongke')
<body>
    @include('layouts.header_thongke')
    <div class="container mt-3">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <h2 class="text-center mb-0">Thống kê tổng quát &nbsp;</h2>
                <select id="selectMonth" class="form-control mr-2">
                    @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ $i == date('n') ? 'selected' : '' }}>Tháng {{ $i }}</option>
                    @endfor
                </select>
                <select id="selectYear" class="form-control mr-2">
                    @for ($year = 2020; $year <= date('Y'); $year++)
                        <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
                    @endfor
                </select>
            </div>
            {{-- <a href="/thongke/khuvuc/" class="simple-link h4 mb-0"> Xem chi tiết</a> --}}
            <div class="dropdown">
                <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                  Xem chi tiết
                </button>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                  <li><a class="dropdown-item" href="/thongke/khuvuc/">Thống kê khu vực</a></li>
                  <li><a class="dropdown-item" href="/thongke/filter/">Lọc theo ngày</a></li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-lg-4 col-md-12">
                <h4>Thống kê theo Tháng <br>(đơn vị tính: tỉ đồng)</h4>
                <canvas id="barChartThang"></canvas>
                <div class="table-container mt-lg-3" id="thangTable"></div>
            </div>
            <div class="col-lg-4 col-md-12">
                <h4>Thống kê theo Quý <br>(đơn vị tính: tỉ đồng)</h4>
                <canvas id="barChartQuy"></canvas>
                <div class="table-container mt-lg-3" id="quyTable"></div>
            </div>
            <div class="col-lg-4 col-md-12">
                <h4>Thống kê theo Năm <br>(đơn vị tính: tỉ đồng)</h4>
                <canvas id="barChartNam"></canvas>
                <div class="table-container mt-lg-3" id="namTable"></div>
            </div>
        </div>
    </div>

    <script>
        console.log("Script thành công");
        let barChartThang, barChartQuy, barChartNam;
    
        async function fetchData(thoiGian, thang, nam) {
            const response = await fetch(`/thongke/all?time_format=${thoiGian}&thang=${thang}&nam=${nam}`);
            return response.json();
        }
    
        function createBarChart(ctx, labels, dataKPI, dataTotal, tableId) {
            if (ctx.chart) {
                ctx.chart.destroy();
            }
            const newChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'KPI',
                            data: dataKPI,
                            backgroundColor: '#ececec'
                        },
                        {
                            label: 'Thực hiện',
                            data: dataTotal,
                            backgroundColor: '#FE504F'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            ctx.chart = newChart;
    
            const tableRows = dataTotal.map((total, index) => {
                const kpi = dataKPI[index];
                const percentage = kpi ? ((total / kpi) * 100).toFixed(2) : 'N/A';
                return `
                    <tr>
                        <td>${percentage}%</td>
                        <td>${labels[index]}</td>
                        <td>${kpi.toFixed(2)}</td>
                        <td>${total.toFixed(2)}</td>
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
    
            document.getElementById(tableId).innerHTML = `
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Tỷ lệ</th>
                            <th>Đơn vị</th>
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
    
            const dataThang = await fetchData('thang', thang, nam);
            const dataQuy = await fetchData('quy', thang, nam);
            const dataNam = await fetchData('nam', thang, nam);
    
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
    
        document.getElementById('selectMonth').addEventListener('change', renderCharts);
        document.getElementById('selectYear').addEventListener('change', renderCharts);
    
        setInterval(renderCharts, 3600000); // Update every hour
        renderCharts(); // Initial render
    </script>
</body>
</html>

@include('layouts.head_thongke')
<body>
    @include('layouts.header_thongke')
    <div class=" mt-3">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <h2 class="text-center mb-0">Thống kê theo lĩnh vực &nbsp;</h2>
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
            </div>
            <hr>
        </div>

        @if (session('role') == 3)
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-12">
                    <h5>Thống kê theo Tháng <br>(đơn vị tính: tỉ đồng)</h5>
                    <i class="fa fa-search-plus" aria-hidden="true" onclick="viewDetail('thang')"></i>
                    <canvas id="barChartThang"></canvas>
                    <div class="table-container mt-lg-3" id="thangTable"></div>
                </div>
                <div class="col-lg-4 col-md-12">
                    <h5>Thống kê theo Quý <br>(đơn vị tính: tỉ đồng)</h5>
                    <i class="fa fa-search-plus" aria-hidden="true" onclick="viewDetail('quy')"></i>
                    <canvas id="barChartQuy"></canvas>
                    <div class="table-container mt-lg-3" id="quyTable"></div>
                </div>
                <div class="col-lg-4 col-md-12">
                    <h5>Thống kê theo Năm <br>(đơn vị tính: tỉ đồng)</h5>
                    <i class="fa fa-search-plus" aria-hidden="true" onclick="viewDetail('nam')"></i>
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

        async function fetchData(thoiGian, thang, nam) {
            const response = await fetch(`/thongke/linhvuc/all?time_format=${thoiGian}&thang=${thang}&nam=${nam}`);
            return response.json();
        }

        function createBarChart(ctx, labels, data, tableId) {
            if (ctx.chart) {
                ctx.chart.destroy();
            }
            const newChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Thực hiện',
                            data: data,
                            backgroundColor: '#EE3642' // Red
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
                                    return `${context.dataset.label}: ${context.raw}`;
                                }
                            }
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'end',
                            formatter: (value) => value,
                            color: '#000',
                            font: {
                                size: 10
                            },
                        },
                    }
                },
                plugins: [ChartDataLabels]
            });
            ctx.chart = newChart;

            const tableRows = data.map((total, index) => `
                <tr>
                    <td>${labels[index]}</td>
                    <td>${total.toFixed(2)}</td>
                </tr>
            `).join('');

            const total = data.reduce((acc, curr) => acc + curr, 0);

            const totalRow = `
                <tr>
                    <td><strong>Tổng cộng</strong></td>
                    <td><strong>${total.toFixed(2)}</strong></td>
                </tr>
            `;

            document.getElementById(tableId).innerHTML = `
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Đơn vị</th>
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
            const labelsThang = dataThang.map(item => item.ten_linh_vuc);
            const totalThang = dataThang.map(item => item.total);
            const labelsQuy = dataQuy.map(item => item.ten_linh_vuc);
            const totalQuy = dataQuy.map(item => item.total);
            const labelsNam = dataNam.map(item => item.ten_linh_vuc);
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
            barChartThang = createBarChart(document.getElementById('barChartThang').getContext('2d'), labelsThang, totalThang, 'thangTable');
            barChartQuy = createBarChart(document.getElementById('barChartQuy').getContext('2d'), labelsQuy, totalQuy, 'quyTable');
            barChartNam = createBarChart(document.getElementById('barChartNam').getContext('2d'), labelsNam, totalNam, 'namTable');
        }

        function viewDetail(thoiGian) {
            const month = document.getElementById('selectMonth').value;
            const year = document.getElementById('selectYear').value;
            window.location.href = `/chi-tiet-chart?type=linhvuc&time-format=${thoiGian}&thang=${month}&nam=${year}`;
        }

        document.getElementById('selectMonth').addEventListener('change', renderCharts);
        document.getElementById('selectYear').addEventListener('change', renderCharts);

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
    </style>
</body>
</html>

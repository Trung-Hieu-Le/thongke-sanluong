@include('layouts.head_thongke')
<body>
    @include('layouts.header_thongke')
    <div class="mt-3">
        <div class="container">
            <div class="d-flex align-items-center">
                <h2>Chi tiết biểu đồ</h2>
                <a href="javascript:history.back()" class="simple-link ms-3 h2">(Quay lại)</a>
            </div>
        </div>

        @if (session('role') == 3 || session('role') == 2)
        <div class="container">
            <div class="row">
                <div class="col-lg-8 col-md-12">
                    <canvas id="barChart"></canvas>
                </div>
                <div class="col-lg-4 col-md-12">
                    <div class="legend-container d-flex justify-content-center mt-3">
                        <div class="legend-item">
                            Chú thích: &nbsp;<span style="background-color: #EE3642;"></span> Dưới 33%
                        </div>
                        <div class="legend-item mx-2">
                            <span style="background-color: #EB5B00;"></span> 33-60%
                        </div>
                        <div class="legend-item">
                            <span style="background-color: #46D725;"></span> Trên 60%
                        </div>
                    </div>
                    <div id="statTable" class="mt-3"></div>
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
        let barChart;

        async function fetchData(type, thoiGian, thang, nam, hopDongId) {
            let response;
            if (type === "tongquat") {
                // TODO: lọc hợp đồng
                response = await fetch(`/thongke/all?time_format=${thoiGian}&thang=${thang}&nam=${nam}&hop_dong=${hopDongId}`);
            } else if (type === "linhvuc") {
                response = await fetch(`/thongke/linhvuc/all?time_format=${thoiGian}&thang=${thang}&nam=${nam}`);
            }
            return response.json();
        }

        function createBarChart(ctx, labels, dataKPI, dataTotal, showKPI) {
            if (ctx.chart) {
                ctx.chart.destroy();
            }

            const backgroundColors = dataTotal.map((total, index) => {
                const percentage = showKPI && dataKPI[index] ? (total / dataKPI[index] * 100).toFixed(1) : 'N/A';
                if (percentage > 60) return '#46D725';
                if (percentage > 33) return '#EB5B00';
                return '#EE3642';
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
            const datasets = [
                {
                    label: 'Thực hiện',
                    data: dataTotal,
                    backgroundColor: backgroundColors
                }
            ];

            if (showKPI) {
                datasets.unshift({
                    label: 'KPI',
                    data: dataKPI,
                    backgroundColor: '#ececec'
                });
            }

            const newChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: datasets
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
                                        const percentage = showKPI && dataKPI[index] ? ((dataTotal[index] / dataKPI[index]) * 100).toFixed(1) : 'N/A';
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
                                    const percentage = showKPI && dataKPI[index] ? ((value / dataKPI[index]) * 100).toFixed(1) : 'N/A';
                                    return `${value} \n${percentage}%`;
                                } else {
                                    return value;
                                }
                            },
                            color: '#000',
                            font: {
                                size: 15
                            },
                        }
                    }
                },
                plugins: [ChartDataLabels, legendMargin]
            });
            ctx.chart = newChart;
        }

        function getQueryParams() {
            const params = new URLSearchParams(window.location.search);
            return {
                type: params.get('type'),
                timeFormat: params.get('time-format'),
                thang: params.get('thang'),
                nam: params.get('nam'),
                hopDong: params.get('hop_dong')
                // TODO: Lọc hợp đồng
            };
        }

        async function renderCharts() {
            const params = getQueryParams();
            const type = params.type;
            const timeFormat = params.timeFormat;
            const thang = params.thang;
            const nam = params.nam;
            const hopDong = params.hopDong;

            const data = await fetchData(type, timeFormat, thang, nam, hopDong);
            const labels = type === "tongquat" ? data.map(item => item.ten_khu_vuc) : data.map(item => item.ten_linh_vuc);
            const kpi = type === "tongquat" || type === "linhvuc" ? data.map(item => item.kpi) : [];
            const total = data.map(item => item.total);
            const showKPI = type === "tongquat" || type === "linhvuc";

            if (barChart) {
                barChart.destroy();
            }
            console.log(labels, kpi, total, showKPI);
            barChart = createBarChart(document.getElementById('barChart').getContext('2d'), labels, kpi, total, showKPI);
            renderTable(labels, kpi, total, showKPI, 'statTable');
        }

        function renderTable(labels, kpi, total, showKPI, tableId) {
            const tableRows = labels.map((label, index) => {
                const percentage = showKPI && kpi[index] ? ((total[index] / kpi[index]) * 100).toFixed(1) : 'N/A';
                return `
                    <tr>
                        <td>${percentage}%</td>
                        <td>${label}</td>
                        <td>${kpi[index].toFixed(1)}</td>
                        <td>${total[index].toFixed(1)}</td>
                    </tr>
                `;
            }).join('');

            const totalKPI = kpi.reduce((acc, val) => acc + val, 0);
            const totalTotal = total.reduce((acc, val) => acc + val, 0);
            const totalPercentage = showKPI ? ((totalTotal / totalKPI) * 100).toFixed(1) : 'N/A';

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

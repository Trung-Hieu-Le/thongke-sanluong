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
                        <div class="legend-item me-2">
                            Chú thích: &nbsp;<span style="background-color: #EE4266; display: inline-block; width: 16px; height: 16px;"></span> <=40%
                        </div>
                        <div class="legend-item me-2">
                            <span style="background-color: #FFD23F; display: inline-block; width: 16px; height: 16px;"></span> <=70%
                        </div>
                        <div class="legend-item me-2">
                            <span style="background-color: #337357; display: inline-block; width: 16px; height: 16px;"></span> <=100%
                        </div>
                        <div class="legend-item">
                            <span style="background-color: #5E1675; display: inline-block; width: 16px; height: 16px;"></span> >100%
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
                response = await fetch(`/thongke/all?time_format=${thoiGian}&thang=${thang}&nam=${nam}&hop_dong=${hopDongId}`);
            } else if (type === "linhvuc") {
                response = await fetch(`/thongke/linhvuc/all?time_format=${thoiGian}&thang=${thang}&nam=${nam}`);
            }
            return response.json();
        }

        function createBarChart(ctx, labels, dataKPI, dataTotal, showKPI) {
            console.log(labels);
            if (ctx.chart) {
                ctx.chart.destroy();
            }

            const backgroundColors = dataTotal.map((total, index) => {
                const percentage = showKPI && dataKPI[index] ? (total / dataKPI[index] * 100).toFixed(1) : 'N/A';
                if (percentage > 100) return '#5E1675'; // Purple
                if (percentage > 70) return '#337357'; // Green
                if (percentage > 40) return '#FFD23F'; // Yellow
                return '#EE4266'; // Red
            });

            const legendMargin = {
                id: 'legendMargin',
                beforeInit(chart) {
                    const fitValue = chart.legend.fit;
                    chart.legend.fit = function fit() {
                        fitValue.bind(chart.legend)();
                        return this.height += 15;
                    };
                }
            };

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
                    backgroundColor: '#1B5EBE'
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
                                label: function (context) {
                                    if (context.dataset.label === 'Thực hiện') {
                                        const index = context.dataIndex;
                                        const percentage = showKPI && dataKPI[index] ? ((dataTotal[index] / dataKPI[index]) * 100).toFixed(1) : 'N/A';
                                        return `${context.dataset.label}: ${context.raw.toFixed(1)} (${percentage}%)`;
                                    } else {
                                        return `${context.dataset.label}: ${context.raw.toFixed(1)}`;
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
                                    return `${value.toFixed(1)} \n${percentage}%`;
                                } else {
                                    return value.toFixed(1);
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
        }

        function getQueryParams() {
            const params = new URLSearchParams(window.location.search);
            return {
                type: params.get('type'),
                timeFormat: params.get('time-format'),
                thang: params.get('thang'),
                nam: params.get('nam'),
                hopDong: params.get('hop_dong')
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
            // TODO: thêm khu vực
            const labels = type === "tongquat" ? data.map(item => item.ten_khu_vuc) : data.map(item => item.ten_linh_vuc + '-' + item.khu_vuc);
            const kpi = type === "tongquat" || type === "linhvuc" ? data.map(item => item.kpi) : [];
            const total = data.map(item => item.total);
            const showKPI = type === "tongquat" || type === "linhvuc";
            // let labels, kpi, total, showKPI;
            // if (type == "linhvuc") {
            //     labels = Array.from(new Set(data.map(item => item.ten_linh_vuc)));
            //     kpi = labels.map(label => data.filter(item => item.ten_linh_vuc === label).reduce((acc, curr) => acc + curr.kpi, 0));
            //     total = labels.map(label => data.filter(item => item.ten_linh_vuc === label).reduce((acc, curr) => acc + curr.total, 0));
            //     showKPI = true;
            // } else {
            //     labels = data.map(item => item.ten_khu_vuc);
            //     kpi = data.map(item => item.kpi);
            //     total = data.map(item => item.total);
            //     showKPI = true;
            // }

            if (barChart) {
                barChart.destroy();
            }
            barChart = createBarChart(document.getElementById('barChart').getContext('2d'), labels, kpi, total, showKPI);
            renderTable(type, data, 'statTable');
        }

        function renderTable(type, data, tableId) {
            let tableRows = '';
            let totalKPI = 0;
            let totalTotal = 0;

            if (type === "linhvuc") {
                const groupedData = {};
                data.forEach(item => {
                    if (!groupedData[item.khu_vuc]) {
                        groupedData[item.khu_vuc] = [];
                    }
                    groupedData[item.khu_vuc].push(item);
                });
                console.log(groupedData);

                for (const khuVuc in groupedData) {
                    const khuVucData = groupedData[khuVuc];
                    khuVucData.forEach(item => {
                        const percentage = item.kpi ? ((item.total / item.kpi) * 100).toFixed(1) : 'N/A';
                        tableRows += `
                            <tr>
                                <td>${percentage}%</td>
                                <td>${item.khu_vuc}</td>
                                <td>${item.ten_linh_vuc}</td>
                                <td>${item.kpi.toFixed(1)}</td>
                                <td>${item.total.toFixed(1)}</td>
                            </tr>
                        `;
                        totalKPI += item.kpi;
                        totalTotal += item.total;
                    });
                }
            } else {
                tableRows = data.map((item) => {
                    const percentage = item.kpi ? ((item.total / item.kpi) * 100).toFixed(1) : 'N/A';
                    totalKPI += item.kpi;
                    totalTotal += item.total;
                    return `
                        <tr>
                            <td>${percentage}%</td>
                            <td>${item.ten_khu_vuc}</td>
                            <td>${item.kpi.toFixed(1)}</td>
                            <td>${item.total.toFixed(1)}</td>
                        </tr>
                    `;
                }).join('');
            }

            const totalPercentage = totalKPI ? ((totalTotal / totalKPI) * 100).toFixed(1) : 'N/A';

            const totalRow = `
                <tr>
                    <td><strong>${totalPercentage}%</strong></td>
                    <td><strong>Tổng cộng</strong></td>
                    ${type === "linhvuc" ? '<td></td>' : ''}
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
                            ${type === "linhvuc" ? '<th>Lĩnh vực</th>' : ''}
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

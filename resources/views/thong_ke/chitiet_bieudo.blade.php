@include('layouts.head_thongke')
<body>
    @include('layouts.header_thongke')
    <div class=" mt-3">
        <div class="container">
            <h5>Chi tiết biểu đồ</h5>
            <a href="javascript:history.back()">Quay lại</a>
        </div>

        @if (session('role') == 3)
        <div class="container">
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
            <div class="row">
                <canvas id="barChart"></canvas>
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

    async function fetchData(type, thoiGian, thang, nam) {
        let response;
        if (type === "tongquat") {
            console.log("Lấy data tổng quát");
            response = await fetch(`/thongke/all?time_format=${thoiGian}&thang=${thang}&nam=${nam}`);
        } else if (type === "linhvuc") {
            console.log("Lấy data lĩnh vực");
            response = await fetch(`/thongke/linhvuc/all?time_format=${thoiGian}&thang=${thang}&nam=${nam}`);
        }
        return response.json(); // Ensure response.json() is called only once
    }

    function createBarChart(ctx, labels, dataKPI, dataTotal, showKPI) {
        if (ctx.chart) {
            ctx.chart.destroy();
        }

        const backgroundColors = dataTotal.map((total, index) => {
            const percentage = showKPI && dataKPI[index] ? (total / dataKPI[index] * 100).toFixed(2) : 'N/A';
            // if (percentage <= 33) return '#EE3642'; // Red
            // if (percentage <= 60) return '#EB5B00'; // Orange
            // return '#46D725'; // Green
            if (percentage > 60) return '#46D725';
            if (percentage > 33) return '#EB5B00';
            return '#EE3642';
        });

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
                                    const percentage = showKPI && dataKPI[index] ? ((dataTotal[index] / dataKPI[index]) * 100).toFixed(2) : 'N/A';
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
                                const percentage = showKPI && dataKPI[index] ? ((value / dataKPI[index]) * 100).toFixed(2) : 'N/A';
                                return `${value}\n(${percentage}%)`;
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
            plugins: [ChartDataLabels]
        });
        ctx.chart = newChart;
    }

    function getQueryParams() {
        const params = new URLSearchParams(window.location.search);
        return {
            type: params.get('type'),
            timeFormat: params.get('time-format'),
            thang: params.get('thang'),
            nam: params.get('nam')
        };
    }

    async function renderCharts() {
        const params = getQueryParams();
        const type = params.type;
        const timeFormat = params.timeFormat;
        const thang = params.thang;
        const nam = params.nam;

        const data = await fetchData(type, timeFormat, thang, nam);
        const labels = type === "tongquat" ? data.map(item => item.ten_khu_vuc) : data.map(item => item.ten_linh_vuc);
        const kpi = type === "tongquat" ? data.map(item => item.kpi) : [];
        const total = data.map(item => item.total);
        const showKPI = type === "tongquat";

        if (barChart) {
            barChart.destroy();
        }
        console.log(labels, kpi, total, showKPI);
        barChart = createBarChart(document.getElementById('barChart').getContext('2d'), labels, kpi, total, showKPI);
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

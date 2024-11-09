@include('layouts.head_thongke')
<body>
    @include('layouts.header_thongke')
    <div class="container row">
        <div class="col-12 breadcrumb-wrapper mt-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a class="simple-link" href="/">Tổng quát</a></li>
                    <li class="breadcrumb-item"><a class="simple-link" href="/thongke/filter">Lọc sản lượng</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Cập nhật bảng Dashboard</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="container mt-3 d-flex flex-column">
        <div>
            <!-- Display the message (success or error) -->
            <h3>{{ $message }}</h3>
        </div>
        <div>

            <!-- Button to go back to the previous page -->
            <button class="btn btn-primary" onclick="javascript:history.back()">Quay lại</button>
        </div>
    </div>
</body>
</html>

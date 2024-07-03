@include('layouts.head_thongke')
<body>
    @include('layouts.header_thongke')
    <div class="container">
        <div class="row">
            <div class="col-12 breadcrumb-wrapper mt-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a class="simple-link" href="/">Tổng quát</a></li>
                        <li class="breadcrumb-item"><a class="simple-link" href="/sanluong-khac/index">Sản lượng khác</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Thêm sản lượng</li>
                    </ol>
                </nav>
            </div>
        </div>
        <h2>Thêm mới sản lượng</h2>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        <div class="p-3 border">
        <form action="{{ route('sanluongkhac.handleAdd') }}" method="POST">
            @csrf
            <div class="row">
                <!-- Cột bên trái -->
                <div class="col-lg-7 col-md-12">
                    {{-- <div class="form-group m-2">
                        <label for="HopDong_Id">Hợp Đồng:</label>
                        <select class="form-control" id="HopDong_Id" name="HopDong_Id" required>
                            @foreach($hopdongs as $hopdong)
                                <option value="{{ $hopdong->HopDong_Id }}">{{ $hopdong->HopDong_SoHopDong }} - {{ $hopdong->HopDong_TenHopDong }}</option>
                            @endforeach
                        </select>
                    </div> --}}
                    <div class="form-group m-2">
                        <label for="SanLuong_Tram">Sản Lượng Trạm:</label>
                        <input type="text" class="form-control" id="SanLuong_Tram" name="SanLuong_Tram" required>
                    </div>
                    <div class="form-group m-2">
                        <label for="SanLuong_Gia">Sản Lượng Giá:</label>
                        <input type="text" class="form-control" id="SanLuong_Gia" name="SanLuong_Gia" pattern="^\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)" required>
                    </div>
                    <div class="form-group m-2">
                        <label for="SanLuong_Ngay">Ngày:</label>
                        <input type="date" class="form-control" id="SanLuong_Ngay" name="SanLuong_Ngay" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Thêm sản lượng</button>
        </form>
        </div>
    </div>
</body>
</html>

{{-- resources/views/sanluongkhac/create.blade.php --}}
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
                <div class="col">
                    {{-- <div class="form-group m-2">
                        <label for="HopDong_Id">Hợp Đồng:</label>
                        <select class="form-control" id="HopDong_Id" name="HopDong_Id" required>
                            @foreach($hopdongs as $hopdong)
                                <option value="{{ $hopdong->HopDong_Id }}">{{ $hopdong->HopDong_SoHopDong }}</option>
                            @endforeach
                        </select>
                    </div> --}}
                    <div class="form-group m-2">
                        <label for="khu_vuc">Khu vực:</label>
                        <select class="form-control" id="khu_vuc" name="khu_vuc" required>
                            <option value="">Chọn khu vực</option>
                            @foreach($khuvucs as $khuvuc)
                                <option value="{{ $khuvuc->khu_vuc }}">{{ $khuvuc->khu_vuc }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group m-2">
                        <label for="SanLuong_TenHangMuc">Lĩnh vực:</label>
                        <select class="form-control" id="SanLuong_TenHangMuc" name="SanLuong_TenHangMuc" required>
                            <option value="">Chọn lĩnh vực</option>
                            @foreach($noidungs as $noidung)
                                <option value="{{ $noidung->noi_dung }}">{{ $noidung->noi_dung }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- <div class="form-group m-2">
                        <label for="SanLuong_Tram">Sản Lượng Trạm:</label>
                        <input type="text" class="form-control" id="SanLuong_Tram" name="SanLuong_Tram" required>
                    </div> --}}
                </div>
                <div class="col">
                    <div class="form-group m-2">
                        <label for="SanLuong_Gia">Sản Lượng Ngày:</label>
                        <input type="text" class="form-control" id="SanLuong_Gia" name="SanLuong_Gia" pattern="^-?\d+(\.\d{1,4})?$" title="Vui lòng nhập số (đến chữ số thập phân thứ tư)" required>
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
    
    {{-- <script>
        document.getElementById('khu_vuc').addEventListener('change', function() {
            var khuVuc = this.value;
            fetch(`/sanluong-khac/noidung/${khuVuc}`)
                .then(response => response.json())
                .then(data => {
                    var noidungSelect = document.getElementById('SanLuong_TenHangMuc');
                    noidungSelect.innerHTML = '<option value="">Chọn lĩnh vực</option>';
                    data.forEach(function(noidung) {
                        var option = document.createElement('option');
                        option.value = noidung.noi_dung;
                        option.text = noidung.noi_dung;
                        noidungSelect.appendChild(option);
                    });
                });
        });
    </script> --}}
</body>
</html>

<header class="container-fluid sticky-top fw-2 rounded p-0 g-0 bg-white" style="box-shadow: 0 10px 10px 0 rgba(200,200,200,0.2);">
    <div class="container-fluid">
        <div class="row justify-content-center align-items-start g-2">
        <div class="col-4 col-sm-4 col-lg-2 text-center text-sm-start mt-1">
            <a class="m-0 ms-xxl-4 p-0 ps-xl-2 ps-lg-0 opacity-100 pb-1" href="/">
                <img src="{{ asset('images/vtk_logo.jpg') }}" class="img-fluid logo" alt="..." style="height:50px; width:107px;">
            </a>
        </div>
        <div class="col-8 col-sm-8 col-lg-10">
            <nav class="navbar navbar-expand-lg justify-content-center py-0">
                <div class="container-fluid justify-content-end">
                <button class="navbar-toggler h-100 border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fa fa-bars text-dark" aria-hidden="true" style="font-size: 30px;"></i>
                </button>
                <div class="collapse navbar-collapse justify-content-end text-end" id="navbarSupportedContent">
                    <ul class="navbar-nav fill h-100" style="font-size: 15px;">
                        <li class="nav-item dropdown ms-1 me-2">
                            <a class="nav-link fw-semibold " href="#" style="color:#040404 ;z-index: 2;" data-bs-toggle="dropdown">THỐNG KÊ</a>
                            <ul class="dropdown-menu p-0 text-end text-lg-start border-0 shadow-sm">
                                <li class="dropdown-navbar">
                                    <a class="dropdown-item fs-5" href="/">
                                        Thống kê tổng quát
                                    </a>
                                </li>
                                <li class="dropdown-navbar">
                                    <a class="dropdown-item fs-5" href="/thongke/khuvuc">
                                        Thống kê theo khu vực
                                    </a>
                                </li>
                                <li class="dropdown-navbar">
                                    <a class="dropdown-item fs-5" href="/thongke/linhvuc">
                                        Thống kê theo lĩnh vực
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item ms-1 me-2">
                            <a class="nav-link fw-semibold " href="{{ route('tram.filter')}}" style="color:#040404 ;z-index: 2;">SẢN LƯỢNG THI CÔNG</a>
                        </li>
                        <li class="nav-item ms-1 me-2">
                            <a class="nav-link fw-semibold " href="/kpi-quy/index" style="color:#040404 ;z-index: 2;">KPI</a>
                        </li>
                        <li class="nav-item ms-1 me-2 bg-danger rounded-pill mb-2 px-2">
                            <a class="nav-link fw-semibold text-light" href="/sanluong-khac/index" style="color:#040404 ;z-index: 2;">LĨNH VỰC KHÁC +</a>
                        </li>
                        <li class="nav-item ms-1 me-2">
                            <a class="nav-link fw-semibold" href="/chat" style="color:#040404; font-size:20px; z-index: 2; position: relative;">
                                <i class="fa fa-comments" aria-hidden="true"></i>
                                <span id="unread-indicator" style="display:none; position: absolute; top: 0px; right: 0px; width: 10px; height: 10px; background-color: red; border-radius: 50%;"></span>
                            </a>
                        </li>                        
                        <li class="nav-item dropdown ms-1 me-2">
                            <a style="color:#040404; font-size:20px ;z-index: 2;" href="#" class="nav-link fw-semibold" data-bs-toggle="dropdown"
                            aria-expanded="true">
                                <i class="fa fa-user-circle" aria-hidden="true"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end p-0 text-end text-lg-start border-0 shadow-sm">
                                {{-- <li class="dropdown-navbar">
                                    <a class="dropdown-item fs-5" href="{{ route('xdsoft.khoahoc')}}">
                                        Hồ sơ
                                    </a>
                                </li> --}}
                                <li class="dropdown-navbar">
                                    <a class="dropdown-item fs-5" href="/logout" onclick="return confirm('Bạn có muốn đăng xuất?');">
                                        Đăng xuất
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
                </div>
            </nav>
        </div>
        </div>
    </div>
</header>
<script>
    function checkUnreadMessages() {
    fetch('/chat/checkUnreadMessages', {
        method: 'GET'
    })
    .then(response => response.json())
    .then(data => {
        if (data.unread_count > 0) {
            document.getElementById('unread-indicator').style.display = 'block';
        } else {
            document.getElementById('unread-indicator').style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Error checking unread messages:', error);
    });
}
checkUnreadMessages();
</script>
@include('layouts.head_thongke')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" integrity="sha512-tS3S5qG0BlhnQROyJXvNjeEM4UpMXHrQfTGmbQ1gKmelCxlSEBUaxhRBj/EFTzpbP4RVSrpEikbmdJobCvhE3g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css" integrity="sha512-sMXtMNL1zRzolHYKEujM2AqCLUR9F2C4/05cdbxjjLSRvMQIciEPCQZo++nk7go3BtSuK9kfa/s+a4f4i5pLkw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js" integrity="sha512-bPs7Ae6pVvhOSiIcyUClR7/q2OAsRiovw4vAkX+zJbw3ShAeeqezq50RIIcIURq7Oa20rW2n2q+fyXBNcU9lrw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<body>
    @include('layouts.header_thongke')
    <div class="container row">
        <div class="col-12 breadcrumb-wrapper mt-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a class="simple-link" href="/">Tổng quát</a></li>
                    <li class="breadcrumb-item"><a class="simple-link" href="/thongke/filter">Lọc sản lượng</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Chi tiết hình ảnh trạm</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="container mt-3 d-flex flex-column">
        <div>
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link" href="/viewsanluong/{{ $ma_tram }}?days={{ implode(',', $days) }}">Sản lượng</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="/viewhinhanh/{{ $ma_tram }}?days={{ implode(',', $days) }}">Hình ảnh</a>
                </li>
            </ul>
        </div>

        <div class="main-content px-3 mt-3">
            <form id="filterForm" method="GET" action="{{ url('/viewhinhanh/'.$ma_tram) }}" class="form-inline mb-3">
                @csrf
                <div class="form-group">
                    <input type="text" class="form-control date" name="days" placeholder="Chọn các ngày lọc" value="{{ implode(',', $days) }}">
                    <button class="btn btn-primary mb-1" type="submit">Lọc</button>
                </div>
            </form>
            <h2>Danh sách hạng mục và ảnh</h2>
            <div id="results">
                @foreach ($groupedData as $item)
                    <h3>{{ $item['ten_hang_muc'] }}</h3>
                    <div class="owl-carousel">
                        @if (count($item['anh_chuan_bi']) === 0 && count($item['anh_da_xong']) === 0)
                            <div class="item">
                                <div class="card">
                                    <img src="{{asset('/images/default_img.svg')}}" class="card-img-top" alt="No image available">
                                    <div class="card-body">
                                        <p class="card-text">No image available</p>
                                        <p class="card-text">No image available</p>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- TODO: Sửa lại link đường dẫn img phù hợp --}}
                            @foreach ($item['anh_chuan_bi'] as $image)
                                <div class="item">
                                    <div class="card">
                                        <img src="http://dashboard.vtk.com.vn:8083/PEC/VTK/{{ $ma_tram }}/{{ $image }}" 
                                        onerror="this.onerror=null;this.src='{{ asset('/images/default_img.svg') }}';"
                                        class="card-img-top" alt="{{ $image }}">
                                        <div class="card-body">
                                            <p class="card-text">{{ $image }}</p>
                                            <p class="card-text">Chuẩn bị</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            @foreach ($item['anh_da_xong'] as $image)
                                <div class="item">
                                    <div class="card">
                                        <img src="http://dashboard.vtk.com.vn:8083/PEC/VTK/{{ $ma_tram }}/{{ $image }}" 
                                        onerror="this.onerror=null;this.src='{{ asset('/images/default_img.svg') }}';"
                                        class="card-img-top" alt="{{ $image }}">
                                        <div class="card-body">
                                            <p class="card-text">{{ $image }}</p>
                                            <p class="card-text">Đã xong</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            const selectedDays = @json($days).map(day => new Date(day.slice(4, 8) + '-' + day.slice(2, 4) + '-' + day.slice(0, 2)));
            $('.date').datepicker({
                multidate: true,
                format: 'dd-mm-yyyy'
            }).datepicker('setDates', selectedDays).on('changeDate', function(e) {
                const selectedDates = $(this).datepicker('getFormattedDate');
                $('input[name="days"]').val(selectedDates);
            });

            initializeOwlCarousel();

            function initializeOwlCarousel() {
                $('.owl-carousel').each(function() {
                    var $this = $(this);
                    if ($this.find('.item').length > 1) {
                        $this.owlCarousel({
                            loop: false,
                            margin: 10,
                            nav: false,
                            responsive: {
                                0: {
                                    items: 1
                                },
                                600: {
                                    items: 3
                                },
                                1000: {
                                    items: 5
                                }
                            }
                        });
                    } else {
                        $this.addClass('single-item');
                    }
                });
            }
        });
    </script>
    <style>
        .owl-carousel {
            margin-top: 20px;
        }
        .owl-carousel .item img {
            display: block;
            width: 100%;
            height: 250px; /* Set the height */
            object-fit: cover; /* Ensure the image fits within the specified height */
        }
        .card {
            width: auto; /* Let the width be flexible */
        }
        .single-item .item {
            display: inline-block;
        }
    </style>
</body>
</html>

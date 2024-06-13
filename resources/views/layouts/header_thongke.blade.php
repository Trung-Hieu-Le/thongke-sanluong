<!DOCTYPE html>
<html>
<head>
    <title>Thống kê sản lượng Viettel VTK</title>
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/vtk_logo.jpg') }}"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        /* Header */
        .header {
            position: sticky;
            top: 0;
            background-color: #fff;
            padding: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }
        .header img {
            height: 40px;
            margin-right: 20px;
        }
        /* Simple link */
        a.simple-link {
            color: black;
            text-decoration: none;
        }
        a.simple-link:hover {
            text-decoration: underline;
        }

        .container {
            /* width: 85%; */
            margin: auto;
        }
        .breadcrumb { margin-top: 10px;}
        .form-control {width:fit-content; display: inline;}
        .bar-chart{
            /* background-color: #44494D; */
            /* color: white; */
            margin: 10px;
            padding: 10px;
        }
        .bar-chart canvas {width: 100%;}
        .scrollable-table {
            width: 100%;
            overflow-x: auto;
        }
        table {
            width: 100%;
            /* margin: 10px; */
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
            white-space: nowrap; /* Ngăn tiêu đề xuống dòng */
    overflow: hidden; /* Ẩn phần nội dung tràn */
    text-overflow: ellipsis;
        }
    </style>
</head>

{{-- Meta tags --}}
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="id" content="{{ $userId }}">
<meta name="messenger-theme" content="{{ 'light' }}">

<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="url" content="{{ url('') }}" data-user="{{ session('userId') }}">


{{-- scripts --}}
<script
  src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('js/chatify/font.awesome.min.js') }}"></script>
<script src="{{ asset('js/chatify/autosize.js') }}"></script>
{{-- <script src="{{ asset('js/app.js') }}"></script> --}}
<script src='https://unpkg.com/nprogress@0.2.0/nprogress.js'></script>

{{-- styles --}}
<link rel='stylesheet' href='https://unpkg.com/nprogress@0.2.0/nprogress.css'/>
<link href="{{ asset('css/chatify/style.css') }}" rel="stylesheet" />
<link href="{{ asset('css/chatify/light.mode.css') }}" rel="stylesheet" />
{{-- <link href="{{ asset('css/app.css') }}" rel="stylesheet" /> --}}
<style>
  :root {
      --primary-color: #dc3545 ;
  }

  .unread-badge {
    background-color: red;
    color: white;
    border-radius: 50%;
    padding: 5px;
    font-size: 12px;
    position: absolute;
    top: 0;
    right: 0;
}

</style>
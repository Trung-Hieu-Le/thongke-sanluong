@if(session('role') == 3)
<div class="messenger-sendCard" id="messenger-sendCard">
    <form id="messageForm" class="message-form" method="POST" enctype="multipart/form-data">
        @csrf
        <label><span class="fas fa-plus-circle"></span><input type="file" class="upload-attachment" name="file" /></label>
        {{-- <button class="emoji-button"></span><span class="fas fa-smile"></button> --}}
        <textarea name="message" class="m-send app-scroll" placeholder="Viết tin nhắn.."></textarea>
        <button class="send-button"><span class="fas fa-paper-plane"></span></button>
    </form>
</div>
@else
<div></div>
@endif

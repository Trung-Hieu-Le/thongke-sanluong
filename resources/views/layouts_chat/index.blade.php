@include('layouts_chat.headLinks')
@include('layouts.head_thongke')
@include('layouts.header_thongke')
<div class="messenger mt-2">
    {{-- ----------------------Users/Groups lists side---------------------- --}}
    <div class="messenger-listView {{ !!$userId ? 'conversation-active' : '' }}" id="messengerListView">
        {{-- Header and search bar --}}
        <div class="m-header">
            <nav style="display: flex; align-items: center; justify-content: space-between; padding: 20px 10px 10px 10px;">
                <a href="#" style="display: flex; align-items: center;">
                    <i class="fas fa-inbox text-danger"></i>
                    <span class="messenger-headTitle" style="margin-left: 10px;">TIN NHẮN</span>
                </a>
                <div>
                    <i class="toggleButton" aria-label="Toggle Menu 1" style="cursor: pointer;">
                        <span class="fas fa-angle-down" style="color: black; font-size:20px;"></span>
                    </i>
                </div>
            </nav>
            
            <input type="text" class="messenger-search" placeholder="Search" id="search" />
        </div>
        {{-- tabs and lists --}}
        <div class="m-body contacts-container">
           {{-- ---------------- [ User Tab ] ---------------- --}}
           <div class="show messenger-tab users-tab app-scroll" data-view="users">
               <p class="messenger-title"><span>Tất cả tin nhắn</span></p>
               <div class="listOfContacts" style="width: 100%;height: calc(100% - 272px);position: relative;">
                <div class="user-list">
                    <ul id="userList" class="messenger-list">
                        <!-- User list will be populated here by JS -->
                    </ul>
                </div>
               </div>
           </div>
            
        </div>
    </div>

    {{-- ----------------------Messaging side---------------------- --}}
    <div class="messenger-messagingView">
        {{-- header title [conversation name] amd buttons --}}
        <div class="m-header m-header-messaging">
            <nav class="chatify-d-flex chatify-justify-content-between chatify-align-items-center">
                {{-- header back button, avatar and user name --}}
                <div class="chatify-d-flex chatify-justify-content-between chatify-align-items-center">
                    {{-- <a href="#" class="show-listView"><i class="fas fa-arrow-left"></i></a> --}}
                    <div class="avatar av-s header-avatar" style="margin: 0px 10px; margin-top: -5px; margin-bottom: -5px;">
                    </div>
                    <h2 id="userNameChatHeader" style="margin: 0; font-size: 18px;"></h2>
                </div>
                <div>
                    <i class="toggleButton" aria-label="Toggle Menu 2" style=" cursor: pointer; position: absolute; right: 10px; top: 15px;">
                        <span class="fas fa-angle-down" style="color: black; font-size:20px;"></span>
                    </i>
                </div>
                
            </nav>
            {{-- Internet connection --}}
            <div class="internet-connection">
                <span class="ic-connected">Connected</span>
                <span class="ic-connecting">Connecting...</span>
                <span class="ic-noInternet">No internet access</span>
            </div>
        </div>

        {{-- Messaging area --}}
        <div class="m-body messages-container app-scroll">
            <div class="messages">
                {{-- <p class="message-hint center-el"><span>Please select a chat to start messaging</span></p> --}}
                <div class="chat-window">
                    <div class="chat-history" id="chatHistory">
                        <!-- Chat history will be displayed here -->
                    </div>
                </div>
            </div>
            {{-- Typing indicator --}}
            <div class="typing-indicator">
                <div class="message-card typing">
                    <div class="message">
                        <span class="typing-dots">
                            <span class="dot dot-1"></span>
                            <span class="dot dot-2"></span>
                            <span class="dot dot-3"></span>
                        </span>
                    </div>
                </div>
            </div>

        </div>
        {{-- Send Message Form --}}
        @include('layouts_chat.sendForm')
    </div>
</div>
<script>
    const myUserId = @json($userId);
</script>
@include('layouts_chat.footerLinks')



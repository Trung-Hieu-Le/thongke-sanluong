    const messagesContainer = $(".messenger-messagingView .m-body"),
        messengerTitleDefault = $(".messenger-headTitle").text(),
        messageInputContainer = $(".messenger-sendCard"),
        messageInput = $("#message-form .m-send"),
        auth_id = $("meta[name=url]").data("user"),
        url = $("meta[name=url]").attr("content"),
        messengerTheme = $("meta[name=messenger-theme]").attr("content"),
        defaultMessengerColor = $("meta[name=messenger-color]").attr("content"),
        csrfToken = $('meta[name="csrf-token"]').attr("content"),
        userListContainer = document.querySelector('.user-list'),
        chatHistory = document.getElementById('chatHistory');
        window.currentChatUserId = null;
        

    // Helper functions to get/set messenger ID
    const getMessengerId = () => $("meta[name=id]").attr("content");
    const setMessengerId = (id) => $("meta[name=id]").attr("content", id);
    
    function fetchUsers(query = '') {
        fetch(`/chat/search?query=${query}`, {
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(response => response.json())
        .then(displayUserList)
        .catch(console.error);
    }

    function displayUserList(users) {
        userListContainer.innerHTML = ''; // Clear existing list
        users.forEach(user => {
            
            const table = createUserTable(user);
            userListContainer.appendChild(table);
        });
    }

    function createUserTable(user) {
        const table = document.createElement('table');
        table.className = 'messenger-list-item';
        table.dataset.contact = user.user_id;
        table.style.width = '100%';
        table.style.tableLayout = 'fixed';
        
        const tr = document.createElement('tr');
        tr.dataset.action = '0';
        
        const tdInfo = createInfoCell(user);
        
        tr.appendChild(tdInfo);
        table.appendChild(tr);
        
        table.addEventListener('click', () => handleUserClick(user));
        
        return table;
    }

    function createInfoCell(user) {
        const tdInfo = document.createElement('td');
        tdInfo.style.textAlign = 'start';
        tdInfo.style.border = '1px solid #f5f5f5';
        
        const p = document.createElement('p');
        p.dataset.id = user.user_id;
        p.dataset.type = 'user';
        p.textContent = user.user_name;
        
        const unreadCount = document.createElement('span');
        unreadCount.className = 'unread-count';
        unreadCount.style.backgroundColor = 'red';
        unreadCount.style.color = 'white';
        unreadCount.style.borderRadius = '50%';
        unreadCount.style.padding = '5px';
        unreadCount.style.minWidth = '20px';
        unreadCount.style.marginLeft = '10px';
        unreadCount.style.display = 'none';
        unreadCount.style.textAlign = 'center';
        unreadCount.style.marginRight = '10px';
        unreadCount.textContent = user.unread_count > 0 ? user.unread_count : '';
        
        if (user.unread_count > 0) {
            unreadCount.style.display = 'inline-block';
        }

        p.appendChild(unreadCount);

        const messageSpan = document.createElement('span');
        if (user.sender_id == myUserId) {
            if (user.latest_message) {
                messageSpan.textContent = 'Bạn: ' + user.latest_message;
            } else if (user.has_attachment) {
                messageSpan.textContent = 'Bạn: Gửi file đính kèm';
            } else {
                messageSpan.textContent = ''; 
            }
        } else {
            if (user.latest_message) {
                messageSpan.textContent = user.user_name + ': ' + user.latest_message;
            } else if (user.has_attachment) {
                messageSpan.textContent = user.user_name + ': Gửi file đính kèm';
            } else {
                messageSpan.textContent = '';
            }
        }
        messageSpan.style.display = 'block'; 
        messageSpan.style.whiteSpace = 'nowrap'; 
        messageSpan.style.overflow = 'hidden';
        messageSpan.style.textOverflow = 'ellipsis'; 

        tdInfo.appendChild(p);
        tdInfo.appendChild(messageSpan);

        return tdInfo;
    }

    function handleUserClick(user) {
        loadChat(user.user_id);
        updateSelectedContact(user.user_id);
        window.currentChatUserId = user.user_id;
        document.getElementById('userNameChatHeader').textContent = user.user_name;        
        markMessagesAsSeen(user.user_id);

        const unreadCountElement = document.querySelector(`[data-contact="${user.user_id}"] .unread-count`);
        if (unreadCountElement) {
            unreadCountElement.textContent = '';
            unreadCountElement.style.display = 'none';
        }
        const element = document.querySelector('.messenger-listView');
        if (element) {
            element.classList.remove('force-show');
        }
    }

    function loadChat(userId) {
        fetchChatData(userId)
            .then(data => {
                if (data && data.messages) {
                    displayChatHistory(data.messages);
                    showMessageForm();
                } else {
                    console.error('No messages found or data is invalid');
                }
            })
            .catch(console.error);
    }

    function fetchChatData(userId) {
        return fetch(`/chat/loadChat/${userId}`, {
            //TODO: nếu có lỗi token thì xem xét xóa phần này đi
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(response => response.json());
    }

    function displayChatHistory(messages) {
        chatHistory.innerHTML = '';
        messages.forEach(message => {
            const messageCard = createMessageCard(message);
            chatHistory.appendChild(messageCard);
        });
        scrollChatToBottom();
    }
    function scrollChatToBottom() {
        const messagesContainer = document.querySelector('.m-body.messages-container.app-scroll');
        if (messagesContainer) {
            setTimeout(() => {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }, 500);
        }
    }

    function createMessageCard(message) {
        const div = document.createElement('div');
        div.className = 'message-card';
    
        if (message.sender_id == myUserId) {
            div.classList.add('mc-sender');
        } else {
            div.classList.add('mc-receiver');
        }
    
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message';
        if (message.message) {
            contentDiv.innerHTML = message.message;
        }
    
        const timestamp = document.createElement('span');
        timestamp.className = 'message-time';
        timestamp.textContent = new Date(message.created_at).toLocaleString('en-GB', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    
        const attachmentsWrapper = document.createElement('div');
        if (message.attachments) {
            JSON.parse(message.attachments).forEach(attachment => {
                const attachmentElement = createAttachmentElement(attachment);
                attachmentsWrapper.appendChild(attachmentElement);
            });
        }
    
        const deleteButton = document.createElement('span');
        deleteButton.innerHTML = '<i class="fas fa-trash"></i>';
        deleteButton.style.padding = '6px 12px';
        deleteButton.style.cursor = 'pointer';
        deleteButton.onclick = function () {
            if (confirm('Bạn có chắc muốn xóa tin nhắn này không?')) {
                deleteMessage(message.id);
            }
        };
    
        if (message.sender_id == myUserId) {
            div.appendChild(deleteButton);
            div.appendChild(timestamp);
            if (message.message) div.appendChild(contentDiv);
            if (message.attachments) div.appendChild(attachmentsWrapper);
        } else {
            if (message.message) div.appendChild(contentDiv);
            if (message.attachments) div.appendChild(attachmentsWrapper);
            div.appendChild(timestamp);
        }
    
        return div;
    }
    

    function createAttachmentElement(attachment) {
        let link, ext = attachment.new_name.split('.').pop().toLowerCase();
        const wrapper = document.createElement('div');
        const fileUrl = `/storage/attachments/${attachment.new_name}`;

        if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
            link = document.createElement('img');
            link.src = fileUrl;
            link.alt = attachment.old_name;
            link.style.maxWidth = '250px';
            link.style.maxHeight = '250px';
        } else if (['mp4', 'webm', 'ogg'].includes(ext)) {
            link = document.createElement('video');
            link.src = fileUrl;
            link.controls = true;
            link.style.maxWidth = '250px';
            link.style.maxHeight = '250px';
        } else {
            link = document.createElement('a');
            link.href = fileUrl;
            link.textContent = attachment.old_name;
            link.target = '_blank';
        }

        const downloadButton = document.createElement('span');
        downloadButton.innerHTML = '<i class="fas fa-download"></i>'; 
        downloadButton.style.padding = '6px 12px';
        downloadButton.style.cursor = 'pointer';
        downloadButton.onclick = () => {
            const a = document.createElement('a');
            a.href = fileUrl;
            
            a.download = attachment.new_name;
            a.click();
        };

        wrapper.appendChild(link);
        wrapper.appendChild(downloadButton);
        return wrapper;
    }

    function showMessageForm() {
        const messageForm = document.getElementById('messenger-sendCard');
        if (messageForm) {
            messageForm.style.display = 'block';
        }
    }

    function deleteMessage(messageId) {
        fetch(`chat/deleteMessage/${messageId}`, {
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                loadChat(window.currentChatUserId);
            } else {
                alert('An error occurred while deleting the message.');
            }
        });
    }

    function updateSelectedContact(user_id) {
        const contactSelector = `.messenger-list-item[data-contact="${user_id || getMessengerId()}"]`;
        $(".messenger-list-item").removeClass("m-list-active");
        $(contactSelector).addClass("m-list-active");
    }

    document.getElementById('search').addEventListener('input', function() {
        fetchUsers(this.value);
    });
    fetchUsers(); 

    document.getElementById('messageForm').addEventListener('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        formData.append('receiver_id', window.currentChatUserId);
        fetch('/chat/sendMessage', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            document.querySelector('textarea[name="message"]').value = '';
            loadChat(window.currentChatUserId);
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
document.querySelector('.upload-attachment').addEventListener('change', function(e) {
    if (this.files.length === 0) {
        return;
    }
    let formData = new FormData();
    for (let i = 0; i < this.files.length; i++) {
        formData.append('attachments[]', this.files[i]);
    }
    formData.append('receiver_id', window.currentChatUserId);
    fetch('/chat/sendMessage', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'Message sent successfully') {
            e.target.value = '';
            loadChat(window.currentChatUserId);
        } else {
            console.error('Error:', data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});
document.querySelectorAll('.toggleButton').forEach(button => {
    button.addEventListener('click', function() {
        var element = document.querySelector('.messenger-listView');
        element.classList.toggle('force-show');
    });
});
function markMessagesAsSeen(userId) {
    fetch(`/mark-messages-as-seen/${userId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            console.log('Messages marked as seen');
        } else {
            console.error('Failed to mark messages as seen');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
function updateUnreadMessageCount(userId) {
    fetch(`/get-unread-message-count/${userId}`)
        .then(response => response.json())
        .then(data => {
            const unreadCount = data.unreadCount;
            const userElement = document.querySelector(`.messenger-list-item[data-contact="${userId}"]`);
            const unreadBadge = userElement.querySelector('.unread-badge');

            if (unreadCount > 0) {
                if (!unreadBadge) {
                    const badge = document.createElement('span');
                    badge.className = 'unread-badge';
                    badge.innerText = unreadCount;
                    userElement.appendChild(badge);
                } else {
                    unreadBadge.innerText = unreadCount;
                }
            } else if (unreadBadge) {
                unreadBadge.remove();
            }
        })
        .catch(console.error);
}


const pusher = new Pusher('1439f031a8d034d7068a', {
    cluster: 'ap1',
    encrypted: true
});

const userId = myUserId;
const channel = pusher.subscribe(`chat-channel.${userId}`);

channel.bind('message.sent', function(data) {
    if (window.currentChatUserId === data.senderId) {
        loadChat(window.currentChatUserId);
    } else {
        const contactElement = document.querySelector(`[data-contact="${data.senderId}"] .unread-count`);
        if (contactElement) {
            let unreadCount = parseInt(contactElement.textContent) || 0;
            contactElement.textContent = unreadCount + 1;
            contactElement.style.display = 'inline-block';
        }
    }
});

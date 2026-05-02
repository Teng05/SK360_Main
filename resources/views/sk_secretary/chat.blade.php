{{-- File guide: Blade view template for resources/views/sk_secretary/chat.blade.php. --}}
@extends('layouts.app')

@section('title', 'SK 360 Chat')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100 overflow-hidden">
    {{-- Shared secretary sidebar/topbar layout --}}
    @include('sk_secretary.partials.sidebar')
    <div class="flex-1 flex flex-col overflow-hidden">
        @include('sk_secretary.partials.topbar')
        {{-- Chat room and messages area --}}
        <main class="flex-1 overflow-y-auto bg-gray-50 p-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Real-Time Chat</h1>
                <p class="text-gray-500">Formal communication channel for SK federation</p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
                <div class="min-w-0 rounded-3xl border border-gray-100 bg-white shadow-sm lg:w-[320px]">
                    <div class="border-b border-gray-100 p-4">
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300">&#128269;</span>
                            <input id="roomSearch" type="text" placeholder="Search users or groups..." class="w-full rounded-xl bg-gray-50 py-3 pl-10 pr-4 text-sm text-gray-700 outline-none ring-1 ring-transparent focus:ring-red-200">
                        </div>
                    </div>
                    <div id="roomList" class="max-h-[560px] space-y-2 overflow-y-auto p-3"></div>
                </div>

                <div class="min-w-0 rounded-3xl border border-gray-100 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 p-5">
                        <div>
                            <h2 id="activeRoomName" class="text-lg font-black text-gray-900">No active conversation</h2>
                            <p id="activeRoomMeta" class="text-xs text-gray-400">Search for a user or create a group to start chatting</p>
                        </div>
                        <div class="flex items-center gap-2 text-gray-400">
                            <button type="button" class="rounded-lg border border-gray-200 px-2 py-1 text-xs">&#128249;</button>
                            <button type="button" class="rounded-lg border border-gray-200 px-2 py-1 text-xs">&#128222;</button>
                        </div>
                    </div>

                    <div id="chatStatus" class="px-5 pt-4 text-xs text-gray-400">No conversation selected yet.</div>

                    <div id="messageList" class="h-[440px] min-w-0 overflow-x-hidden overflow-y-auto px-5 py-4 space-y-3"></div>

                    <div class="border-t border-gray-100 p-4">
                        <form id="messageForm" class="flex items-center gap-3">
                            <button type="button" class="rounded-lg border border-gray-200 px-3 py-2 text-gray-400">&#128206;</button>
                            <input id="messageInput" type="text" placeholder="Type your message..." class="flex-1 rounded-xl bg-gray-50 px-4 py-3 text-sm text-gray-700 outline-none ring-1 ring-transparent focus:ring-red-200" disabled>
                            <button id="sendMessageBtn" type="submit" class="rounded-lg bg-red-500 px-4 py-3 text-white hover:bg-red-600 transition disabled:cursor-not-allowed disabled:opacity-50" disabled>&#10148;</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
@include('sk_secretary.partials.dropdown-scripts')
<script type="module">
    import { initializeApp } from "https://www.gstatic.com/firebasejs/12.12.1/firebase-app.js";
    import {
        getFirestore,
        collection,
        addDoc,
        query,
        where,
        orderBy,
        onSnapshot,
        serverTimestamp,
        limit,
        getDocs
    } from "https://www.gstatic.com/firebasejs/12.12.1/firebase-firestore.js";

    const firebaseConfig = {
        apiKey: "AIzaSyAxzGOnsSKoapk_PPNdD-A-48JlwNTkNrw",
        authDomain: "sk360chat.firebaseapp.com",
        projectId: "sk360chat",
        storageBucket: "sk360chat.firebasestorage.app",
        messagingSenderId: "1015579839049",
        appId: "1:1015579839049:web:0d5d440359bed55d858d98"
    };

    const app = initializeApp(firebaseConfig);
    const db = getFirestore(app);

    const currentUser = {
        id: @json($currentUserId),
        name: @json($fullName),
        role: @json($currentUserRole)
    };

    let rooms = [];
    let activeRoomId = null;
    let unsubscribeMessages = null;
    let searchDebounce = null;

    const roomList = document.getElementById('roomList');
    const roomSearch = document.getElementById('roomSearch');
    const activeRoomName = document.getElementById('activeRoomName');
    const activeRoomMeta = document.getElementById('activeRoomMeta');
    const messageList = document.getElementById('messageList');
    const chatStatus = document.getElementById('chatStatus');
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.getElementById('messageInput');
    const sendMessageBtn = document.getElementById('sendMessageBtn');
    const dropdownBtn = document.getElementById('profileDropdownBtn');
    const profileMenu = document.getElementById('profileMenu');

    function resetEmptyState() {
        activeRoomName.textContent = 'No active conversation';
        activeRoomMeta.textContent = 'Search for a user or create a group to start chatting';
        chatStatus.textContent = 'No conversation selected yet.';
        messageList.innerHTML = `
            <div class="flex h-full min-h-[360px] items-center justify-center">
                <div class="max-w-sm text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-2xl text-gray-400">&#128172;</div>
                    <h3 class="text-lg font-black text-gray-800">No conversations yet</h3>
                    <p class="mt-2 text-sm text-gray-400">Search for a user or create a group once the chat room setup is ready.</p>
                </div>
            </div>
        `;
        messageInput.disabled = true;
        sendMessageBtn.disabled = true;
    }

    function roleLabel(role) {
        return String(role || '')
            .replaceAll('_', ' ')
            .replace(/\b\w/g, (char) => char.toUpperCase());
    }

    function makeRoomKey(otherUserId) {
        return [String(currentUser.id), String(otherUserId)].sort().join('_');
    }

    function renderRooms(filter = '') {
        const keyword = filter.trim().toLowerCase();
        const filteredRooms = rooms.filter((room) =>
            room.name.toLowerCase().includes(keyword) || room.subtitle.toLowerCase().includes(keyword)
        );

        if (!filteredRooms.length) {
            roomList.innerHTML = `
                <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-6 text-center">
                    <div class="mb-2 text-xl text-gray-300">&#128269;</div>
                    <h3 class="text-sm font-bold text-gray-700">No conversations found</h3>
                    <p class="mt-1 text-xs text-gray-400">Start by creating a room or adding users once the room manager is available.</p>
                </div>
            `;
            return;
        }

        roomList.innerHTML = filteredRooms.map((room) => `
            <button
                type="button"
                data-room-id="${room.id}"
                class="chat-room-btn w-full rounded-2xl p-3 text-left transition ${room.id === activeRoomId ? 'bg-green-500 text-white' : 'hover:bg-gray-50'}"
            >
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full ${room.id === activeRoomId ? 'bg-white/20 text-white' : room.color + ' text-white'} text-[10px] font-black">
                        ${room.initials}
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-black ${room.id === activeRoomId ? 'text-white' : 'text-gray-800'}">${room.name}</div>
                        <div class="text-[11px] ${room.id === activeRoomId ? 'text-white/80' : 'text-gray-400'}">${room.subtitle}</div>
                    </div>
                </div>
            </button>
        `).join('');

        document.querySelectorAll('.chat-room-btn').forEach((button) => {
            button.addEventListener('click', () => {
                activeRoomId = button.dataset.roomId;
                renderRooms(roomSearch.value);
                subscribeToMessages();
            });
        });
    }

    function renderUserSearchResults(users) {
        if (!users.length) {
            roomList.innerHTML = `
                <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-6 text-center">
                    <div class="mb-2 text-xl text-gray-300">&#128269;</div>
                    <h3 class="text-sm font-bold text-gray-700">No registered users found</h3>
                    <p class="mt-1 text-xs text-gray-400">Try another name, email, or barangay.</p>
                </div>
            `;
            return;
        }

        roomList.innerHTML = users.map((user) => {
            const initials = user.name
                .split(' ')
                .map((part) => part[0] || '')
                .join('')
                .slice(0, 2)
                .toUpperCase();

            return `
                <button
                    type="button"
                    data-user-id="${user.id}"
                    data-user-name="${user.name}"
                    data-user-role="${user.role}"
                    class="user-search-btn w-full rounded-2xl border border-gray-100 p-3 text-left transition hover:bg-gray-50"
                >
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-500 text-[10px] font-black text-white">
                            ${initials}
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-black text-gray-800">${user.name}</div>
                            <div class="text-[11px] text-gray-400">${roleLabel(user.role)}${user.barangay ? ' • ' + user.barangay : ''}</div>
                            <div class="text-[11px] text-gray-400">${user.email}</div>
                        </div>
                    </div>
                </button>
            `;
        }).join('');

        document.querySelectorAll('.user-search-btn').forEach((button) => {
            button.addEventListener('click', async () => {
                await openDirectConversation({
                    id: button.dataset.userId,
                    name: button.dataset.userName,
                    role: button.dataset.userRole
                });
            });
        });
    }

    function updateActiveRoomHeader() {
        const room = rooms.find((item) => item.id === activeRoomId);
        if (!room) {
            resetEmptyState();
            return;
        }
        activeRoomName.textContent = room.name;
        activeRoomMeta.textContent = room.subtitle;
        messageInput.disabled = false;
        sendMessageBtn.disabled = false;
    }

    async function searchRegisteredUsers(keyword) {
        try {
            const response = await fetch(`{{ route('sk_secretary.chat.users') }}?search=${encodeURIComponent(keyword)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Unable to search users.');
            }

            const users = await response.json();
            renderUserSearchResults(users);
        } catch (error) {
            roomList.innerHTML = `<div class="rounded-2xl border border-red-100 bg-red-50 p-4 text-sm text-red-600">${error.message}</div>`;
        }
    }

    async function ensureDirectRoom(otherUser) {
        const roomKey = makeRoomKey(otherUser.id);
        const existingRoomQuery = query(
            collection(db, 'chat_rooms'),
            where('roomKey', '==', roomKey),
            limit(1)
        );

        const existingRoomSnapshot = await getDocs(existingRoomQuery);

        if (!existingRoomSnapshot.empty) {
            const doc = existingRoomSnapshot.docs[0];
            return {
                id: doc.id,
                ...doc.data()
            };
        }

        await addDoc(collection(db, 'chat_rooms'), {
            name: otherUser.name,
            type: 'direct',
            createdBy: String(currentUser.id),
            createdAt: serverTimestamp(),
            memberIds: [String(currentUser.id), String(otherUser.id)],
            memberNames: [currentUser.name, otherUser.name],
            roomKey
        });

        const createdRoomSnapshot = await getDocs(existingRoomQuery);

        if (createdRoomSnapshot.empty) {
            throw new Error('Unable to create conversation room.');
        }

        const createdDoc = createdRoomSnapshot.docs[0];

        return {
            id: createdDoc.id,
            ...createdDoc.data()
        };
    }

    async function openDirectConversation(otherUser) {
        chatStatus.textContent = 'Opening conversation...';

        try {
            const room = await ensureDirectRoom(otherUser);
            const roomEntry = {
                id: room.id,
                name: otherUser.name,
                subtitle: 'Direct message',
                color: 'bg-green-500',
                initials: otherUser.name
                    .split(' ')
                    .map((part) => part[0] || '')
                    .join('')
                    .slice(0, 2)
                    .toUpperCase()
            };

            const existingIndex = rooms.findIndex((item) => item.id === room.id);

            if (existingIndex === -1) {
                rooms.unshift(roomEntry);
            } else {
                rooms[existingIndex] = roomEntry;
            }

            activeRoomId = room.id;
            roomSearch.value = '';
            renderRooms();
            subscribeToMessages();
        } catch (error) {
            chatStatus.textContent = 'Unable to open conversation.';
            console.error(error);
        }
    }

    async function loadRooms() {
        chatStatus.textContent = 'Loading rooms...';

        try {
            const roomsQuery = query(
                collection(db, 'chat_rooms'),
                where('memberIds', 'array-contains', String(currentUser.id)),
                limit(50)
            );

            const snapshot = await getDocs(roomsQuery);

            rooms = snapshot.docs.map((doc) => {
                const data = doc.data();
                const memberCount = Array.isArray(data.memberIds) ? data.memberIds.length : 0;
                const isDirect = data.type === 'direct';
                const directPartnerName = Array.isArray(data.memberNames)
                    ? data.memberNames.find((name) => name !== currentUser.name)
                    : null;

                return {
                    id: doc.id,
                    name: isDirect ? (directPartnerName || data.name || 'Direct Chat') : (data.name || 'Unnamed Room'),
                    subtitle: isDirect ? 'Direct message' : `${memberCount} members`,
                    color: 'bg-green-500',
                    initials: (isDirect ? (directPartnerName || data.name || 'Room') : (data.name || 'Room'))
                        .split(' ')
                        .map((part) => part[0] || '')
                        .join('')
                        .slice(0, 3)
                        .toUpperCase(),
                    createdAtSeconds: data.createdAt?.seconds || 0
                };
            }).sort((a, b) => a.createdAtSeconds - b.createdAtSeconds);

            renderRooms();

            if (rooms.length > 0) {
                activeRoomId = rooms[0].id;
                subscribeToMessages();
            } else {
                resetEmptyState();
            }
        } catch (error) {
            chatStatus.textContent = 'Unable to load rooms. Check Firestore rules.';
            roomList.innerHTML = `<div class="rounded-2xl border border-red-100 bg-red-50 p-4 text-sm text-red-600">${error.message}</div>`;
            resetEmptyState();
        }
    }

    function formatMessageTime(timestamp) {
        if (!timestamp?.seconds) {
            return 'sending...';
        }

        return new Date(timestamp.seconds * 1000).toLocaleTimeString([], {
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function renderMessages(messages) {
        if (!messages.length) {
            messageList.innerHTML = '<div class="rounded-2xl bg-gray-50 p-4 text-sm text-gray-400">No messages yet in this room.</div>';
            return;
        }

        messageList.innerHTML = messages.map((message) => {
            const isOwn = String(message.senderId || '') === String(currentUser.id) || (!message.senderId && message.senderName === currentUser.name);
            return `
                <div class="flex ${isOwn ? 'justify-end' : 'justify-start'}">
                    <div class="max-w-[75%]">
                        <div class="mb-1 text-[11px] text-gray-400 ${isOwn ? 'text-right' : ''}">
                            ${message.senderName} ${message.senderRole ? '• ' + message.senderRole : ''}
                        </div>
                        <div class="rounded-2xl px-4 py-3 text-sm shadow-sm ${isOwn ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-800'}">
                            ${message.text}
                        </div>
                        <div class="mt-1 text-[10px] text-gray-400 ${isOwn ? 'text-right' : ''}">
                            ${formatMessageTime(message.createdAt)}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        messageList.scrollTop = messageList.scrollHeight;
    }

    function subscribeToMessages() {
        if (!activeRoomId) {
            if (unsubscribeMessages) {
                unsubscribeMessages();
                unsubscribeMessages = null;
            }
            resetEmptyState();
            return;
        }

        updateActiveRoomHeader();
        chatStatus.textContent = 'Loading messages...';

        if (unsubscribeMessages) {
            unsubscribeMessages();
        }

        const messagesQuery = query(
            collection(db, 'chat_messages'),
            where('roomId', '==', activeRoomId),
            orderBy('createdAt', 'asc'),
            limit(100)
        );

        unsubscribeMessages = onSnapshot(messagesQuery, (snapshot) => {
            const messages = snapshot.docs.map((doc) => ({
                id: doc.id,
                ...doc.data()
            }));

            chatStatus.textContent = 'Connected to Firebase chat.';
            renderMessages(messages);
        }, (error) => {
            chatStatus.textContent = 'Chat connection failed. Check Firestore setup and rules.';
            messageList.innerHTML = `<div class="rounded-2xl border border-red-100 bg-red-50 p-4 text-sm text-red-600">${error.message}</div>`;
        });
    }

    messageForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!activeRoomId) {
            return;
        }

        const text = messageInput.value.trim();

        if (!text) {
            return;
        }

        try {
            await addDoc(collection(db, 'chat_messages'), {
                roomId: activeRoomId,
                text,
                senderId: String(currentUser.id),
                senderName: currentUser.name,
                senderRole: currentUser.role,
                createdAt: serverTimestamp()
            });

            messageInput.value = '';
        } catch (error) {
            chatStatus.textContent = 'Unable to send message. Check Firestore permissions.';
            console.error(error);
        }
    });

    roomSearch.addEventListener('input', () => {
        const keyword = roomSearch.value.trim();

        clearTimeout(searchDebounce);

        if (keyword === '') {
            renderRooms();
            return;
        }

        searchDebounce = setTimeout(() => {
            searchRegisteredUsers(keyword);
        }, 250);
    });

    if (dropdownBtn && profileMenu) {
        dropdownBtn.addEventListener('click', (event) => {
            event.stopPropagation();
            profileMenu.classList.toggle('hidden');
        });

        window.addEventListener('click', (event) => {
            if (!profileMenu.contains(event.target) && !dropdownBtn.contains(event.target)) {
                profileMenu.classList.add('hidden');
            }
        });
    }

    loadRooms();
</script>
@endpush

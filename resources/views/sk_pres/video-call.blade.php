{{-- File guide: Blade view template for resources/views/sk_pres/video-call.blade.php. --}}
@extends('layouts.app')

@section('title', 'Meeting Call')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
    <style>
        html, body { height: 100%; margin: 0; overflow: hidden; background: #111111; }
        .video-pane { background: #0f172a; border: 1px solid rgba(255, 255, 255, 0.06); }
    </style>
@endsection

@section('content')
<div class="relative h-screen w-screen overflow-hidden bg-[#111111] text-white">
    <div class="absolute left-4 top-4 z-30 flex items-center gap-3 rounded-2xl bg-slate-900/85 px-4 py-3 shadow-lg backdrop-blur">
        <a href="{{ $backRoute ?? route('sk_pres.meetings') }}" class="rounded-lg bg-white/10 px-3 py-2 text-sm font-medium hover:bg-white/20">Back</a>
        <div>
            <p class="text-sm font-semibold">{{ $meeting->title }}</p>
            <p class="text-xs text-slate-300">{{ $meeting->display_datetime }}</p>
        </div>
    </div>

    <div class="absolute right-4 top-4 z-30 max-w-sm rounded-2xl bg-slate-900/85 px-4 py-3 shadow-lg backdrop-blur">
        <p class="text-sm font-semibold">Meeting Agenda</p>
        <p class="mt-1 text-xs text-slate-300">{{ $meeting->agenda ?: 'No agenda provided.' }}</p>
    </div>

    <div id="statusBar" class="absolute inset-x-0 top-24 z-30 mx-auto hidden w-full max-w-2xl rounded-2xl border border-red-500/40 bg-red-500/15 px-5 py-4 text-sm text-red-100 shadow-lg">
        <span id="statusText"></span>
    </div>

    <div class="grid h-full w-full grid-cols-1 gap-3 p-6 pt-28 lg:grid-cols-[1fr_320px]">
        <div class="grid min-h-0 grid-rows-[1fr_152px_auto] gap-3">
            <div id="remote-grid" class="grid min-h-0 grid-cols-1 gap-3 md:grid-cols-2">
                <div id="remote-empty" class="video-pane flex min-h-[320px] items-center justify-center rounded-[24px] text-sm text-slate-400">
                    Waiting for other participants to join...
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <div id="local-player" class="video-pane flex h-[140px] items-center justify-center rounded-[20px] text-sm text-slate-400">
                    Joining local preview...
                </div>
            </div>

            <div class="video-pane flex flex-wrap items-center justify-between gap-3 rounded-[20px] px-4 py-3">
                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-300">
                    <span class="rounded-xl bg-white/10 px-3 py-2">Channel: {{ $channelName }}</span>
                    <span id="connectionStatus" class="rounded-xl bg-green-500/20 px-3 py-2 text-green-300">Ready</span>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button id="toggleMicBtn" type="button" class="rounded-xl bg-white/10 px-4 py-2 text-xs font-semibold hover:bg-white/20">Mute Mic</button>
                    <button id="toggleCamBtn" type="button" class="rounded-xl bg-white/10 px-4 py-2 text-xs font-semibold hover:bg-white/20">Turn Off Camera</button>
                    <button id="leaveBtn" type="button" class="rounded-xl bg-[#ef4444] px-4 py-2 text-xs font-semibold text-white hover:bg-[#dc2626]">Leave Meeting</button>
                </div>
            </div>
        </div>

        <aside class="video-pane rounded-[24px] p-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold">Participants</h3>
                <span id="participantCount" class="text-xs text-slate-400">1 online</span>
            </div>
            <div id="participantList" class="mt-4 space-y-3">
                <div class="rounded-2xl border border-white/10 px-3 py-3">
                    <p class="text-sm font-semibold">{{ $fullName }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">You</p>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const tokenUrl = @json($tokenRoute ?? route('sk_pres.meetings.agora.token', $meeting->meeting_id));
    const meetingChannel = @json($channelName);
    const statusBar = document.getElementById('statusBar');
    const statusText = document.getElementById('statusText');
    const connectionStatus = document.getElementById('connectionStatus');
    const remoteGrid = document.getElementById('remote-grid');
    const remoteEmpty = document.getElementById('remote-empty');
    const participantList = document.getElementById('participantList');
    const participantCount = document.getElementById('participantCount');
    const toggleMicBtn = document.getElementById('toggleMicBtn');
    const toggleCamBtn = document.getElementById('toggleCamBtn');
    const leaveBtn = document.getElementById('leaveBtn');

    let client;
    let localTracks = [];
    let remoteUsers = new Map();

    const showStatus = (message) => {
        statusText.textContent = message;
        statusBar.classList.remove('hidden');
        setTimeout(() => statusBar.classList.add('hidden'), 5000);
    };

    const setConnectionStatus = (message, classes) => {
        connectionStatus.textContent = message;
        connectionStatus.className = `rounded-xl px-3 py-2 text-xs ${classes}`;
    };

    const refreshParticipantCount = () => {
        participantCount.textContent = `${remoteUsers.size + 1} online`;
    };

    const ensureRemoteCard = (user) => {
        const id = `remote-${user.uid}`;
        let player = document.getElementById(id);

        if (!player) {
            player = document.createElement('div');
            player.id = id;
            // Highlight screenshares (UIDs over 1000)
            const isScreen = user.uid >= 1000;
            player.className = `video-pane h-full min-h-[320px] rounded-[24px] overflow-hidden ${isScreen ? 'md:col-span-2' : ''}`;
            remoteGrid.appendChild(player);
        }

        remoteEmpty.classList.add('hidden');
        return player;
    };

    const syncParticipantList = () => {
        participantList.innerHTML = `
            <div class="rounded-2xl border border-white/10 px-3 py-3">
                <p class="text-sm font-semibold">{{ $fullName }}</p>
                <p class="mt-1 text-[11px] text-slate-400">You</p>
            </div>
        `;

        remoteUsers.forEach((user) => {
            const entry = document.createElement('div');
            entry.className = 'rounded-2xl border border-white/10 px-3 py-3';
            const isScreen = user.uid >= 1000;
            entry.innerHTML = `
                <p class="text-sm font-semibold">${isScreen ? 'Presentation' : 'User ' + user.uid}</p>
                <p class="mt-1 text-[11px] text-slate-400">Connected</p>
            `;
            participantList.appendChild(entry);
        });
        refreshParticipantCount();
    };

    const joinMeeting = async () => {
        try {
            setConnectionStatus('Fetching token', 'bg-yellow-500/20 text-yellow-300');

            const tokenResponse = await fetch(tokenUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ channel: meetingChannel }),
            });

            const tokenPayload = await tokenResponse.json();
            if (!tokenResponse.ok) throw new Error(tokenPayload.message || 'Failed to fetch token.');
            client = AgoraRTC.createClient({ mode: 'rtc', codec: 'vp8' });

            client.on('user-published', async (user, mediaType) => {
                remoteUsers.set(String(user.uid), user);
                syncParticipantList();
                await client.subscribe(user, mediaType);

                if (mediaType === 'video') {
                    const player = ensureRemoteCard(user);
                    user.videoTrack.play(player.id);
                }
                if (mediaType === 'audio') user.audioTrack.play();
            });

            client.on('user-unpublished', (user) => {
                remoteUsers.delete(String(user.uid));
                const player = document.getElementById(`remote-${user.uid}`);
                if (player) player.remove();
                if (remoteUsers.size === 0) remoteEmpty.classList.remove('hidden');
                syncParticipantList();
            });

            await client.join(tokenPayload.appId, tokenPayload.channel, tokenPayload.token, tokenPayload.uid);
            
            localTracks = await AgoraRTC.createMicrophoneAndCameraTracks();
            localTracks[1].play('local-player');
            await client.publish(localTracks);

            setConnectionStatus('Connected', 'bg-green-500/20 text-green-300');
            syncParticipantList();
        } catch (error) {
            console.error(error);
            setConnectionStatus('Join failed', 'bg-red-500/20 text-red-300');
            showStatus(`Call failed: ${error.message}`);
        }
    };
    // Existing Mic/Cam/Leave Handlers
    toggleMicBtn.addEventListener('click', async () => {
        if (!localTracks[0]) return;
        const shouldMute = localTracks[0].enabled;
        await localTracks[0].setEnabled(!shouldMute);
        toggleMicBtn.textContent = shouldMute ? 'Unmute Mic' : 'Mute Mic';
    });

    toggleCamBtn.addEventListener('click', async () => {
        if (!localTracks[1]) return;
        const shouldDisable = localTracks[1].enabled;
        await localTracks[1].setEnabled(!shouldDisable);
        toggleCamBtn.textContent = shouldDisable ? 'Turn On Camera' : 'Turn Off Camera';
    });

    leaveBtn.addEventListener('click', async () => {
        for (const track of localTracks) { track.stop(); track.close(); }
        if (client) await client.leave();
        window.location.href = @json($backRoute ?? route('sk_pres.meetings'));
    });

    joinMeeting();
</script>
@endpush


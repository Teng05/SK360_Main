{{-- File guide: Blade view template for resources/views/sk_pres/video-call.blade.php. --}}
@extends('layouts.app')
@section('title','SK 360° | Meeting Call')
@section('page_css')
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
<style>
html,body{height:100%;margin:0;overflow:hidden;background:#111827}
.meeting-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));grid-auto-rows:minmax(220px,1fr);align-content:center}
.participant-tile{background:#172033;border:1px solid rgba(255,255,255,.08);transition:.2s ease}
.participant-tile.active-speaker{border-color:#4ade80;box-shadow:0 0 0 3px rgba(74,222,128,.3)}
.participant-video>div,.participant-video video{width:100%!important;height:100%!important;object-fit:cover!important}
</style>
@endsection
@section('content')
<div class="relative h-screen w-screen overflow-hidden bg-[#111827] text-white">
    <div class="absolute inset-x-0 top-0 z-30 flex items-center justify-between gap-4 bg-[#111827]/90 px-5 py-4 backdrop-blur">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ $backRoute ?? route('sk_pres.meetings') }}" class="rounded-xl bg-white/10 px-3 py-2 text-xs font-semibold hover:bg-white/20">Back</a>
            <div class="min-w-0">
                <p class="truncate text-sm font-bold">{{ $meeting->title }}</p>
                <p class="truncate text-[11px] text-slate-400">{{ $meeting->display_datetime }}</p>
            </div>
        </div>
        <div class="hidden max-w-md text-right md:block">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Agenda</p>
            <p class="truncate text-xs text-slate-300">{{ $meeting->agenda ?: 'No agenda provided.' }}</p>
        </div>
    </div>
    <div id="statusBar" class="absolute inset-x-0 top-20 z-50 mx-auto hidden w-[calc(100%-2rem)] max-w-2xl rounded-2xl border border-red-400/30 bg-red-500/20 px-5 py-4 text-sm text-red-100 shadow-xl backdrop-blur">
        <span id="statusText"></span>
    </div>
    <div id="meetingEndedOverlay" class="absolute inset-0 z-[100] hidden items-center justify-center bg-black/80 px-6 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-3xl border border-white/10 bg-[#1b2434] p-8 text-center shadow-2xl">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/20 text-3xl">☎</div>
            <h2 class="mt-5 text-2xl font-bold">Meeting ended</h2>
            <p id="meetingEndedText" class="mt-2 text-sm text-slate-300">The meeting was ended by the SK President.</p>
            <p class="mt-4 text-xs text-slate-500">Returning to Meetings...</p>
        </div>
    </div>
    <div class="grid h-full min-h-0 grid-cols-1 gap-3 px-4 pb-24 pt-20 lg:grid-cols-[minmax(0,1fr)_290px]">
        <main class="min-h-0 overflow-hidden rounded-[26px] bg-[#0d1422] p-3">
            <div id="participantGrid" class="meeting-grid h-full min-h-0 gap-3 overflow-y-auto p-1">
                <div id="localTile" class="participant-tile relative min-h-[220px] overflow-hidden rounded-[24px]" data-local="1">
                    <div id="localVideo" class="participant-video absolute inset-0 hidden"></div>
                    <div id="localAvatar" class="absolute inset-0 flex items-center justify-center">
                        <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full bg-red-500 text-3xl font-black shadow-xl">
                            <img id="localAvatarImage" src="" alt="" class="hidden h-full w-full object-cover">
                            <span id="localInitials">{{ collect(explode(' ',trim($fullName)))->filter()->map(fn($part)=>strtoupper(substr($part,0,1)))->take(2)->implode('') ?: 'SK' }}</span>
                        </div>
                    </div>
                    <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 bg-gradient-to-t from-black/80 to-transparent px-4 pb-4 pt-10">
                        <div class="min-w-0">
                            <p id="localName" class="truncate text-sm font-semibold">{{ $fullName }} <span class="text-xs font-normal text-slate-300">(You)</span></p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <span id="localMicBadge" class="rounded-full bg-white/10 px-2 py-1 text-[10px] font-semibold text-slate-300">Mic off</span>
                            <span id="localCamBadge" class="rounded-full bg-white/10 px-2 py-1 text-[10px] font-semibold text-slate-300">Cam off</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <aside class="hidden min-h-0 rounded-[26px] border border-white/10 bg-[#161f2f] p-4 lg:flex lg:flex-col">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold">Participants</h3>
                <span id="participantCount" class="text-xs text-slate-400">1 online</span>
            </div>
            <div id="participantList" class="mt-4 min-h-0 flex-1 space-y-2 overflow-y-auto"></div>
            <div class="mt-4 rounded-2xl bg-white/5 px-3 py-3 text-[11px] text-slate-400">
                Camera-off participants stay visible using their profile photo or initials.
            </div>
        </aside>
    </div>
    <div class="absolute inset-x-0 bottom-0 z-40 flex items-center justify-center px-4 pb-4">
        <div class="flex flex-wrap items-center justify-center gap-2 rounded-2xl border border-white/10 bg-[#1b2434]/95 px-3 py-3 shadow-2xl backdrop-blur">
            <span id="connectionStatus" class="rounded-xl bg-yellow-500/15 px-3 py-2 text-xs font-semibold text-yellow-200">Connecting...</span>
            <button id="toggleMicBtn" type="button" class="rounded-xl bg-white/10 px-4 py-2 text-xs font-semibold hover:bg-white/20">Turn On Mic</button>
            <button id="toggleCamBtn" type="button" class="rounded-xl bg-white/10 px-4 py-2 text-xs font-semibold hover:bg-white/20">Turn On Camera</button>
            <button id="leaveBtn" type="button" class="rounded-xl bg-white/10 px-4 py-2 text-xs font-semibold hover:bg-white/20">Leave</button>
            @if($canEndMeeting ?? false)
                <button id="endMeetingBtn" type="button" class="rounded-xl bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">End for Everyone</button>
            @endif
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
const tokenUrl=@json($tokenRoute ?? route('sk_pres.meetings.agora.token',$meeting->meeting_id));
const joinAttendanceUrl=@json($joinAttendanceRoute ?? route('meetings.call-attendance.join',$meeting->meeting_id));
const leaveAttendanceUrl=@json($leaveAttendanceRoute ?? route('meetings.call-attendance.leave',$meeting->meeting_id));
const endMeetingUrl=@json($endMeetingRoute ?? null);
const meetingStatusUrl=@json(auth()->check() ? route('notifications.feed',['meeting_id'=>$meeting->meeting_id,'only_status'=>1]) : null);
const backUrl=@json($backRoute ?? route('sk_pres.meetings'));
const meetingChannel=@json($channelName);
const participantNames=@json($participantNames ?? []);
const csrfToken=@json(csrf_token());
const statusBar=document.getElementById('statusBar');
const statusText=document.getElementById('statusText');
const connectionStatus=document.getElementById('connectionStatus');
const participantGrid=document.getElementById('participantGrid');
const participantList=document.getElementById('participantList');
const participantCount=document.getElementById('participantCount');
const localTile=document.getElementById('localTile');
const localVideo=document.getElementById('localVideo');
const localAvatar=document.getElementById('localAvatar');
const localAvatarImage=document.getElementById('localAvatarImage');
const localInitials=document.getElementById('localInitials');
const localName=document.getElementById('localName');
const localMicBadge=document.getElementById('localMicBadge');
const localCamBadge=document.getElementById('localCamBadge');
const toggleMicBtn=document.getElementById('toggleMicBtn');
const toggleCamBtn=document.getElementById('toggleCamBtn');
const leaveBtn=document.getElementById('leaveBtn');
const endMeetingBtn=document.getElementById('endMeetingBtn');
const meetingEndedOverlay=document.getElementById('meetingEndedOverlay');
const meetingEndedText=document.getElementById('meetingEndedText');
let client=null;
let localMicTrack=null;
let localCameraTrack=null;
let localUid=null;
let joinedCall=false;
let leavingCall=false;
let meetingEnded=false;
let statusTimer=null;
let participantProfiles={};
const remoteUsers=new Map();
const showStatus=(message)=>{
    statusText.textContent=message;
    statusBar.classList.remove('hidden');
    clearTimeout(showStatus.timer);
    showStatus.timer=setTimeout(
        ()=>statusBar.classList.add('hidden'),
        5000
    );
};
const setConnectionStatus=(message,classes)=>{
    connectionStatus.textContent=message;
    connectionStatus.className=`rounded-xl px-3 py-2 text-xs font-semibold ${classes}`;
};
const initialsFor=(name)=>{
    const parts=String(name || 'User')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0,2);
    return parts
        .map(
            (part)=>
                part.charAt(0).toUpperCase()
        )
        .join('')
        || 'SK';
};
const profileFor=(uid)=>{
    const key=String(uid);
    const profile=
        participantProfiles[key]
        || {};
    const name=
        profile.name
        ||
        participantNames[key]
        ||
        `User ${key}`;
    return {
        name,
        initials:
            profile.initials
            ||
            initialsFor(name),
        profile_pic:
            profile.profile_pic
            ||
            null
    };
};
const applyAvatar=(imageEl,initialsEl,profile)=>{
    initialsEl.textContent=
        profile.initials
        ||
        initialsFor(profile.name);
    if(profile.profile_pic){
        imageEl.src=profile.profile_pic;
        imageEl.alt=
            profile.name
            ||
            'Participant';
        imageEl.classList.remove('hidden');
        initialsEl.classList.add('hidden');
    }else{
        imageEl.removeAttribute('src');
        imageEl.classList.add('hidden');
        initialsEl.classList.remove('hidden');
    }
};
const updateLocalIdentity=()=>{
    if(localUid===null){
        return;
    }
    const profile=profileFor(localUid);
    localName.innerHTML='';
    localName.append(
        document.createTextNode(
            profile.name+' '
        )
    );
    const you=document.createElement('span');
    you.className=
        'text-xs font-normal text-slate-300';
    you.textContent='(You)';
    localName.appendChild(you);
    applyAvatar(
        localAvatarImage,
        localInitials,
        profile
    );
};
const remoteTileId=(uid)=>
    `participant-${String(uid).replace(/[^a-zA-Z0-9_-]/g,'')}`;
const ensureRemoteTile=(user)=>{
    const uid=String(user.uid);
    let tile=
        document.getElementById(
            remoteTileId(uid)
        );
    if(tile){
        updateRemoteTileState(user);
        return tile;
    }
    const profile=profileFor(uid);
    tile=document.createElement('div');
    tile.id=remoteTileId(uid);
    tile.dataset.uid=uid;
    tile.className=
        'participant-tile relative min-h-[220px] overflow-hidden rounded-[24px]';
    const video=document.createElement('div');
    video.id=`video-${uid}`;
    video.className=
        'participant-video absolute inset-0 hidden';
    video.dataset.videoContainer='1';
    const avatar=document.createElement('div');
    avatar.className=
        'absolute inset-0 flex items-center justify-center';
    avatar.dataset.avatar='1';
    const avatarCircle=
        document.createElement('div');
    avatarCircle.className=
        'flex h-24 w-24 items-center justify-center overflow-hidden rounded-full bg-slate-600 text-3xl font-black shadow-xl';
    const image=document.createElement('img');
    image.className=
        'hidden h-full w-full object-cover';
    image.dataset.avatarImage='1';
    const initials=document.createElement('span');
    initials.dataset.initials='1';
    avatarCircle.append(
        image,
        initials
    );
    avatar.appendChild(
        avatarCircle
    );
    const footer=
        document.createElement('div');
    footer.className=
        'absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 bg-gradient-to-t from-black/80 to-transparent px-4 pb-4 pt-10';
    const nameWrap=
        document.createElement('div');
    nameWrap.className='min-w-0';
    const name=document.createElement('p');
    name.className=
        'truncate text-sm font-semibold';
    name.dataset.participantName='1';
    nameWrap.appendChild(name);
    const badges=
        document.createElement('div');
    badges.className=
        'flex shrink-0 gap-2';
    const mic=document.createElement('span');
    mic.className=
        'rounded-full bg-white/10 px-2 py-1 text-[10px] font-semibold text-slate-300';
    mic.dataset.micBadge='1';
    const cam=document.createElement('span');
    cam.className=
        'rounded-full bg-white/10 px-2 py-1 text-[10px] font-semibold text-slate-300';
    cam.dataset.camBadge='1';
    badges.append(
        mic,
        cam
    );
    footer.append(
        nameWrap,
        badges
    );
    tile.append(
        video,
        avatar,
        footer
    );
    participantGrid.appendChild(
        tile
    );
    applyAvatar(
        image,
        initials,
        profile
    );
    name.textContent=profile.name;
    updateRemoteTileState(user);
    refreshParticipantList();
    return tile;
};
const updateRemoteTileIdentity=(uid)=>{
    const tile=
        document.getElementById(
            remoteTileId(uid)
        );
    if(!tile){
        return;
    }
    const profile=
        profileFor(uid);
    const name=
        tile.querySelector(
            '[data-participant-name]'
        );
    const image=
        tile.querySelector(
            '[data-avatar-image]'
        );
    const initials=
        tile.querySelector(
            '[data-initials]'
        );
    if(name){
        name.textContent=
            profile.name;
    }
    if(image && initials){
        applyAvatar(
            image,
            initials,
            profile
        );
    }
};
const setRemoteVideoVisible=(uid,visible)=>{
    const tile=
        document.getElementById(
            remoteTileId(uid)
        );
    if(!tile){
        return;
    }
    tile.querySelector(
        '[data-video-container]'
    )?.classList.toggle(
        'hidden',
        !visible
    );
    tile.querySelector(
        '[data-avatar]'
    )?.classList.toggle(
        'hidden',
        visible
    );
};
const updateRemoteTileState=(user)=>{
    const tile=
        document.getElementById(
            remoteTileId(user.uid)
        );
    if(!tile){
        return;
    }
    const mic=
        tile.querySelector(
            '[data-mic-badge]'
        );
    const cam=
        tile.querySelector(
            '[data-cam-badge]'
        );
    if(mic){
        mic.textContent=
            user.hasAudio
                ? 'Mic on'
                : 'Mic off';
        mic.className=
            `rounded-full px-2 py-1 text-[10px] font-semibold ${
                user.hasAudio
                    ? 'bg-green-500/20 text-green-200'
                    : 'bg-white/10 text-slate-300'
            }`;
    }
    if(cam){
        cam.textContent=
            user.hasVideo
                ? 'Cam on'
                : 'Cam off';
        cam.className=
            `rounded-full px-2 py-1 text-[10px] font-semibold ${
                user.hasVideo
                    ? 'bg-green-500/20 text-green-200'
                    : 'bg-white/10 text-slate-300'
            }`;
    }
};
const removeRemoteTile=(uid)=>{
    document.getElementById(
        remoteTileId(uid)
    )?.remove();
    refreshParticipantList();
};
const updateLocalMediaState=()=>{
    const micOn=
        Boolean(
            localMicTrack?.enabled
        );
    const camOn=
        Boolean(
            localCameraTrack?.enabled
        );
    localMicBadge.textContent=
        micOn
            ? 'Mic on'
            : 'Mic off';
    localCamBadge.textContent=
        camOn
            ? 'Cam on'
            : 'Cam off';
    localMicBadge.className=
        `rounded-full px-2 py-1 text-[10px] font-semibold ${
            micOn
                ? 'bg-green-500/20 text-green-200'
                : 'bg-white/10 text-slate-300'
        }`;
    localCamBadge.className=
        `rounded-full px-2 py-1 text-[10px] font-semibold ${
            camOn
                ? 'bg-green-500/20 text-green-200'
                : 'bg-white/10 text-slate-300'
        }`;
    toggleMicBtn.textContent=
        micOn
            ? 'Mute Mic'
            : 'Turn On Mic';
    toggleCamBtn.textContent=
        camOn
            ? 'Turn Off Camera'
            : 'Turn On Camera';
    localVideo.classList.toggle(
        'hidden',
        !camOn
    );
    localAvatar.classList.toggle(
        'hidden',
        camOn
    );
};
const refreshParticipantList=()=>{
    participantList.innerHTML='';
    const localProfile=
        localUid!==null
            ? profileFor(localUid)
            : {
                name:@json($fullName),
                initials:initialsFor(
                    @json($fullName)
                ),
                profile_pic:null
            };
    const addEntry=(
        profile,
        label,
        micOn,
        camOn
    )=>{
        const row=
            document.createElement('div');
        row.className=
            'flex items-center gap-3 rounded-2xl border border-white/10 px-3 py-3';
        const avatar=
            document.createElement('div');
        avatar.className=
            'flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-600 text-xs font-bold';
        if(profile.profile_pic){
            const img=
                document.createElement('img');
            img.src=
                profile.profile_pic;
            img.alt=
                profile.name;
            img.className=
                'h-full w-full object-cover';
            avatar.appendChild(img);
        }else{
            avatar.textContent=
                profile.initials
                ||
                initialsFor(
                    profile.name
                );
        }
        const details=
            document.createElement('div');
        details.className=
            'min-w-0 flex-1';
        const name=
            document.createElement('p');
        name.className=
            'truncate text-sm font-semibold';
        name.textContent=
            profile.name;
        const meta=
            document.createElement('p');
        meta.className=
            'mt-1 text-[10px] text-slate-400';
        meta.textContent=
            `${label} • ${
                micOn
                    ? 'Mic on'
                    : 'Mic off'
            } • ${
                camOn
                    ? 'Cam on'
                    : 'Cam off'
            }`;
        details.append(
            name,
            meta
        );
        row.append(
            avatar,
            details
        );
        participantList.appendChild(
            row
        );
    };
    addEntry(
        localProfile,
        'You',
        Boolean(
            localMicTrack?.enabled
        ),
        Boolean(
            localCameraTrack?.enabled
        )
    );
    remoteUsers.forEach(
        (user)=>{
            addEntry(
                profileFor(
                    user.uid
                ),
                'Connected',
                Boolean(
                    user.hasAudio
                ),
                Boolean(
                    user.hasVideo
                )
            );
        }
    );
    participantCount.textContent=
        `${remoteUsers.size+1} online`;
};
const refreshIdentities=()=>{
    updateLocalIdentity();
    remoteUsers.forEach(
        (user)=>
            updateRemoteTileIdentity(
                user.uid
            )
    );
    refreshParticipantList();
};
const setActiveSpeaker=(uid,active)=>{
    const tile=
        localUid!==null
        &&
        String(uid)===
            String(localUid)
            ? localTile
            : document.getElementById(
                remoteTileId(uid)
            );
    tile?.classList.toggle(
        'active-speaker',
        active
    );
};
const clearActiveSpeakers=()=>{
    participantGrid
        .querySelectorAll(
            '.participant-tile'
        )
        .forEach(
            (tile)=>
                tile.classList.remove(
                    'active-speaker'
                )
        );
};
const recordCallJoin=async()=>{
    try{
        const response=
            await fetch(
                joinAttendanceUrl,
                {
                    method:'POST',
                    headers:{
                        'Content-Type':
                            'application/json',
                        'X-CSRF-TOKEN':
                            csrfToken,
                        'Accept':
                            'application/json'
                    },
                    body:
                        JSON.stringify({})
                }
            );
        const data=
            await response
                .json()
                .catch(()=>({}));
        if(!response.ok){
            showStatus(
                data.message
                ||
                'Connected to the call, but attendance could not be recorded.'
            );
        }
    }catch(error){
        console.error(error);
        showStatus(
            'Connected to the call, but attendance could not be recorded.'
        );
    }
};
const recordCallLeave=async()=>{
    if(!joinedCall){
        return;
    }
    try{
        await fetch(
            leaveAttendanceUrl,
            {
                method:'POST',
                headers:{
                    'Content-Type':
                        'application/json',
                    'X-CSRF-TOKEN':
                        csrfToken,
                    'Accept':
                        'application/json'
                },
                body:
                    JSON.stringify({}),
                keepalive:true
            }
        );
    }catch(error){
        console.error(error);
    }
};
const stopLocalMedia=()=>{
    [
        localMicTrack,
        localCameraTrack
    ].forEach(
        (track)=>{
            if(track){
                track.stop();
                track.close();
            }
        }
    );
    localMicTrack=null;
    localCameraTrack=null;
    updateLocalMediaState();
};
const shutdownCall=async(
    recordLeave=true
)=>{
    if(leavingCall){
        return;
    }
    leavingCall=true;
    if(statusTimer){
        clearInterval(
            statusTimer
        );
        statusTimer=null;
    }
    if(recordLeave){
        await recordCallLeave();
    }
    stopLocalMedia();
    if(client){
        try{
            await client.leave();
        }catch(error){
            console.error(error);
        }
    }
    joinedCall=false;
};
const handleMeetingEnded=async(
    message=
        'The meeting was ended by the SK President.'
)=>{
    if(meetingEnded){
        return;
    }
    meetingEnded=true;
    meetingEndedText.textContent=
        message;
    meetingEndedOverlay
        .classList
        .remove('hidden');
    meetingEndedOverlay
        .classList
        .add('flex');
    setConnectionStatus(
        'Meeting ended',
        'bg-red-500/20 text-red-200'
    );
    await shutdownCall(false);
    setTimeout(()=>{
        window.location.href=
            backUrl;
    },2200);
};
const refreshMeetingStatus=async()=>{
    if(
        !meetingStatusUrl
        ||
        meetingEnded
    ){
        return true;
    }
    try{
        const response=
            await fetch(
                meetingStatusUrl,
                {
                    headers:{
                        'X-Requested-With':
                            'XMLHttpRequest',
                        'Accept':
                            'application/json'
                    },
                    credentials:
                        'same-origin'
                }
            );
        if(response.status===403){
            return true;
        }
        if(!response.ok){
            return true;
        }
        const data=
            await response.json();
        participantProfiles=
            data.participants
            ||
            participantProfiles;
        refreshIdentities();
        if(
            data.status===
            'completed'
        ){
            await handleMeetingEnded(
                'The meeting was ended by the SK President.'
            );
            return false;
        }
        if(
            data.status===
            'cancelled'
        ){
            await handleMeetingEnded(
                'This meeting was cancelled.'
            );
            return false;
        }
    }catch(error){
        console.error(error);
    }
    return true;
};
const startMicrophone=async()=>{
    if(localMicTrack){
        if(
            !localMicTrack.enabled
        ){
            await localMicTrack
                .setEnabled(true);
        }
        updateLocalMediaState();
        refreshParticipantList();
        return;
    }
    try{
        localMicTrack=
            await AgoraRTC
                .createMicrophoneAudioTrack();
        await client.publish(
            localMicTrack
        );
    }catch(error){
        localMicTrack=null;
        showStatus(
            'Microphone is unavailable. You can stay in the meeting without it.'
        );
    }
    updateLocalMediaState();
    refreshParticipantList();
};
const startCamera=async()=>{
    if(localCameraTrack){
        if(
            !localCameraTrack.enabled
        ){
            await localCameraTrack
                .setEnabled(true);
        }
        updateLocalMediaState();
        refreshParticipantList();
        return;
    }
    try{
        localCameraTrack=
            await AgoraRTC
                .createCameraVideoTrack();
        localVideo
            .classList
            .remove('hidden');
        localAvatar
            .classList
            .add('hidden');
        localCameraTrack.play(
            'localVideo',
            {
                fit:'cover'
            }
        );
        await client.publish(
            localCameraTrack
        );
    }catch(error){
        localCameraTrack=null;
        showStatus(
            'Camera is unavailable. Your profile will remain visible instead.'
        );
    }
    updateLocalMediaState();
    refreshParticipantList();
};
const joinMeeting=async()=>{
    try{
        const active=
            await refreshMeetingStatus();
        if(!active){
            return;
        }
        setConnectionStatus(
            'Fetching token',
            'bg-yellow-500/20 text-yellow-200'
        );
        const tokenResponse=
            await fetch(
                tokenUrl,
                {
                    method:'POST',
                    headers:{
                        'Content-Type':
                            'application/json',
                        'X-CSRF-TOKEN':
                            csrfToken,
                        'Accept':
                            'application/json'
                    },
                    body:
                        JSON.stringify({
                            channel:
                                meetingChannel
                        })
                }
            );
        const tokenPayload=
            await tokenResponse
                .json()
                .catch(()=>({}));
        if(!tokenResponse.ok){
            throw new Error(
                tokenPayload.message
                ||
                'Failed to fetch token.'
            );
        }
        client=
            AgoraRTC.createClient({
                mode:'rtc',
                codec:'vp8'
            });
        client.on(
            'user-joined',
            (user)=>{
                remoteUsers.set(
                    String(user.uid),
                    user
                );
                ensureRemoteTile(
                    user
                );
                refreshParticipantList();
            }
        );
        client.on(
            'user-published',
            async(user,mediaType)=>{
                remoteUsers.set(
                    String(user.uid),
                    user
                );
                ensureRemoteTile(
                    user
                );
                try{
                    await client.subscribe(
                        user,
                        mediaType
                    );
                    if(
                        mediaType==='video'
                        &&
                        user.videoTrack
                    ){
                        setRemoteVideoVisible(
                            user.uid,
                            true
                        );
                        user.videoTrack.play(
                            `video-${user.uid}`,
                            {
                                fit:'cover'
                            }
                        );
                    }
                    if(
                        mediaType==='audio'
                        &&
                        user.audioTrack
                    ){
                        user.audioTrack.play();
                    }
                }catch(error){
                    console.error(error);
                }
                updateRemoteTileState(
                    user
                );
                refreshParticipantList();
            }
        );
        client.on(
            'user-unpublished',
            (user,mediaType)=>{
                remoteUsers.set(
                    String(user.uid),
                    user
                );
                ensureRemoteTile(
                    user
                );
                if(
                    mediaType==='video'
                ){
                    setRemoteVideoVisible(
                        user.uid,
                        false
                    );
                }
                updateRemoteTileState(
                    user
                );
                refreshParticipantList();
            }
        );
        client.on(
            'user-left',
            (user)=>{
                remoteUsers.delete(
                    String(user.uid)
                );
                removeRemoteTile(
                    user.uid
                );
            }
        );
        client.enableAudioVolumeIndicator();
        client.on(
            'volume-indicator',
            (volumes)=>{
                clearActiveSpeakers();
                volumes.forEach(
                    (volume)=>{
                        if(
                            volume.level>=5
                        ){
                            setActiveSpeaker(
                                volume.uid,
                                true
                            );
                        }
                    }
                );
            }
        );
        await client.join(
            tokenPayload.appId,
            tokenPayload.channel,
            tokenPayload.token,
            tokenPayload.uid
        );
        localUid=
            tokenPayload.uid;
        localTile.dataset.uid=
            String(localUid);
        joinedCall=true;
        client.remoteUsers
            .forEach(
                (user)=>{
                    remoteUsers.set(
                        String(user.uid),
                        user
                    );
                    ensureRemoteTile(
                        user
                    );
                }
            );
        await refreshMeetingStatus();
        updateLocalIdentity();
        refreshParticipantList();
        await recordCallJoin();
        await startMicrophone();
        await startCamera();
        setConnectionStatus(
            'Connected',
            'bg-green-500/20 text-green-200'
        );
        statusTimer=setInterval(
            refreshMeetingStatus,
            2500
        );
    }catch(error){
        console.error(error);
        setConnectionStatus(
            'Join failed',
            'bg-red-500/20 text-red-200'
        );
        showStatus(
            `Call failed: ${error.message}`
        );
    }
};
toggleMicBtn.addEventListener(
    'click',
    async()=>{
        if(
            !client
            ||
            !joinedCall
        ){
            return;
        }
        if(!localMicTrack){
            await startMicrophone();
            return;
        }
        const shouldDisable=
            localMicTrack.enabled;
        await localMicTrack
            .setEnabled(
                !shouldDisable
            );
        updateLocalMediaState();
        refreshParticipantList();
    }
);
toggleCamBtn.addEventListener(
    'click',
    async()=>{
        if(
            !client
            ||
            !joinedCall
        ){
            return;
        }
        if(!localCameraTrack){
            await startCamera();
            return;
        }
        const shouldDisable=
            localCameraTrack.enabled;
        await localCameraTrack
            .setEnabled(
                !shouldDisable
            );
        if(!shouldDisable){
            localCameraTrack.play(
                'localVideo',
                {
                    fit:'cover'
                }
            );
        }
        updateLocalMediaState();
        refreshParticipantList();
    }
);
leaveBtn.addEventListener(
    'click',
    async()=>{
        if(leavingCall){
            return;
        }
        leaveBtn.disabled=true;
        setConnectionStatus(
            'Leaving...',
            'bg-yellow-500/20 text-yellow-200'
        );
        await shutdownCall(true);
        window.location.href=
            backUrl;
    }
);
endMeetingBtn?.addEventListener(
    'click',
    async()=>{
        if(
            !endMeetingUrl
            ||
            meetingEnded
        ){
            return;
        }
        if(
            !confirm(
                'End this meeting for everyone? All participants will be disconnected and nobody will be able to rejoin.'
            )
        ){
            return;
        }
        endMeetingBtn.disabled=true;
        endMeetingBtn.textContent=
            'Ending...';
        try{
            const response=
                await fetch(
                    endMeetingUrl,
                    {
                        method:'POST',
                        headers:{
                            'X-CSRF-TOKEN':
                                csrfToken,
                            'X-Requested-With':
                                'XMLHttpRequest',
                            'Accept':
                                'application/json'
                        },
                        credentials:
                            'same-origin'
                    }
                );
            const data=
                await response
                    .json()
                    .catch(()=>({}));
            if(!response.ok){
                throw new Error(
                    data.message
                    ||
                    'Unable to end the meeting.'
                );
            }
            await handleMeetingEnded(
                data.message
                ||
                'Meeting ended for everyone.'
            );
        }catch(error){
            endMeetingBtn.disabled=false;
            endMeetingBtn.textContent=
                'End for Everyone';
            showStatus(
                error.message
            );
        }
    }
);
window.addEventListener(
    'pagehide',
    ()=>{
        if(
            !joinedCall
            ||
            meetingEnded
        ){
            return;
        }
        fetch(
            leaveAttendanceUrl,
            {
                method:'POST',
                headers:{
                    'Content-Type':
                        'application/json',
                    'X-CSRF-TOKEN':
                        csrfToken,
                    'Accept':
                        'application/json'
                },
                body:
                    JSON.stringify({}),
                keepalive:true
            }
        );
    }
);
updateLocalMediaState();
refreshParticipantList();
joinMeeting();
</script>
@endpush
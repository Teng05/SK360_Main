{{-- Shared wall feed: same working routes/data, main-branch presentation. --}}
@php
    $wallInitials = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: 'SK';
@endphp
<section class="sk-card p-6">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div><h2 class="sk-section-title">Activity Feed</h2><p class="sk-section-subtitle">Updates and accomplishments shared across SK councils.</p></div>
        <span class="sk-badge sk-badge--gray">@include('partials.ui.icon', ['icon' => 'users', 'iconSize' => 14]) Shared Wall</span>
    </div>
    @if (session('wall_status'))<div class="sk-alert sk-alert--success mb-4 !text-[13px]">@include('partials.ui.icon', ['icon' => 'circle-check', 'iconSize' => 17])<span>{{ session('wall_status') }}</span></div>@endif
    @if ($errors->has('post_content'))<div class="sk-alert sk-alert--error mb-4 !text-[13px]">@include('partials.ui.icon', ['icon' => 'circle-alert', 'iconSize' => 17])<span>{{ $errors->first('post_content') }}</span></div>@endif
    <form action="{{ route('wall.posts.store') }}" method="POST" class="mb-6 rounded-2xl border border-gray-200 bg-[#f8f9fb] p-4">@csrf
        <input type="hidden" name="post_category" value="{{ $defaultPostCategory ?? 'update' }}">
        <div class="flex items-start gap-3"><span class="sk-avatar">{{ $wallInitials($fullName ?? '') }}</span><textarea name="post_content" class="min-h-[88px] w-full resize-none rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm" rows="3" placeholder="Share updates with everyone..." required>{{ old('post_content') }}</textarea></div>
        <div class="flex justify-end mt-3"><button type="submit" class="sk-btn sk-btn--primary">@include('partials.ui.icon', ['icon' => 'send', 'iconSize' => 16]) Post</button></div>
    </form>
    <div class="space-y-4">
        @forelse ($feedPosts as $post)
            <article class="rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm">
                <div class="flex items-start justify-between gap-4 mb-3"><div class="flex items-center gap-3 min-w-0"><span class="sk-avatar">{{ $wallInitials($post->author_name) }}</span><div class="min-w-0"><h3 class="font-bold text-gray-900 leading-tight">{{ $post->author_name }}</h3><p class="text-xs font-semibold text-gray-500 mt-0.5">{{ $post->role_label }}{{ $post->barangay_name ? ' - Barangay '.$post->barangay_name : '' }}</p></div></div><div class="flex items-center gap-2"><span class="flex items-center gap-1 text-xs font-semibold text-gray-400 whitespace-nowrap">@include('partials.ui.icon', ['icon' => 'clock', 'iconSize' => 13]) {{ \Illuminate\Support\Carbon::parse($post->created_at)->diffForHumans() }}</span>@if((int) $post->user_id === (int) auth()->id())<details class="relative"><summary class="cursor-pointer list-none rounded-lg px-2 text-gray-500">⋮</summary><div class="absolute right-0 z-20 mt-1 w-32 rounded-xl border bg-white p-1 shadow-lg"><button type="button" class="w-full rounded px-2 py-1 text-left text-sm" onclick="editWallPost({{ $post->announcement_id }}, @js($post->content))">Edit</button><form method="POST" action="{{ route('wall.posts.destroy',$post->announcement_id) }}" onsubmit="return confirm('Delete this post?')">@csrf @method('DELETE')<button class="w-full rounded px-2 py-1 text-left text-sm text-red-600">Delete</button></form></div></details>@endif</div></div>
                <div class="mb-3"><span class="sk-badge sk-badge--red sk-badge--dot">{{ $post->title }}</span></div>
                <p class="text-[15px] leading-relaxed text-gray-700 whitespace-pre-line">{{ $post->content }}</p>
                <div class="mt-4 border-t border-gray-100 pt-3 flex items-center gap-5"><form action="{{ route('wall.posts.like', $post->announcement_id) }}" method="POST">@csrf<button type="submit" class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-bold text-gray-500">@include('partials.ui.icon', ['icon' => 'heart', 'iconSize' => 17]) {{ $post->likes_count }}</button></form><button type="button" class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-bold text-gray-500 hover:text-red-600" onclick='openWallComments(@json($post->announcement_id), @json($post->title), @json($post->comments))'>@include('partials.ui.icon', ['icon' => 'message-square', 'iconSize' => 17]) <span>{{ $post->comments_count }}</span> Comments</button></div>
            </article>
        @empty
            <div class="sk-empty"><span class="sk-icon-tile">@include('partials.ui.icon', ['icon' => 'message-square', 'iconSize' => 24])</span><p class="sk-empty__text">No posts yet. Start sharing updates.</p></div>
        @endforelse
    </div>
</section>
<div id="wallCommentModal" class="hidden fixed inset-0 z-[110] items-center justify-center bg-black/50 p-4">
    <div class="flex h-[min(720px,86vh)] w-full max-w-2xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">
        <div class="flex items-start justify-between border-b px-6 py-5"><div><p class="text-[10px] font-black uppercase tracking-widest text-red-600">Discussion</p><h3 id="wallCommentTitle" class="mt-1 text-xl font-black text-gray-900">Comments</h3><p class="mt-1 text-xs text-gray-400">Comments from public visitors and SK officials.</p></div><button type="button" onclick="closeWallComments()" class="h-9 w-9 rounded-full bg-gray-100 text-xl font-black text-gray-500">&times;</button></div>
        <div id="wallCommentList" class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-5"></div>
        <form id="wallCommentForm" method="POST" class="border-t bg-gray-50 p-5">@csrf<textarea name="comment" required maxlength="2000" rows="2" class="w-full resize-none rounded-xl border border-red-200 px-4 py-3 text-sm" placeholder="Write a comment..."></textarea><div class="mt-3 flex items-center justify-end"><button class="rounded-xl bg-red-600 px-5 py-2.5 text-sm font-bold text-white shadow">Send Comment</button></div></form>
    </div>
</div>
<script>
function openWallComments(id, title, comments) {
    document.getElementById('wallCommentTitle').textContent = title + ' Comments';
    document.getElementById('wallCommentList').innerHTML = comments.length ? comments.map(c => `<div class="flex gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50 font-black text-red-600">${escapeWallComment((c.author_name || 'O').trim().charAt(0).toUpperCase())}</span><div class="min-w-0 flex-1"><div class="rounded-2xl bg-gray-100 px-4 py-3"><p class="text-sm font-black text-gray-800">${escapeWallComment(c.author_name || 'Official')}</p><p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-gray-600">${escapeWallComment(c.comment)}</p></div><small class="ml-2 mt-1 block text-[10px] text-gray-400">${escapeWallComment(c.created_at)}</small></div></div>`).join('') : '<p class="py-16 text-center text-sm text-gray-400">No comments yet.</p>';
    document.getElementById('wallCommentForm').action = `{{ url('/wall/posts') }}/${id}/comments`;
    document.getElementById('wallCommentModal').classList.remove('hidden');
    document.getElementById('wallCommentModal').classList.add('flex');
}
function closeWallComments(){ const m=document.getElementById('wallCommentModal'); m.classList.add('hidden'); m.classList.remove('flex'); }
function escapeWallComment(value){ const d=document.createElement('div'); d.textContent=value || ''; return d.innerHTML; }
function editWallPost(id, currentContent) {
    const content = window.prompt('Edit post:', currentContent);
    if (content === null || !content.trim()) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `{{ url('/wall/posts') }}/${id}`;
    form.innerHTML = `@csrf<input type="hidden" name="_method" value="PUT"><input type="hidden" name="post_content">`;
    form.querySelector('[name="post_content"]').value = content;
    document.body.appendChild(form);
    form.submit();
}
</script>

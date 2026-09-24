{{-- File guide: Blade view template for resources/views/shared/wall-feed.blade.php. --}}
@php
    $wallInitials = fn ($name) => collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('') ?: 'SK';
@endphp

<section class="sk-card p-6">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h2 class="sk-section-title">Activity Feed</h2>
            <p class="sk-section-subtitle">Updates and accomplishments shared across SK councils.</p>
        </div>
        <span class="sk-badge sk-badge--gray">
            @include('partials.ui.icon', ['icon' => 'users', 'iconSize' => 14])
            Shared Wall
        </span>
    </div>

    @if (session('wall_status'))
        <div class="sk-alert sk-alert--success mb-4 !text-[13px]">
            @include('partials.ui.icon', ['icon' => 'circle-check', 'iconSize' => 17])
            <span>{{ session('wall_status') }}</span>
        </div>
    @endif

    @if ($errors->has('post_content'))
        <div class="sk-alert sk-alert--error mb-4 !text-[13px]">
            @include('partials.ui.icon', ['icon' => 'circle-alert', 'iconSize' => 17])
            <span>{{ $errors->first('post_content') }}</span>
        </div>
    @endif

    <form action="{{ route('wall.posts.store') }}" method="POST" class="mb-6 rounded-2xl border border-gray-200 bg-[#f8f9fb] p-4">
        @csrf
        <input type="hidden" name="post_category" value="{{ $defaultPostCategory ?? 'update' }}">

        <div class="flex items-start gap-3">
            <span class="sk-avatar">{{ $wallInitials($fullName ?? '') }}</span>
            <textarea name="post_content" class="min-h-[88px] w-full resize-none rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm" rows="3" placeholder="Share updates with everyone..." required>{{ old('post_content') }}</textarea>
        </div>

        <div class="flex justify-end mt-3">
            <button type="submit" class="sk-btn sk-btn--primary">
                @include('partials.ui.icon', ['icon' => 'send', 'iconSize' => 16])
                Post
            </button>
        </div>
    </form>

    <div class="space-y-4">
        @forelse ($feedPosts as $post)
            <article class="rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-gray-300 hover:shadow-sm">
                <div class="flex items-start justify-between gap-4 mb-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="sk-avatar">{{ $wallInitials($post->author_name) }}</span>
                        <div class="min-w-0">
                            <h3 class="font-bold text-gray-900 leading-tight">{{ $post->author_name }}</h3>
                            <p class="text-xs font-semibold text-gray-500 mt-0.5">
                                {{ $post->role_label }}{{ $post->barangay_name ? ' - Barangay '.$post->barangay_name : '' }}
                            </p>
                        </div>
                    </div>
                    <span class="flex items-center gap-1 text-xs font-semibold text-gray-400 whitespace-nowrap">
                        @include('partials.ui.icon', ['icon' => 'clock', 'iconSize' => 13])
                        {{ \Illuminate\Support\Carbon::parse($post->created_at)->diffForHumans() }}
                    </span>
                </div>
                <div class="mb-3">
                    <span class="sk-badge sk-badge--red sk-badge--dot">{{ $post->title }}</span>
                </div>
                <p class="text-[15px] leading-relaxed text-gray-700 whitespace-pre-line">{{ $post->content }}</p>
                <div class="mt-4 border-t border-gray-100 pt-3">
                    <form action="{{ route('wall.posts.like', $post->announcement_id) }}" method="POST">
                        @csrf
                        <button type="submit" aria-label="{{ $post->liked_by_current_user ? 'Unlike post' : 'Like post' }}" class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-bold transition {{ $post->liked_by_current_user ? 'bg-red-50 text-red-600' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                            <span class="{{ $post->liked_by_current_user ? '[&_path]:fill-current' : '' }}">
                                @include('partials.ui.icon', ['icon' => 'heart', 'iconSize' => 17])
                            </span>
                            <span>{{ $post->likes_count }}</span>
                        </button>
                    </form>
                </div>
            </article>
        @empty
            <div class="sk-empty">
                <span class="sk-icon-tile">
                    @include('partials.ui.icon', ['icon' => 'message-square', 'iconSize' => 24])
                </span>
                <p class="sk-empty__text">No posts yet. Start sharing updates.</p>
            </div>
        @endforelse
    </div>
</section>

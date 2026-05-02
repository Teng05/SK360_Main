{{-- File guide: Blade view template for resources/views/shared/wall-feed.blade.php. --}}
<div class="bg-white p-6 rounded-xl shadow">
    <div class="flex justify-between items-center mb-4">
        <h2 class="font-semibold">Activity Feed</h2>
        <span class="text-xs text-gray-400">Shared Wall</span>
    </div>

    @if (session('wall_status'))
        <div class="mb-4 rounded-xl border border-green-100 bg-green-50 px-4 py-3 text-xs font-semibold text-green-700">
            {{ session('wall_status') }}
        </div>
    @endif

    @if ($errors->has('post_content'))
        <div class="mb-4 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-xs font-semibold text-red-600">
            {{ $errors->first('post_content') }}
        </div>
    @endif

    <form action="{{ route('wall.posts.store') }}" method="POST" class="border rounded-lg p-4 mb-4">
        @csrf
        <input type="hidden" name="post_category" value="{{ $defaultPostCategory ?? 'update' }}">
        <textarea name="post_content" class="w-full border rounded p-3 resize-none focus:outline-none focus:ring-2 focus:ring-red-400" rows="3" placeholder="Share updates with everyone..." required>{{ old('post_content') }}</textarea>

        <div class="flex justify-end mt-3">
            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">
                Post
            </button>
        </div>
    </form>

    <div class="space-y-4">
        @forelse ($feedPosts as $post)
            <div class="border rounded-xl p-4 bg-gray-50">
                <div class="flex items-start justify-between gap-4 mb-2">
                    <div>
                        <h3 class="font-semibold text-gray-800">{{ $post->author_name }}</h3>
                        <p class="text-[11px] text-gray-400">
                            {{ $post->role_label }}{{ $post->barangay_name ? ' - Barangay '.$post->barangay_name : '' }}
                        </p>
                    </div>
                    <span class="text-xs text-gray-400 whitespace-nowrap">
                        {{ \Illuminate\Support\Carbon::parse($post->created_at)->diffForHumans() }}
                    </span>
                </div>
                <div class="mb-2">
                    <span class="rounded-full bg-red-50 px-2 py-1 text-[10px] font-bold uppercase text-red-600">{{ $post->title }}</span>
                </div>
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ $post->content }}</p>
                <div class="mt-4 border-t pt-3">
                    <form action="{{ route('wall.posts.like', $post->announcement_id) }}" method="POST">
                        @csrf
                        <button type="submit" aria-label="{{ $post->liked_by_current_user ? 'Unlike post' : 'Like post' }}" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition {{ $post->liked_by_current_user ? 'bg-red-50 text-red-600' : 'text-gray-500 hover:bg-gray-100 hover:text-red-600' }}">
                            <span>{{ $post->liked_by_current_user ? '♥' : '♡' }}</span>
                            <span class="text-gray-400">{{ $post->likes_count }}</span>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-center text-gray-400 py-10">
                No posts yet. Start sharing updates.
            </div>
        @endforelse
    </div>
</div>

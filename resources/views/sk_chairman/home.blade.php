@extends('layouts.app')

@section('title', 'SK Chairman Dashboard')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
    <script crossorigin src="https://unpkg.com/react@18/umd/react.development.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
@endsection

@section('content')
<div class="flex h-screen bg-gray-100">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 overflow-y-auto">
        <div class="bg-red-500 rounded-lg p-4 flex items-center gap-3 mb-4 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 h-10 w-10 rounded-full flex items-center justify-center text-lg">&#128273;</div>
            <div>
                <p class="font-semibold text-sm">SK Chairman</p>
                <p class="text-xs opacity-80">Active Role</p>
            </div>
        </div>

        <nav class="space-y-2 text-xs">
            @foreach ($menuItems as $item)
                @php $isActive = $item['link'] === $currentUrl; @endphp
                <a href="{{ $item['link'] }}" class="flex items-center gap-3 p-3 rounded-xl {{ $isActive ? 'bg-red-500' : 'hover:bg-red-500 transition' }}">
                    <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400 text-white' }} h-10 w-10 rounded-xl flex items-center justify-center text-sm">{!! $item['icon'] !!}</span>
                    <span class="{{ $isActive ? 'text-yellow-300 font-semibold' : 'text-white' }} text-sm">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="flex-1 flex flex-col">
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow">
            <input
                type="text"
                placeholder="Search..."
                class="px-4 py-2 rounded-full text-black w-1/3 focus:outline-none"
            >

            <div class="flex items-center gap-3 relative">
                <div class="relative">
                    <button id="notifBtn" type="button" class="text-xl hover:bg-red-500 p-2 rounded-lg transition">
                        &#128276;
                    </button>

                    <div id="notifDropdown" class="hidden absolute right-0 mt-3 w-72 bg-white rounded-2xl shadow-xl border z-50 overflow-hidden">
                        <div class="px-4 py-3 font-semibold border-b text-gray-800">
                            Notifications
                        </div>

                        <div class="max-h-64 overflow-y-auto">
                            <div class="px-4 py-3 hover:bg-gray-100 text-sm text-gray-700">
                                No notifications yet
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <button id="userMenuBtn" type="button" class="flex items-center gap-2 hover:bg-red-500 px-3 py-2 rounded-lg transition">
                        <span class="font-semibold">{{ $fullName }}</span>
                    </button>

                    <div id="userDropdown" class="hidden absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-xl border overflow-hidden z-50">
                        <div class="px-5 py-4 font-semibold text-gray-800 border-b">
                            My Account
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left flex items-center gap-3 px-5 py-3 text-red-500 hover:bg-gray-100 transition">
                                <span>&#8617;</span>
                                <span>Log Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 overflow-y-auto">
            <h1 class="text-2xl font-bold mb-4">
                Good morning, <span>{{ $fullName }}</span>!
            </h1>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                @foreach ($summaryCards as $card)
                    <div class="{{ $card['classes'] }} p-5 rounded-xl shadow">
                        <h2 class="text-2xl font-bold">{{ $card['value'] }}</h2>
                        <p class="text-sm">{{ $card['label'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="bg-white p-5 rounded-xl shadow mb-6">
                <h2 class="font-semibold mb-3">Quick Actions</h2>
                <div class="flex gap-3 flex-wrap">
                    <a href="{{ route('sk_chairman.reports') }}" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">
                        Open Reports
                    </a>

                    <a href="{{ route('sk_chairman.budget') }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg">
                        Open Budget
                    </a>

                    <a href="{{ route('sk_chairman.chat') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                        Open Chat
                    </a>
                </div>
            </div>

            <div id="activityFeedApp"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const notifBtn = document.getElementById('notifBtn');
    const notifDropdown = document.getElementById('notifDropdown');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');

    notifBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        notifDropdown.classList.toggle('hidden');
        userDropdown.classList.add('hidden');
    });

    userMenuBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        userDropdown.classList.toggle('hidden');
        notifDropdown.classList.add('hidden');
    });

    document.addEventListener('click', function (e) {
        if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.add('hidden');
        }

        if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.add('hidden');
        }
    });
</script>

<script type="text/babel">
    const { useState } = React;

    function ActivityFeed() {
        const [postText, setPostText] = useState("");
        const [posts, setPosts] = useState([]);

        const handlePost = () => {
            const trimmedText = postText.trim();

            if (!trimmedText) return;

            const newPost = {
                id: Date.now(),
                author: @json($fullName),
                content: trimmedText,
                time: new Date().toLocaleString()
            };

            setPosts([newPost, ...posts]);
            setPostText("");
        };

        return (
            <div className="bg-white p-6 rounded-xl shadow">
                <div className="flex justify-between items-center mb-4">
                    <h2 className="font-semibold">Activity Feed</h2>
                    <span className="text-xs text-gray-400">Live</span>
                </div>

                <div className="border rounded-lg p-4 mb-4">
                    <textarea
                        className="w-full border rounded p-3 resize-none focus:outline-none focus:ring-2 focus:ring-red-400"
                        rows="3"
                        placeholder="Share an update with your barangay..."
                        value={postText}
                        onChange={(e) => setPostText(e.target.value)}
                    ></textarea>

                    <div className="flex justify-end mt-3">
                        <button
                            type="button"
                            onClick={handlePost}
                            className="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg"
                        >
                            Post
                        </button>
                    </div>
                </div>

                {posts.length === 0 ? (
                    <div className="text-center text-gray-400 py-10">
                        No posts yet. Start sharing updates.
                    </div>
                ) : (
                    <div className="space-y-4">
                        {posts.map((post) => (
                            <div key={post.id} className="border rounded-xl p-4 bg-gray-50">
                                <div className="flex items-center justify-between mb-2">
                                    <h3 className="font-semibold text-gray-800">{post.author}</h3>
                                    <span className="text-xs text-gray-400">{post.time}</span>
                                </div>
                                <p className="text-sm text-gray-700 whitespace-pre-line">{post.content}</p>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        );
    }

    ReactDOM.createRoot(document.getElementById('activityFeedApp')).render(<ActivityFeed />);
</script>
@endpush

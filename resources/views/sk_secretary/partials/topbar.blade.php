<div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow">
    {{-- Topbar search --}}
    <input type="text" placeholder="Search..." class="px-4 py-2 rounded-full text-black w-1/3 focus:outline-none">

    <div class="flex items-center gap-3 relative">
        {{-- Notification dropdown --}}
        <div class="relative">
            <button id="notifBtn" type="button" class="text-xl hover:bg-red-500 p-2 rounded-lg transition">
                &#128276;
            </button>

            <div id="notifDropdown" class="hidden absolute right-0 mt-3 w-72 bg-white rounded-2xl shadow-xl border z-50 overflow-hidden">
                <div class="px-4 py-3 font-semibold border-b text-gray-800">Notifications</div>
                <div class="max-h-64 overflow-y-auto">
                    <div class="px-4 py-3 hover:bg-gray-100 text-sm text-gray-700">No notifications yet</div>
                </div>
            </div>
        </div>

        {{-- Account dropdown --}}
        <div class="relative">
            <button id="userMenuBtn" type="button" class="flex items-center gap-2 hover:bg-red-500 px-3 py-2 rounded-lg transition">
                <span class="font-semibold">{{ $fullName }}</span>
            </button>

            <div id="userDropdown" class="hidden absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-xl border overflow-hidden z-50">
                <div class="px-5 py-4 font-semibold text-gray-800 border-b">My Account</div>
                <a href="{{ route('sk_secretary.profile') }}" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-100 transition">
                    <span>&#128100;</span>
                    <span class="text-gray-700">Profile Settings</span>
                </a>
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

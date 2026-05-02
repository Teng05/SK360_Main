{{-- File guide: Blade view template for resources/views/youth/profile.blade.php. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | SK 360&deg;</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .tab-active { border-bottom: 2px solid #ef4444; color: #ef4444; }
    </style>
</head>
<body class="bg-gray-50 font-sans" x-data="{
    activeTab: 'personal',
    isEditing: false,
    showPassModal: false
}">

<div class="flex h-screen overflow-hidden">
    <div class="w-64 bg-red-600 text-white flex flex-col p-3 shadow-xl z-20">
        <div class="flex items-center gap-2 mb-3">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="w-7 h-7" alt="logo">
            <h2 class="text-base font-bold text-white">SK 360&deg;</h2>
        </div>
        <div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
            <div class="bg-yellow-400 text-red-600 p-1 rounded-full font-bold px-2">&#128100;</div>
            <div>
                <p class="font-semibold">{{ $userName }}</p>
                <p class="opacity-80 text-[10px]">Youth Member</p>
            </div>
        </div>
        <nav class="space-y-1 text-xs">
            @foreach ($menuItems as $item)
                @php $isActive = $currentUrl === $item['link']; @endphp
                <a href="{{ $item['link'] }}" class="flex items-center gap-2 p-2 rounded-lg transition {{ $isActive ? 'bg-red-500 text-yellow-300 font-bold border-l-4 border-yellow-300' : 'hover:bg-red-500' }}">
                    <span class="{{ $isActive ? 'bg-yellow-400 text-red-600' : 'bg-red-400' }} p-1 rounded">{{ $item['icon'] }}</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <div class="bg-red-600 text-white px-6 py-3 flex justify-between items-center shadow relative z-10">
            <div class="w-1/4"></div>
            <div class="w-1/3">
                <input type="text" placeholder="Search settings..." class="w-full px-4 py-2 rounded-full text-black text-sm outline-none">
            </div>
            <div class="w-1/4 flex justify-end items-center gap-5 text-sm">
                <button class="hover:opacity-80">&#128276;</button>
                <div class="relative">
                    <button id="profileDropdownBtn" class="flex items-center gap-2 font-semibold focus:outline-none hover:opacity-80 transition">
                        <span>{{ $userName }}</span>
                        <span class="text-[10px]">&#9660;</span>
                    </button>
                    <div id="profileMenu" class="absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-2xl py-2 z-[9999] hidden border border-gray-100">
                        <div class="px-4 py-3 border-b border-gray-50">
                            <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest">Account Settings</p>
                        </div>
                        <a href="{{ route('youth.profile') }}" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 text-xs flex items-center gap-2 transition">
                            <span>&#128100;</span> View Profile
                        </a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-3 text-red-600 hover:bg-red-50 text-xs font-bold flex items-center gap-2 transition">
                                <span>&#128682;</span> Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <main class="flex-1 overflow-y-auto p-8 bg-gray-50">
            <div class="max-w-5xl mx-auto">
                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <header class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Profile Settings</h1>
                    <p class="text-sm text-gray-500">Manage your profile and security settings for Lipa Youth.</p>
                </header>

                <div class="bg-white rounded-[32px] p-6 shadow-sm border border-gray-100 flex items-center justify-between mb-8">
                    <div class="flex items-center gap-6">
                        <div class="relative group">
                            <div class="w-24 h-24 bg-red-600 rounded-2xl flex items-center justify-center text-white text-4xl font-bold border-4 border-white shadow-md overflow-hidden">
                                @if ($hasProfilePicColumn && !empty($user->profile_pic ?? null))
                                    <img src="{{ asset('uploads/profile_pics/' . $user->profile_pic) }}" class="w-full h-full object-cover" alt="Profile picture">
                                @else
                                    {{ strtoupper(substr($user->first_name ?? 'U', 0, 1)) }}
                                @endif
                            </div>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">{{ $userName }}</h2>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Barangay {{ $barangayName }}</p>
                            <div class="mt-2 inline-flex items-center gap-1.5 px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-black">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span> VERIFIED
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-sm pr-4 border-l pl-8 border-gray-100">
                        <div class="space-y-1">
                            <p class="text-[10px] font-black text-gray-400 uppercase">Term Start</p>
                            <p class="font-bold text-gray-700">June 2024</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[10px] font-black text-gray-400 uppercase">Term End</p>
                            <p class="font-bold text-gray-700">June 2026</p>
                        </div>
                    </div>
                </div>

                @unless ($hasProfilePicColumn)
                    <div class="mb-8 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Photo upload is disabled because `users.profile_pic` does not exist in the current database schema.
                    </div>
                @endunless

                <div class="flex gap-8 mb-8 border-b border-gray-200">
                    <button @click="activeTab = 'personal'" :class="activeTab === 'personal' ? 'tab-active' : 'text-gray-400'" class="pb-4 text-xs font-black uppercase tracking-widest transition-all">Personal</button>
                    <button @click="activeTab = 'security'" :class="activeTab === 'security' ? 'tab-active' : 'text-gray-400'" class="pb-4 text-xs font-black uppercase tracking-widest transition-all">Security</button>
                </div>

                <div x-show="activeTab === 'personal'" x-transition x-cloak class="space-y-6">
                    <div class="bg-white rounded-[32px] p-8 shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-10">
                            <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest">Personal Information</h3>
                            <button @click="isEditing = !isEditing" type="button" class="text-xs font-bold text-red-600 hover:bg-red-50 px-4 py-2 rounded-xl transition" x-text="isEditing ? 'Cancel Edit' : 'Edit Information'"></button>
                        </div>

                        <form action="{{ route('youth.profile.update') }}" method="POST">
                            @csrf
                            <div class="grid grid-cols-2 gap-x-12 gap-y-8">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">First Name</label>
                                    <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" :disabled="!isEditing" :class="isEditing ? 'bg-gray-50 border-gray-200' : 'bg-transparent border-transparent cursor-default'" class="w-full p-2 text-sm font-bold text-gray-700 border-b outline-none transition">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Last Name</label>
                                    <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" :disabled="!isEditing" :class="isEditing ? 'bg-gray-50 border-gray-200' : 'bg-transparent border-transparent cursor-default'" class="w-full p-2 text-sm font-bold text-gray-700 border-b outline-none transition">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Email Address</label>
                                    <div class="flex items-center gap-2 bg-gray-50 rounded-2xl px-3">
                                        <input type="text" value="{{ $user->email }}" disabled class="w-full py-3 text-sm font-bold text-gray-400 bg-transparent outline-none cursor-not-allowed">
                                        <span class="text-[8px] bg-gray-200 text-gray-500 px-2 py-1 rounded font-black tracking-widest">LOCKED</span>
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Phone Number</label>
                                    <div class="flex items-center gap-2 bg-gray-50 rounded-2xl px-3">
                                        <input type="text" value="{{ $user->phone_number ?? '09XXXXXXXXX' }}" disabled class="w-full py-3 text-sm font-bold text-gray-400 bg-transparent outline-none cursor-not-allowed">
                                        <span class="text-[8px] bg-gray-200 text-gray-500 px-2 py-1 rounded font-black tracking-widest">LOCKED</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-10 p-5 bg-blue-50/50 border border-blue-100 rounded-3xl flex gap-4 items-center">
                                <span class="text-xl">&#8505;</span>
                                <p class="text-[11px] text-blue-700 font-medium">To update your <strong>Email</strong> or <strong>Phone Number</strong>, update the current backend flow first. These fields are intentionally locked here.</p>
                            </div>

                            <div x-show="isEditing" class="mt-8 flex justify-end">
                                <button class="bg-red-600 text-white px-10 py-3 rounded-xl text-xs font-black shadow-lg shadow-red-100 hover:bg-red-700 transition uppercase tracking-widest">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div x-show="activeTab === 'security'" x-transition x-cloak class="space-y-6">
                    <div class="bg-white rounded-3xl p-8 shadow-sm border border-gray-100">
                        <h3 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-8">Manage Password</h3>
                        <div class="flex items-center justify-between p-6 bg-gray-50 rounded-3xl group hover:bg-red-50 transition">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-xl shadow-sm">&#128273;</div>
                                <div>
                                    <p class="text-sm font-bold text-gray-800">Password</p>
                                    <p class="text-[11px] text-gray-400">Update your account password regularly.</p>
                                </div>
                            </div>
                            <button @click="showPassModal = true" type="button" class="px-6 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-bold text-red-600 shadow-sm group-hover:bg-red-600 group-hover:text-white transition">Update Password</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<div x-show="showPassModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-black/50 backdrop-blur-sm">
    <div class="bg-white w-full max-w-md rounded-[40px] p-10 shadow-2xl">
        <h2 class="text-2xl font-black text-gray-900 mb-2">Update Password</h2>
        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mb-8">Security Preference</p>
        <form action="{{ route('youth.profile.password') }}" method="POST" class="space-y-6">
            @csrf
            <div class="space-y-1">
                <label class="text-[10px] font-black text-gray-400 uppercase ml-1">Current Password</label>
                <input type="password" name="current_password" required class="w-full bg-gray-50 p-4 rounded-2xl text-sm font-bold border-transparent focus:border-red-200 outline-none transition">
            </div>
            <div class="space-y-1">
                <label class="text-[10px] font-black text-gray-400 uppercase ml-1">New Password</label>
                <input type="password" name="password" required class="w-full bg-gray-50 p-4 rounded-2xl text-sm font-bold border-transparent focus:border-red-200 outline-none transition">
            </div>
            <div class="space-y-1">
                <label class="text-[10px] font-black text-gray-400 uppercase ml-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" required class="w-full bg-gray-50 p-4 rounded-2xl text-sm font-bold border-transparent focus:border-red-200 outline-none transition">
            </div>
            <div class="flex gap-4 pt-6">
                <button type="button" @click="showPassModal = false" class="flex-1 px-8 py-3 rounded-xl text-xs font-bold text-gray-400 hover:bg-gray-100 transition uppercase tracking-widest">Cancel</button>
                <button type="submit" class="flex-1 px-8 py-3 bg-red-600 text-white rounded-xl text-xs font-black shadow-lg shadow-red-100 hover:bg-red-700 transition uppercase tracking-widest">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
const dropdownBtn = document.getElementById('profileDropdownBtn');
const profileMenu = document.getElementById('profileMenu');

dropdownBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    profileMenu.classList.toggle('hidden');
});

window.addEventListener('click', (e) => {
    if (!profileMenu.contains(e.target) && !dropdownBtn.contains(e.target)) {
        profileMenu.classList.add('hidden');
    }
});
</script>

</body>
</html>

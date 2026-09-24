{{-- File guide: Blade view template for resources/views/shared/profile-settings.blade.php. --}}
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
<body class="bg-gray-50 font-sans" x-data="{ activeTab: 'personal', isEditing: false, showPassModal: false }">
<div class="flex h-screen overflow-hidden">
    @include('partials.app.sidebar')

    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        @include('partials.app.topbar', ['accountButtonId' => 'profileDropdownBtn', 'accountMenuId' => 'profileMenu', 'bindBell' => true, 'search' => ['placeholder' => 'Search settings...']])

        <main class="flex-1 overflow-y-auto p-8 bg-gray-50">
            <div class="max-w-5xl mx-auto">
                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('status') }}</div>
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
                    <span class="sk-eyebrow"><span class="sk-dot"></span>Profile</span>
                    <h1 class="text-2xl font-bold text-gray-800">Profile Settings</h1>
                    <p class="text-sm text-gray-500">{{ $pageDescription }}</p>
                </header>

                <div class="bg-white rounded-[32px] p-6 shadow-sm border border-gray-100 flex items-center justify-between mb-8">
                    <div class="flex items-center gap-6">
                        <div class="w-24 h-24 bg-red-600 rounded-2xl flex items-center justify-center text-white text-4xl font-bold border-4 border-white shadow-md overflow-hidden">
                            @if ($hasProfilePicColumn && !empty($user->profile_pic ?? null))
                                <img src="{{ asset('uploads/profile_pics/' . $user->profile_pic) }}" class="w-full h-full object-cover" alt="Profile picture">
                            @else
                                {{ strtoupper(substr($user->first_name ?? 'U', 0, 1)) }}
                            @endif
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">{{ $userName }}</h2>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ $roleLabel }} - Barangay {{ $barangayName }}</p>
                            <div class="mt-2 inline-flex items-center gap-1.5 px-3 py-1 bg-green-100 text-green-700 rounded-full text-[10px] font-black">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> VERIFIED
                            </div>
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

                        <form action="{{ $updateRoute }}" method="POST">
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
        <form action="{{ $passwordRoute }}" method="POST" class="space-y-6">
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

if (dropdownBtn && profileMenu) {
    dropdownBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        profileMenu.classList.toggle('hidden');
    });

    window.addEventListener('click', (e) => {
        if (!profileMenu.contains(e.target) && !dropdownBtn.contains(e.target)) {
            profileMenu.classList.add('hidden');
        }
    });
}
</script>
</body>
</html>


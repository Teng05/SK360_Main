<div class="bg-red-500 rounded-lg p-2 flex items-center gap-2 mb-3 shadow text-xs">
    <div class="bg-yellow-400 text-red-600 p-1 rounded-full text-sm shrink-0">&#128100;</div>
    <div class="min-w-0">
        <p class="font-semibold text-xs break-words">{{ $fullName ?? $userName ?? trim(auth()->user()->first_name . ' ' . auth()->user()->last_name) }}</p>
        <p class="text-xs opacity-80">{{ match(auth()->user()->role) { 'sk_president' => 'SK President', 'sk_chairman' => 'SK Chairman', 'sk_secretary' => 'SK Secretary', default => $roleLabel ?? 'Member' } }}</p>
    </div>
</div>

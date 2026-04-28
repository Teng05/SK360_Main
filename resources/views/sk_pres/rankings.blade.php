@extends('layouts.app')

@section('title', 'SK 360 Rankings')

@section('page_css')
    <script src="https://cdn.tailwindcss.com"></script>
@endsection

@section('content')
    @include('shared.rankings-page')
@endsection

@push('scripts')
<script>
    const notifBtn = document.getElementById('notifBtn');
    const profileDropdownBtn = document.getElementById('profileDropdownBtn');
    const profileMenu = document.getElementById('profileMenu');

    if (notifBtn) {
        notifBtn.addEventListener('click', function () {});
    }

    if (profileDropdownBtn && profileMenu) {
        profileDropdownBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            profileMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', function (e) {
            if (!profileMenu.contains(e.target) && !profileDropdownBtn.contains(e.target)) {
                profileMenu.classList.add('hidden');
            }
        });
    }
</script>
@endpush

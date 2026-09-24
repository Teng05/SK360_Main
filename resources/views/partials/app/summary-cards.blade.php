{{--
    KPI cards for the $summaryCards arrays the home controllers build
    (value + label). Only the presentation is chosen here: an outline icon and
    soft tint per known label, and an optional caption from $cardMeta.
--}}
@php
    $skCardStyles = [
        'Reports Submitted' => ['icon' => 'file-text', 'tone' => ''],
        'Community Engagement' => ['icon' => 'users', 'tone' => 'blue'],
        'Pending Reviews' => ['icon' => 'hourglass', 'tone' => 'orange'],
        'Upcoming Events' => ['icon' => 'calendar-days', 'tone' => 'yellow'],
        'Budget Documents' => ['icon' => 'wallet', 'tone' => 'blue'],
        'Your Ranking' => ['icon' => 'trophy', 'tone' => 'yellow'],
        'Pending Tasks' => ['icon' => 'clipboard-list', 'tone' => 'orange'],
    ];
    $skCardMeta = $cardMeta ?? [];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
    @foreach ($summaryCards as $skCard)
        @php $skStyle = $skCardStyles[$skCard['label']] ?? ['icon' => 'layout-grid', 'tone' => '']; @endphp

        <div class="sk-stat">
            <div class="sk-stat__top">
                <div>
                    <p class="sk-stat__label">{{ $skCard['label'] }}</p>
                    <p class="sk-stat__value">{{ $skCard['value'] }}</p>
                </div>
                <span class="sk-icon-tile {{ $skStyle['tone'] ? 'sk-icon-tile--' . $skStyle['tone'] : '' }}">
                    @include('partials.ui.icon', ['icon' => $skStyle['icon'], 'iconSize' => 21])
                </span>
            </div>
            @if (!empty($skCardMeta[$skCard['label']]))
                <p class="sk-stat__meta">{{ $skCardMeta[$skCard['label']] }}</p>
            @endif
        </div>
    @endforeach
</div>

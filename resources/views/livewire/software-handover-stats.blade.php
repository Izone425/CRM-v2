<div wire:poll.10s>
    <div class="dashboard-stats">
        @foreach ($stats as $stat)
            <div class="stat-box {{ $stat['class'] }}">
                <div class="stat-count">{{ $stat['count'] }}</div>
                <div class="stat-label">{{ $stat['label'] }}</div>
            </div>
        @endforeach
    </div>
</div>

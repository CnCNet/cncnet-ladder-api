<div class="player-of-the-day-award">

    @if(\Carbon\Carbon::now()->month == 10)
        @include('animations.player', [
            'src' => '/animations/pumpkin.json',
            'loop' => 'true',
            'width' => '100%',
            'height' => '100px',
        ])
    @else
    <div class="icon fa" style="width: 45px;">
        @include('icons.crown', [
            'colour' => '#ffcd00',
        ])    
    </div>
    @endif

    <div style="color: #ffcd00">
        <h4 class="mb-0">Player of the day</h4>
        <p class="mt-0">
            <strong>Most wins today with {{ $wins }}</small></strong>
        </p>
    </div>
</div>

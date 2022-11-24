
<div class="player-of-the-day-award">

    <div class="icon fa" style="width: 45px;">
        @include("icons.crown", [
            "colour" => "#ffcd00", 
        ])
    </div>

    <div class="info">
        <h4 class="title">Player of the day</h4>
        <div class="details"><small>Most wins today with {{ $wins }}</small></div>
    </div>
</div>
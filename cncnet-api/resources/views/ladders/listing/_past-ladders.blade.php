<?php $date = \Carbon\Carbon::parse($history->ends); ?>

@if ($date->isPast() && ($search === null || $search == ''))
    <h3><strong>{{ $date->format('F - Y') }}</strong> Ladder Champions!</h3>
    <div class="feature">
        <div class="row">
            <?php $winners = $players->slice(0, 2); ?>
            @foreach ($winners as $k => $winner)
                <div class="col-xs-12 col-md-6">
                    <a href="{{ \App\URLHelper::getPlayerProfileUrl($history, $winner->player_name) }}" title="View {{ $winner->player_name }}"
                        class="ladder-cover cover-{{ $history->ladder->abbreviation }}"
                        style="padding: 4rem;background-image: url('/images/ladder/{{ $history->ladder->abbreviation . '-cover-masters.png' }}')">
                        <div>
                            <h1><strong>{{ $winner->player_name }}</strong></h1>
                            <h2><strong>Rank #{{ $k + 1 }}</strong></h2>
                            @if ($k > 0)
                                <small>Runner up of the
                                @else
                                    <small>Champion of the
                            @endif
                            <strong>{{ $date->format('m/Y') }}</strong> {{ $history->abbreviation }} League</small>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endif

<?php $date = \Carbon\Carbon::parse($history->ends); ?>

@if ($date->isPast() && ($search === null || $search == ''))
    <h3><strong>{{ $date->format('m/Y') }}</strong> Ladder Champions!</h3>
    <div class="feature">
        <div class="row">
            <?php $winners = $players->slice(0, 2); ?>
            @foreach ($winners as $k => $winner)
                <div class="col-xs-12 col-md-6">
                    <div class="ladder-cover cover-{{ $history->ladder->abbreviation }}""
                        style="padding: 4rem;background-image: url('/images/ladder/{{ $history->ladder->abbreviation . '-cover-masters.png' }}')">
                        <div class="details tier-league-cards">
                            <div class="type">
                                <h1 class="lead"><strong>{{ $winner->player_name }}</strong></h1>
                                <h2><strong>Rank #{{ $k + 1 }}</strong></h2>
                                <div>
                                    Wins - {{ $winner->wins }}
                                    Games - {{ $winner->games }}
                                </div>
                                @if ($k > 0)
                                    <small>Runner up of the
                                    @else
                                        <small>Champion of the
                                @endif
                                <strong>{{ $date->format('m/Y') }}</strong> {{ $history->abbreviation }} League</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

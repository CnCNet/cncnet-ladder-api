<?php $date = \Carbon\Carbon::parse($history->ends); ?>

@if ($date->isPast() && ($search === null || $search == ''))
    <h2><strong>{{ $date->format('m/Y') }}</strong> League Champions!</h2>
    <div class="feature">
        <div class="row">
            <?php $winners = $players->slice(0, 2); ?>
            @foreach ($winners as $k => $winner)
                <div class="col-xs-12 col-md-6">
                    <div class="ladder-cover cover-{{ $history->ladder->abbreviation }}" style="background-image: url('/images/ladder/{{ $history->ladder->abbreviation . '-cover-masters.png' }}')">
                        <div class="details tier-league-cards">
                            <div class="type">
                                <h1 class="lead"><strong>{{ $winner->player_name }}</strong></h1>
                                <h2><strong>Rank #{{ $k + 1 }}</strong></h2>
                                <ul class="list-inline" style="font-size: 14px;">
                                    <li>
                                        Wins
                                        <i class="fa fa-level-up"></i> {{ $winner->wins }}
                                    </li>
                                    <li>
                                        Games
                                        <i class="fa fa-diamond"></i> {{ $winner->games }}
                                    </li>
                                </ul>
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

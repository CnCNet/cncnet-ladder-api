<?php $date = \Carbon\Carbon::parse($history->ends); ?>

@if ($date->isPast() && ($search === null || $search == ''))
    <h3><strong>{{ $date->format('F - Y') }}</strong> Ladder Champions!</h3>
    <div class="league-selection">
        @if ($players)
            <?php $winners = $players->slice(0, 2); ?>
            @foreach ($winners as $k => $winner)
                <a href="{{ \App\UrlHelper::getLadderLeague($history, 1) }}" title="{{ $history->ladder->name }}" class="league-box tier-1">
                    {!! $k == 0 ? \App\Helpers\LeagueHelper::getLeagueIconByTier(1) : '' !!}
                    <h3 class="league-title">
                        {{ $k == 0 ? 'Winner - ' : 'Runner up - ' }}#{{ $k + 1 }}
                        {{ $winner->player_name }}
                    </h3>
                </a>
            @endforeach
        @endif
    </div>
@endif

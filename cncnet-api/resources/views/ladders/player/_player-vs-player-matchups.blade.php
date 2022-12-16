<h4>Player vs Player stats</h4>
<table class="table player-vs-player-table">
    <thead>
        <tr>
            <th scope="col">Opponent</th>
            <th scope="col">{{$player->username}} Wins</th>
            <th scope="col">{{$player->username}} Losses</th>
            <th scope="col">Total games</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($playerMatchups as $playerMatchup => $winLossArr)
        <tr>
            <th scope="row">
                <div class="player-matchup" style="margin-right:2rem;">
                    <a href="{{\App\URLHelper::getPlayerProfileUrl($history, $playerMatchup)}}">{{$playerMatchup}}
                </div>
            </th>
            @foreach ($winLossArr as $k => $v)
            <td class=" count {{ $k }}">
                @if ($k == 'won')
                {{ $v }}
                @elseif ($k == 'lost')
                {{ $v }}
                @elseif ($k == 'total')
                {{ $v }}
                @endif
            </td>
            @endforeach
        </tr>
        @endforeach

    </tbody>
</table>
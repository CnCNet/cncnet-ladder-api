<div class="table-container">
    <!-- Button to toggle table visibility -->
    <button class="btn btn-primary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#teamMatchupTable" aria-expanded="true" aria-controls="teamMatchupTable">
        Team stats
    </button>

    <!-- Collapsible Table (Expanded by Default) -->
    <div class="collapse show" id="teamMatchupTable">
        <div class="table-responsive">
            <table class="table player-vs-player-table">
                <thead>
                <tr>
                    <th scope="col">Teammate</th>
                    <th scope="col">Wins</th>
                    <th scope="col">Losses</th>
                    <th scope="col">Total games</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($teamMatchups as $playerMatchup => $winLossArr)
                    <tr>
                        <th scope="row">
                            <div class="player-matchup" style="margin-right:2rem;">
                                <a href="{{ \App\Models\URLHelper::getPlayerProfileUrl($history, $playerMatchup) }}">{{ $playerMatchup }}</a>
                            </div>
                        </th>
                        @foreach ($winLossArr as $k => $v)
                            <td class="count {{ $k }}">
                                @if ($k == 'won' || $k == 'lost' || $k == 'total')
                                    {{ $v }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

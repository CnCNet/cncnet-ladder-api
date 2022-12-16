    <table class="table player-factions-table">
        <thead>
            <tr>
                <th scope="col">Faction</th>
                <th scope="col">Won</th>
                <th scope="col">Lost</th>
                <th scope="col">Played</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($playerFactionsByMonth as $faction => $winLossArr)
                <tr>
                    <th scope="row">
                        <div class="faction-name" style="margin-right:2rem;">
                            @php $faction = \App\Helpers\FactionHelper::getFactionCountryByHistory($history, $faction); @endphp
                            <div class="{{ $history->ladder->game }} player-faction player-faction-{{ strtolower($faction) }}"></div>
                            {{ $faction }}
                        </div>
                    </th>
                    @foreach ($winLossArr as $k => $v)
                        <td class="count {{ $k }}">
                            @if ($k == 'won')
                                x{{ $v }}
                            @elseif ($k == 'lost')
                                x{{ $v }}
                            @elseif ($k == 'total')
                                x{{ $v }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach

        </tbody>
    </table>

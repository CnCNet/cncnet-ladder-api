<div class="player-card">
    <div class="player-details">
        <h2 class="username">
            <a href="{{ \App\URLHelper::getClanProfileLadderUrl($history, $clanCache->clan_id) }}"
                title="View {{ $clanCache->clan_name }}'s clan profile">
                {{ $clanCache->clan_name }}

                <br />
                <span style="font-size: 1rem">Played by: {{ $player->username }}</span>
            </a>
        </h2>

        <div class="d-flex">
            <div class="faction">
                @if ($pgr->stats)
                    @php $playerStats2 = \App\Stats2::where("id", $pgr->stats->id)->first(); @endphp
                    @php $playerCountry = $playerStats2->faction($history->ladder->game, $pgr->stats->cty); @endphp
                    <div class="{{ $history->ladder->game }} player-faction player-faction-{{ $playerCountry }}"></div>
                @endif
            </div>
        </div>
    </div>
</div>

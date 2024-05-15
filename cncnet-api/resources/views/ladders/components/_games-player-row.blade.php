<div class="player-container">
    <div class="player-row">

        <div class="player-avatar" style="width:80px;height:80px;">
            <a href="{{ \App\Models\URLHelper::getPlayerProfileUrl($history, $playerGameReport->player->username) }}"
                title="View {{ $playerGameReport->player->username }}'s profile">

                <div class="avatar-with-faction">
                    @include('components.avatar', ['avatar' => $playerGameReport->player->user->getUserAvatar(), 'size' => 80])

                    <div class="faction mt-2" style="padding-left:0;">
                        @if ($playerGameReport->stats)
                            @php $playerStats2 = \App\Models\Stats2::where("id", $playerGameReport->stats->id)->first(); @endphp
                            @php $playerCountry = $playerStats2->faction($history->ladder->game, $playerGameReport->stats->cty); @endphp
                            <div class="{{ $history->ladder->game }} player-faction player-faction-{{ $playerCountry }}"></div>
                        @endif
                    </div>
                </div>
            </a>
        </div>

        <div class="player-username ms-5">
            <a href="{{ $profileUrl ?? '' }}" title="View {{ $playerGameReport->player->username }}'s profile">
                <p class="fw-bold mb-1">{{ $playerGameReport->player->username }}</p>
            </a>
            @if ($playerGameReport->stats)
                <div class="player-points d-flex">
                    @php $playerStats2 = \App\Models\Stats2::where("id", $playerGameReport->stats->id)->first(); @endphp
                    @php $playerCountry = $playerStats2->faction($history->ladder->game, $playerGameReport->stats->cty); @endphp

                    <div class="game-status status-{{ $playerGameReport->won ? 'won' : 'lost' }}">
                        <span class="status-text">
                            {{ $playerGameReport->won == true ? 'Won' : 'Lost' }}
                        </span>

                        <span class="points">
                            {{ $playerGameReport->points >= 0 ? "+$playerGameReport->points" : $playerGameReport->points }}
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </div>


</div>

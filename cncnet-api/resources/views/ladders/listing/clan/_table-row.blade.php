<div class="player-row rank-{{ $rank }}">
    <div class="player-profile d-flex d-lg-none">
        <div class="player-rank player-stat">
            #{{ $rank or 'Unranked' }}
        </div>
        <a class="player-avatar player-stat" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            @include('components.avatar', ['avatar' => $avatar, 'size' => 50])
        </a>
        <a class="player-username player-stat" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            <?php
            ($playername = $username) or '';
            $heart = '';
            if ($abbreviation == 'yr' && str_contains(strtolower($playername), 'irish')) {
                $heart = '🍀';
            }
            ?>

            @if ($rank == 1)
                {{ $playername }} <span style="color:gold;padding-left:0.5rem;"> {{ $heart }}</span>
            @else
                {{ $playername }} <span style="color:red;padding-left:0.5rem;"> {{ $heart }}</span>
            @endif
        </a>
    </div>

    <div class="player-profile d-none d-lg-flex">
        <div class="player-rank player-stat">
            #{{ $rank or 'Unranked' }}
        </div>

        <a class="player-avatar player-stat d-none d-lg-flex" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            @include('components.avatar', ['avatar' => $avatar, 'size' => 50])
        </a>

        <a class="player-country player-stat" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            @if ($mostPlayedFaction)
                <div class="{{ $game }} player-faction player-faction-{{ strtolower($mostPlayedFaction) }}"></div>
            @endif
        </a>
        <a class="player-username player-stat d-none d-lg-flex" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            <?php
            ($playername = $username) or '';
            $heart = '';
            if ($abbreviation == 'yr' && str_contains(strtolower($playername), 'irish')) {
                $heart = '🍀';
            }
            ?>

            @if ($rank == 1)
                {{ $playername }} <span style="color:gold;padding-left:0.5rem;"> {{ $heart }}</span>
            @else
                {{ $playername }} <span style="color:red;padding-left:0.5rem;"> {{ $heart }}</span>
            @endif
        </a>
    </div>

    <div class="player-profile-info">
        <div class="player-social">
            @if ($twitch)
                <a href="{{ $twitch }}"><i class="bi bi-twitch"></i></a>
            @endif
            @if ($youtube)
                <a href="{{ $youtube }}"<i class="bi bi-youtube"></i></a>
            @endif
            {{-- @if ($discord)
            <a href=" {{ $discord }}"><i class="fa fa-discord"></i></a>
            @endif --}}
        </div>
        <div class="player-points player-stat">{{ $points }} <span>points</span></div>
        <div class="player-wins player-stat">{{ $wins }} <span>won</span></div>
        <div class="player-losses player-stat">{{ $losses }} <span>lost</span></div>
        <div class="player-games player-stat">{{ $totalGames }} <span>games</span></div>
    </div>
    {{-- <a href="{{ $url }}" class="player-link">
        <i class="bi bi-chevron-right"></i>
    </a> --}}
</div>

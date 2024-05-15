@props([
    'history',
    'playerCache',
    'statsXOfTheDay',
    'ranks',
    'mostUsedFactions'
])
@php
    $playerOfTheDay = false;
    if (isset($statsXOfTheDay)) {
        $playerOfTheDay = $playerCache->player_name == $statsXOfTheDay->name;
    }
    $username = $playerCache->player_name . ($playerOfTheDay ? ' ' . \App\Helpers\SiteHelper::getEmojiByMonth() : '');
    $url = \App\Models\URLHelper::getPlayerProfileUrl($history, $playerCache->player_name);
    $avatar = $playerCache->player->user->getUserAvatar();
    $mostPlayedFaction = $mostUsedFactions[$playerCache->id] ?? '';
    $avatar = $playerCache->player->user->getUserAvatar();
    $twitch = $playerCache->player->user->getTwitchProfile();
    $youtube = $playerCache->player->user->getYouTubeProfile();
    $discord = $playerCache->player->user->getDiscordProfile();
    $ladderHasEnded = $history->hasEnded();

    $rank = $ranks[$playerCache->id] ?? 9999;
    $points = $playerCache->points;
    $wins = $playerCache->wins;
    $losses = $playerCache->games - $playerCache->wins;
    $totalGames = $playerCache->games;

    $emoji = '';
    if ($history->ladder->abbreviation == 'yr' && str_contains(strtolower($username ?? ''), 'baguette')) {
        #for zedd
        $emoji = '🥖';
    }
@endphp
<div id="js_profile_{{ $username }}" class="player-row rank-{{ $rank }}">
    <div class="player-profile d-flex d-lg-none">
        <div class="player-rank player-stat">#{{ $rank ?? 'Unranked' }}</div>
        <a class="player-avatar player-stat" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            @include('components.avatar', ['avatar' => $avatar, 'size' => 50])
        </a>
        <a class="player-username player-stat" href="{{ $url }}" title="Go to {{ $username }}'s profile">

            @if ($rank == 1)
                {{ $username }} <span style="color:gold;padding-left:0.5rem;"> {{ $emoji }}</span>
            @else
                {{ $username }} <span style="color:red;padding-left:0.5rem;"> {{ $emoji }}</span>
            @endif
        </a>
    </div>

    <div class="player-profile d-none d-lg-flex">
        <div class="player-rank player-stat">
            #{{ $rank ?? 'Unranked' }}
        </div>

        <a class="player-avatar player-stat d-none d-lg-flex" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            @include('components.avatar', ['avatar' => $avatar, 'size' => 50])
        </a>

        <a class="player-country player-stat" href="{{ $url }}" title="Go to {{ $username }}'s profile">
            @if ($mostPlayedFaction)
                <div class="{{ $history->ladder->game }} player-faction player-faction-{{ strtolower($mostPlayedFaction) }}"></div>
            @endif
        </a>
        <a class="player-username player-stat d-none d-lg-flex" href="{{ $url }}" title="Go to {{ $username }}'s profile">

            @if ($rank == 1)
                {{ $username }} <span style="color:gold;padding-left:0.5rem;"> {{ $emoji }}</span>
            @else
                {{ $username }} <span style="color:red;padding-left:0.5rem;"> {{ $emoji }}</span>
            @endif
        </a>
    </div>

    <div class="player-profile-info">
        <div class="player-social">
            @if ($twitch)
                <a href="{{ $twitch }}"><i class="bi bi-twitch"></i></a>
            @endif
            @if ($youtube)
                <a href="{{ $youtube }}"><i class="bi bi-youtube"></i></a>
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

    <a href="{{ $url }}" class="player-link">
        <i class="bi bi-chevron-right"></i>
    </a>

    <div id="js_template_{{ $username }}" style="display: none;" class="player-hover-card">
        <div class="player-info-row">
            <a class="player-avatar player-stat d-none d-lg-flex" href="{{ $url }}" title="Go to {{ $username }}'s profile">
                @include('components.avatar', ['avatar' => $avatar, 'size' => 100])
            </a>
        </div>
        <div class="player-info-row">
            <div class="player-info-col">
                <h5 class="title">Last 5 games</h5>
                <div class="last-five-games">
                    @@badges@@
                </div>
            </div>
            <div class="player-info-col">
                <h5 class="title">Last Online</h5>
                <div class="last-online">
                    @@last_active@@
                </div>
            </div>
        </div>
        <div class="player-info-row">
            <div class="player-info-col js-elo">
                <h5 class="title">Elo</h5>
                <div>
                    Elo: @@elo@@
                </div>
                <div>
                    Rank: #@@elo_rank@@
                </div>
                <div>
                    Games: @@elo_games@@
                </div>
                <div>
                    Deviation: @@elo_deviation@@
                </div>
            </div>
        </div>
        <div class="player-info-row">
            <div class="player-info-col js-joined">
                <h5 class="title">User Joined</h5>
                <div class="last-online">
                    @@user_since@@
                </div>
            </div>
        </div>
    </div>

    @if ($ladderHasEnded == false)
        <script>
            (function() {
                let trigger = document.getElementById("js_profile_{{ $username }}");
                let template = document.getElementById("js_template_{{ $username }}");
                let player = "{{ $username }}";
                let ladder = "{{ $history->ladder->abbreviation }}";

                const tippyInstance = tippy(trigger, {
                    allowHTML: true,
                    theme: "player-card",
                    placement: "auto-start",
                    touch: false,
                    maxWidth: 500,
                    interactive: true,
                    interactiveBorder: 10,
                    content: getLoadingContent(), // Initial loading state
                    onShow: () => fetchPlayer(),
                });

                // Create an object to hold the cached data
                const cache = {};

                function getLoadingContent() {
                    return `<div class="loading-message">Loading...</div>`;
                }

                function fetchPlayer() {
                    // Check if data is already in cache
                    if (cache[player]) {
                        // If cached data is available, update the tooltip content with it
                        updateTippyContent(cache[player]);
                    } else {
                        // If data is not in cache, make an API request to fetch the data
                        fetchDataAndDisplay(player, ladder);
                    }
                }

                async function fetchDataAndDisplay(playerId, ladderId) {
                    try {
                        const response = await fetch(`/api/v1/ladder/${ladderId}/player/${playerId}`);
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        const data = await response.json();

                        // Cache the fetched data
                        cache[player] = data;

                        // Update the Tippy.js tooltip content with the actual data
                        updateTippyContent(data);
                    } catch (error) {
                        // Display an error message if needed
                        tippyInstance.setContent(`<div class="error-message">Error loading data</div>`);
                    }
                }

                function updateTippyContent(data) {
                    // Create the badge divs dynamically
                    let badgesHTML = '';
                    for (let i = 0; i < data.last_five_games?.length; i++) {
                        const won = data.last_five_games[i].won;
                        badgesHTML += `<div class="badge ${won ? 'badge-won' : 'badge-lost'}">${won ? "W" : "L"}</div>`;
                    }

                    if (data.elo == null) {
                        template.querySelector(".js-elo").style.display = "none";
                    }

                    if (data.user_since == null) {
                        template.querySelector(".js-joined").style.display = "none";
                    }

                    // Replace the loading message with the actual badge divs and last_active value
                    let content = template.innerHTML
                        .replace("@@badges@@", badgesHTML)
                        .replace("@@last_active@@", data.last_active ?? "")
                        .replace("@@elo@@", data.elo?.elo ?? "")
                        .replace("@@elo_rank@@", data.elo?.rank ?? "")
                        .replace("@@elo_games@@", data.elo?.game_count ?? "")
                        .replace("@@elo_deviation@@", data.elo?.deviation ?? "")
                        .replace("@@user_since@@", data.user_since ?? "");

                    // Update Tippy.js content
                    tippyInstance.setContent(content);
                }
            })();
        </script>
    @endif
</div>

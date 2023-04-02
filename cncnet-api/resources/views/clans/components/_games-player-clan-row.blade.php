 <div class="player-container">

     <div class="player-row">
         <div class="ms-3 me-3">
             <a href="{{ $profileUrl }}" title="View {{ $playerGameReport->player->username }}'s profile">
                 @include('components.avatar', ['avatar' => $playerGameReport->player->user->getUserAvatar(), 'size' => 55])
             </a>
         </div>
         <div class="player-username">
             <p class="fw-bold mb-1">
                 Clan: <a href="{{ $clanProfileUrl }}" title="View clan {{ $clanName }}'s profile">{{ $clanName }}</a>
             </p>
             <p class="mb-1">
                 Player:
                 <a href="{{ $profileUrl or '' }}" title="View {{ $playerGameReport->player->username }}'s profile">
                     {{ $playerGameReport->player->username }}
                 </a>
             </p>
         </div>
     </div>

     @if ($playerGameReport->stats)
         <div class="player-points d-flex">
             @php $playerStats2 = \App\Stats2::where("id", $playerGameReport->stats->id)->first(); @endphp
             @php $playerCountry = $playerStats2->faction($history->ladder->game, $playerGameReport->stats->cty); @endphp
             <div class="{{ $history->ladder->game }} player-faction player-faction-{{ $playerCountry }}"></div>

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

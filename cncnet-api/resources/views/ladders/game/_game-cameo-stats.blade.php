<div class="stats-breakdown">
    @foreach ($playerGameReports as $k => $pgr)
        @php $gameStats = $pgr->stats; @endphp
        @php $player = $pgr->player; @endphp
        @php $playerCache = $player->playerCache($history->id);@endphp
        @php $playerRank = $playerCache ? $playerCache->rank() : 0; @endphp
        @php $playerGameClip = $player->gameClip($pgr->game_id); @endphp

        @php
            $pointReport = $pgr;
            if ($history->ladder->clans_allowed) {
                $pointReport = $pgr->gameReport->getPointReportByClan($pgr->clan_id);
            }
        @endphp

        @if ($gameStats !== null && $pointReport)
            @php $last_heap = 'Z'; @endphp

            <div class="stats {{ $pgr->points > 0 ? 'won' : 'lost' }}">
                <div class="mb-5">
                    @include('ladders.game._player-card', ['extraStats' => true])
                </div>

                @if ($playerGameClip)
                    <div class="mt-4 mb-4">
                        <h5>Game Recap</h5>
                        <video src="{{ Storage::url($playerGameClip->clip_filename) }}" style="height:360px; max-width:100%" autoplay muted controls>
                    </div>
                @endif

                @foreach ($heaps as $heap)
                    <div>
                        <div class="cameo-row cameo-type-{{ strtolower($heap->name) }}">
                            <div class="cameo-title">
                                <h5>{{ $heap->description }}</h5>
                            </div>
                            <div class="cameo-body">
                                @foreach ($gameStats->gameObjectCounts as $goc)
                                    @if ($goc->countableGameObject?->heap_name == $heap->name && $goc->countableGameObject->cameo != '')
                                        <div class="{{ $gameAbbreviation }}-cameo cameo-tile cameo-{{ $goc->countableGameObject->cameo }}">
                                            <span class="number">{{ $goc->count }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <?php $last_heap = substr($heap->name, 2, 1); ?>
                @endforeach
            </div>
        @endif
    @endforeach
</div>

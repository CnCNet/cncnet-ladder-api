<div class="stats-breakdown">
    @foreach ($playerGameReports as $k => $pgr)
        @php $gameStats = $pgr->stats; @endphp
        @php $player = $pgr->player()->first(); @endphp
        @php $playerCache = $player->playerCache($history->id);@endphp
        @php $playerRank = $playerCache ? $playerCache->rank() : 0; @endphp
        @php
            $pointReport = $pgr;
            if ($history->ladder->clans_allowed) {
                $pointReport = $pgr->gameReport->getPointReportByClan($pgr->clan_id);
            }
        @endphp


        @if ($gameStats !== null && $pointReport)
            @php $last_heap = 'Z'; @endphp

            <div class="stats {{ $pgr->won ? 'won' : 'lost' }}">
                <div class="mb-5">
                    @include('ladders.game._player-card', ['extraStats' => true])
                </div>

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

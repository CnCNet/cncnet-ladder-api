    <div class="row">
        <div class="header">
            <div class="col-md-12">
                <h5><strong>Ranked Match</strong> Stats</h5>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="qm-stats">
                <div class="stat green">
                    <div class="text-center">
                        <span class="material-symbols-outlined">
                            insights
                        </span>
                        <h5>Games Played</h5>
                    </div>
                    <div class="text-center">
                        <div class="value">{{ $stats['past24hMatches'] }} </div>
                        <div><small>(Last 24 hours)</small></div>
                    </div>
                </div>

                <div class="stat purple">
                    <div class="text-center">
                        <span class="material-symbols-outlined">
                            group
                        </span>
                        <h5>Queued Players</h5>
                    </div>
                    <div class="text-center">
                        <div class="value">{{ $stats['queuedPlayers'] }}</div>
                        <div><small>(Right now)</small></div>
                    </div>
                </div>

                <div class="stat blue">
                    <div class="text-center">
                        <span class="material-symbols-outlined">
                            swords
                        </span>
                        <h5>Recently Matched</h5>
                    </div>
                    <div class="text-center">
                        <div class="value">{{ $stats['recentMatchedPlayers'] }}</div>
                        <div><small>(Last 60 minutes)</small></div>
                    </div>
                </div>

                @if ($statsPlayerOfTheDay)
                    <?php $url = \App\URLHelper::getPlayerProfileUrl($history, $statsPlayerOfTheDay->username); ?>
                    <a class="stat gold potd" style="position:relative" href="{{ $url }}" title="{{ $statsPlayerOfTheDay->username }}">
                        <div class="text-center">
                            <div class="icon icon-crown pt-4">
                                @include('icons.crown', [
                                    'colour' => '#ffcd00',
                                ])
                            </div>
                            <h4>Player of the day</h4>
                        </div>
                        <div class="text-center" style="z-index:1;position:relative;">
                            <div class="value">{{ $statsPlayerOfTheDay->username }}</div>
                            <div><small>{{ $statsPlayerOfTheDay->wins }} wins <br />(Today)</small></div>
                        </div>
                        <div style="position: absolute; top: 0; left: 0;width:100%; z-index: 0;">
                            @include('animations.player', [
                                'src' => '/animations/confetti.json',
                                'loop' => 'false',
                                'width' => '100%',
                                'height' => '200px',
                            ])
                        </div>
                    </a>
                @endif
            </div>
        </div>
    </div>

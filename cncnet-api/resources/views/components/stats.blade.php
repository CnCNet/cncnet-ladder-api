<div class="feature">
    <div class="row">
        <div class="header">
            <div class="col-md-12">
            <h3><strong>Quick Match</strong> Stats</h3>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="qm-stats">
                <div class="stat green">
                    <div class="text-center">
                        <i class="fa fa-diamond fa-fw"></i>
                        <h4>Games Played</h4>
                    </div>
                    <div class="text-center">
                        <div class="value">{{ $stats["past24hMatches"]}} </div>
                        <div><small>(Last 24 hours)</small></div>
                    </div>
                </div>
                
                <div class="stat purple">
                    <div class="text-center">
                        <i class="fa fa-level-up fa-fw fa-lg"></i>
                        <h4>Queued Players</h4>
                    </div>
                    <div class="text-center">
                        <div class="value">{{ $stats["queuedPlayers"] }}</div>
                        <div><small>(Right now)</small></div>
                    </div>
                </div>

                <div class="stat blue">
                    <div class="text-center">
                        <i class="fa fa-industry fa-fw"></i>
                        <h4>Recently Matched</h4>
                    </div>
                    <div class="text-center">
                        <div class="value">{{ $stats["recentMatchedPlayers"] }}</div>
                        <div><small>(Last 60 minutes)</small></div>
                    </div>
                </div>

                @if($statsPlayerOfTheDay)

                <?php $url = \App\URLHelper::getPlayerProfileUrl($history, $statsPlayerOfTheDay->username); ?>
                <a class="stat gold" style="position:relative" href="{{ $url }}" title="{{ $statsPlayerOfTheDay->username }}">
                    <div class="text-center">
                        <div class="fa" style="width: 35px;">
                            @include("icons.crown", [
                                "colour" => "#ffcd00", 
                            ])
                        </div>
                        <h4>Player of the day</h4>
                    </div>
                    <div class="text-center" style="z-index:1;position:relative;">
                        <div class="value">{{ $statsPlayerOfTheDay->username }}</div>
                        <div><small>{{ $statsPlayerOfTheDay->wins }} wins <br/>(Today)</small></div>
                    </div>
                    <div style="position: absolute; top: 0; left: 0;width:100%; z-index: 0;">
                    @include("animations.player", [
                        "src" => "/animations/confetti.json", 
                        "loop" => "false",
                        "width" => "100%",
                        "height" => "200px"
                    ])
                    </div>
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
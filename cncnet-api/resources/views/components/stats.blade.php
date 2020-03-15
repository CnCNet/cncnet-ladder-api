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
            </div>
        </div>
    </div>
</div>
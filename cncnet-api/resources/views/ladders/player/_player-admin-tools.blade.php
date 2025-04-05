<div class="container">
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $message)
            <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <div class="d-flex">

        <button type="button" class="btn btn-secondary btn-size-md me-3" data-bs-toggle="modal" data-bs-target="#viewLadderNicks">
            Previous {{ $history->ladder->abbreviation }} Nicknames
        </button>
        <div class="modal fade" id="viewLadderNicks" tabIndex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Previous {{ $history->ladder->abbreviation }} Nicknames</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if($isAnonymous)
                        <p>User is anonymous</p>
                        @else
                        <ul>
                            @foreach ($ladderNicks as $ladderNick)
                            <li>{{ $ladderNick }}</li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if ($userIsMod)
        @include('ladders._modal-edit-player-name')
        <button type="button" class="btn btn-secondary btn-size-md me-3" id="editPlayerName" data-bs-toggle="modal"
            data-bs-target="#editPlayerName">
            Edit
            Player Name
        </button>
        @endif

        @if ($userIsMod)
        <div>
            <a href="/admin/moderate/{{ $ladderId }}/player/{{ $ladderPlayer->id }}" class="btn btn-secondary btn-size-md me-3">
                Moderation Actions
            </a>
        </div>
        @endif

        @if (isset($mod) && $mod->isLadderAdmin($player['ladder']))
        <button type="button" class="btn btn-secondary btn-size-md" data-bs-toggle="modal" data-bs-target="#submitLaundryService">
            Laundry Service
        </button>

        <div class="modal fade" id="submitLaundryService" tabIndex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Submit Laundry Service</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        @if (!$player->laundered($history))
                        <label>Are you sure you want to set all of {{ $player->username }}'s points to 0?</label>
                        @endif

                        <div style="display: inline-block" class="mt-2">
                            <form method="POST" action="/admin/moderate/{{ $player->ladder->id }}/player/{{ $player->id }}/laundry">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input name="player_id" type="hidden" value="{{ $player->id }}" />
                                <input name="ladderHistory_id" type="hidden" value="{{ $history->id }}" />
                                <button type="submit" name="submit" value="update" class="btn btn-danger btn-md">Launder</button>
                            </form>
                        </div>

                        @if ($player->laundered($history))
                        <div style="padding-top: 5px; display: inline-block" class="mt-2">
                            <form method="POST"
                                action="/admin/moderate/{{ $player->ladder->id }}/player/{{ $player->id }}/undoLaundry">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input name="player_id" type="hidden" value="{{ $player->id }}" />
                                <input name="ladderHistory_id" type="hidden" value="{{ $history->id }}" />
                                <button type="submit" name="submit" value="update" class="btn btn-primary btn-sm">Undo
                                    Launder</button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
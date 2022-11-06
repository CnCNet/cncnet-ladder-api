  <div class="player-alerts">
      @if (count($bans))
          <h3> Bans: </h3>
          <ul>
              @foreach ($bans as $ban)
                  <li>{{ $ban }}</li>
              @endforeach
          </ul>
      @endif
      @if (count($alerts))
          <h3> Alerts:</h3>
          <ul>
              @foreach ($alerts as $alert)
                  <li>{!! $alert->message !!}</li>
              @endforeach
          </ul>
      @endif
  </div>

  @if ($userIsMod)
      <div>
          <a href="/admin/moderate/{{ $ladderId }}/player/{{ $ladderPlayer->id }}" class="btn btn-sm btn-danger">Moderation Actions</a>
      </div>
  @endif

  @if (isset($mod) && $mod->isLadderAdmin($player['ladder']))
      <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#submitLaundryService">Laundry Service</button>

      <div class="modal fade" id="submitLaundryService" tabIndex="-1" role="dialog">
          <div class="modal-dialog modal-md" role="document">
              <div class="modal-content">
                  <div class="modal-header">
                      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                      <h3 class="modal-title">Submit Laundry Service</h3>
                  </div>
                  <div class="modal-body clearfix">
                      <div class="container-fluid">
                          <div class="row content">
                              <div class="col-md-12 player-box player-card list-inline">

                                  @if (!$player->laundered($history))
                                      <label>Are you sure you want to set all of {{ $player->username }}'s points to 0?</label>
                                  @endif

                                  <div style="display: inline-block">
                                      <form method="POST" action="/admin/moderate/{{ $player->ladder->id }}/player/{{ $player->id }}/laundry">
                                          <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                          <input name="player_id" type="hidden" value="{{ $player->id }}" />
                                          <input name="ladderHistory_id" type="hidden" value="{{ $history->id }}" />
                                          <button type="submit" name="submit" value="update" class="btn btn-danger btn-md">Launder</button>
                                      </form>
                                  </div>

                                  @if ($player->laundered($history))
                                      <div style="padding-top: 5px; display: inline-block">
                                          <form method="POST" action="/admin/moderate/{{ $player->ladder->id }}/player/{{ $player->id }}/undoLaundry">
                                              <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                              <input name="player_id" type="hidden" value="{{ $player->id }}" />
                                              <input name="ladderHistory_id" type="hidden" value="{{ $history->id }}" />
                                              <button type="submit" name="submit" value="update" class="btn btn-primary btn-sm">Undo Launder</button>
                                          </form>
                                      </div>
                                  @endif
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  @endif

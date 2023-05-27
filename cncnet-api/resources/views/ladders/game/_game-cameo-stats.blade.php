  <section class="game {{ $gameAbbreviation }} mt-5 mb-5">
      <div class="container-fluid max-container">
          <div class="stats-breakdown">
              @foreach ($playerGameReports as $k => $pgr)
                  @php $gameStats = $pgr->stats; @endphp
                  @php $player = $pgr->player()->first(); @endphp
                  @php
                      $pointReport = $pgr;
                      if ($history->ladder->clans_allowed) {
                          $pointReport = $pgr->gameReport->getPointReportByClan($pgr->clan_id);
                      }
                  @endphp


                  @if ($gameStats !== null)
                      @php $last_heap = 'Z'; @endphp

                      <div class="stats">
                          <div>
                              <div class="player-card text-center justify-content-center mb-2">
                                  <a href="{{ \App\URLHelper::getPlayerProfileUrl($history, $player->username) }}"
                                      title="View {{ $player->username }}'s profile">
                                      <div class="player-avatar mb-5">
                                          @include('components.avatar', ['avatar' => $player->user->getUserAvatar(), 'size' => 100])
                                      </div>
                                  </a>
                              </div>

                              <h2 class="username text-center mt-2">
                                  <a href="{{ \App\URLHelper::getPlayerProfileUrl($history, $player->username) }}"
                                      title="View {{ $player->username }}'s profile">
                                      {{ $player->username }}
                                  </a>
                              </h2>

                              <h4 class="text-center status text-uppercase status-{{ $pointReport->won ? 'won' : 'lost' }}">
                                  @if ($pointReport->won)
                                      Won
                                  @elseif($pointReport->draw)
                                      Draw
                                  @elseif($pointReport->disconnected)
                                      Disconnected
                                  @else
                                      Lost
                                  @endif
                              </h4>
                          </div>

                          <div class="text-center">
                              <strong>Funds Left: </strong> {{ $gameStats->crd }}
                          </div>

                          <div class="text-center mb-5">
                              @if ($pgr->stats)
                                  @php $playerStats2 = \App\Stats2::where("id", $pgr->stats->id)->first(); @endphp
                                  @php $playerCountry = $playerStats2->faction($history->ladder->game, $pgr->stats->cty); @endphp
                                  <div class="{{ $history->ladder->game }} player-faction player-faction-{{ $playerCountry }} m-auto mt-2 mb-2"></div>
                              @endif
                          </div>

                          @foreach ($heaps as $heap)
                              <div>
                                  <div class="cameo-row">
                                      <div class="cameo-title">
                                          <h4>{{ $heap->description }}</h4>
                                      </div>
                                      <div class="cameo-body">
                                          @foreach ($gameStats->gameObjectCounts as $goc)
                                              @if ($goc->countableGameObject->heap_name == $heap->name && $goc->countableGameObject->cameo != '')
                                                  <div
                                                      class="{{ $gameAbbreviation }}-cameo cameo-tile cameo-{{ $goc->countableGameObject->cameo }}">
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
      </div>
  </section>

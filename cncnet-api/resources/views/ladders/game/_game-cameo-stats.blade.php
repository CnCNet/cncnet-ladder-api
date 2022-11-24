  <section class="game {{ $gameAbbreviation }} mt-5 mb-5">
      <div class="container">
          <div class="stats-breakdown">
              @foreach ($playerGameReports as $k => $pgr)
                  @php $gameStats = $pgr->stats; @endphp
                  @php $player = $pgr->player()->first(); @endphp

                  @if ($gameStats !== null)
                      @php $last_heap = 'Z'; @endphp

                      <div class="stats">
                          <div>
                              <div class="player-card text-center justify-content-center mb-2">
                                  <a href="{{ \App\URLHelper::getPlayerProfileUrl($history, $player->username) }}" title="View {{ $player->username }}'s profile">
                                      <div class="player-avatar">
                                          @include('components.avatar', ['avatar' => $player->user->getUserAvatar(), 'size' => 150])
                                      </div>
                                  </a>
                              </div>
                              <h2 class="username text-center">
                                  {{ $player->username }}
                              </h2>
                              <h3 class="text-center status text-uppercase status-{{ $pgr->won ? 'won' : 'lost' }}">
                                  @if ($pgr->won)
                                      Won
                                  @elseif($pgr->draw)
                                      Draw
                                  @elseif($pgr->disconnected)
                                      Disconnected
                                  @else
                                      Lost
                                  @endif
                              </h3>
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
                                                  <div class="{{ $gameAbbreviation }}-cameo cameo-tile cameo-{{ $goc->countableGameObject->cameo }}"><span class="number">{{ $goc->count }}</span>
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

{{--
    Per-player replay downloads. Every participant has their own file: the replay stores that
    player's viewport and unit selection, so each one plays back from that player's point of view.

    Hiding the buttons here is presentation only - ReplayController enforces access on the download
    route itself.
--}}
@php
    $replays = collect($playerGameReports)
        ->filter(fn($pgr) => $pgr->playerReplay !== null)
        ->sortBy(fn($pgr) => $pgr->player->username);

    $canDownloadReplays = $history->ladder->allowedToDownloadReplays(\Auth::user());
    $integrity = $replayIntegrity ?? null;
    // Staff only: GetGameDetailAction reads it for nobody else.
    $chat = $replayChat ?? null;
    $chatTime = function ($seconds) {
        $seconds = (int) $seconds;
        return $seconds >= 3600
            ? sprintf('%d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds, 60) % 60, $seconds % 60)
            : sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    };
    $frameTime = function (int $frame) {
        // Seals are 900 frames apart; at the usual 60 frames a second that is 15 seconds of game.
        $seconds = intdiv($frame, 60);
        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    };
@endphp

@if ($canDownloadReplays && ($replays->isNotEmpty() || $integrity !== null || $chat !== null))
    <div class="container mt-3 mb-5">
        <h5>Replays</h5>
        <p class="text-muted" style="font-size: 0.9em;">
            One file per player, each recorded from that player's point of view.
        </p>

        {{-- Every copy of one game carries the same canonical seals, so copies that disagree were
             edited or desynced. The seals outlive the files, so this stays after they are evicted. --}}
        @if ($integrity !== null)
            @if ($integrity['agree'] && $integrity['checkedTo'] >= 0)
                <p class="replay-integrity replay-integrity-ok">
                    All {{ $integrity['copies'] }} players' replays are the same game
                    <span class="text-muted">&ndash; checked to {{ $frameTime($integrity['checkedTo'] + 1) }}</span>
                </p>
            @else
                @foreach ($integrity['editedNames'] as $name)
                    <p class="replay-integrity replay-integrity-bad">
                        {{ $name }}'s replay was changed after the game
                        <span class="text-muted">&ndash; its contents no longer match the seals it was recorded with</span>
                    </p>
                @endforeach
                @foreach ($integrity['differences'] as $difference)
                    <p class="replay-integrity replay-integrity-bad">
                        {{ $difference['names'][0] }}'s and {{ $difference['names'][1] }}'s replays part ways
                        at about {{ $frameTime($difference['frame']) }}
                        <span class="text-muted">&ndash; one was edited, or the game went out of sync there</span>
                    </p>
                @endforeach
            @endif
        @endif

        @foreach ($replays as $pgr)
            @php $replay = $pgr->playerReplay; @endphp
            <a class="btn btn-outline-secondary mb-2 me-2"
               href="{{ route('replay.download', $replay->id) }}"
               title="Uploaded {{ $replay->created_at }} &middot; {{ number_format($replay->file_size / 1024, 0) }} KB">
                Download replay &ndash; {{ $pgr->player->username }}
            </a>
        @endforeach

        {{-- Staff only: everything said in the game. Each replay holds only what its player sent and
             received, so the lines are merged from every uploaded copy, each once. --}}
        @if ($chat !== null)
            <div class="replay-chat">
                @if (count($chat['lines']) > 0)
                    <a class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" href="#replayChat"
                       role="button" aria-expanded="false" aria-controls="replayChat">
                        Chat &ndash; {{ count($chat['lines']) }} {{ count($chat['lines']) === 1 ? 'message' : 'messages' }}
                    </a>
                @else
                    <span>No chat</span>
                @endif
                <span class="text-muted">
                    from {{ $chat['copies'] === 1 ? "1 player's replay" : "{$chat['copies']} players' replays" }} &middot; staff only
                </span>

                @if (count($chat['lines']) > 0)
                    <div class="collapse" id="replayChat">
                        <ol class="replay-chat-lines">
                            @foreach ($chat['lines'] as $line)
                                <li class="ri-colour-{{ $line['color'] }}">
                                    <span class="replay-chat-time" title="Frame {{ number_format($line['f']) }}">{{ $chatTime($line['t']) }}</span>
                                    <span class="replay-chat-name">{{ $line['name'] }}</span>@if ($line['label'] !== null) <span class="replay-chat-label">({{ $line['label'] }})</span>@endif<span class="replay-chat-label">:</span>
                                    <span class="replay-chat-text">{{ $line['text'] }}</span>
                                    @if (!$line['verified'])
                                        <span class="replay-chat-unverified"
                                              title="No unedited copy of the game carries this line's fingerprint: it was added to a replay after the game, or recorded by a spawner too old to fingerprint chat.">unverified</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endif

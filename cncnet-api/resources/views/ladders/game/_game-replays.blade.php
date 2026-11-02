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
    $frameTime = function (int $frame) {
        // Seals are 900 frames apart; at the usual 60 frames a second that is 15 seconds of game.
        $seconds = intdiv($frame, 60);
        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    };
@endphp

@if ($canDownloadReplays && ($replays->isNotEmpty() || $integrity !== null))
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
    </div>
@endif

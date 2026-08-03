{{--
    Per-player replay downloads.

    Every participant has their own file: the replay stores that player's viewport and unit
    selection, so each one plays back from that player's point of view.

    Access is staff-only during the beta - see config/replays.php ('staff_only_downloads') and
    ReplayController, which enforces it on the download route itself. Hiding the buttons here is
    presentation only, not the access control.
--}}
@php
    $replays = collect($playerGameReports)
        ->filter(fn($pgr) => $pgr->playerReplay !== null)
        ->sortBy(fn($pgr) => $pgr->player->username);
@endphp

@if ($replays->isNotEmpty())
    <div class="container mt-3 mb-5">
        <h5>Replays</h5>
        <p class="text-muted" style="font-size: 0.9em;">
            One file per player, each recorded from that player's point of view. Visible to staff only.
        </p>

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

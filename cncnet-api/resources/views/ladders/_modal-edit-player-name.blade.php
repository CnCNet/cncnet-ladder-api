<div class="modal fade" id="editPlayerName" tabIndex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Player Name</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="/admin/moderate/{{ $player->ladder->id }}/player/{{ $player->id }}/editName">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                    <input type="hidden" name="history_id" value="{{ $history->id }}">
                    <input type="hidden" name="player_id" value="{{ $player->id }}" />

                    <div class="form-group">
                        <label for="player_name"> Player Name </label>
                        <input type="text" id="player_name" name="player_name" value="" class="form-control border" />
                        <button type="submit" class="btn btn-primary mt-2">Edit Name</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
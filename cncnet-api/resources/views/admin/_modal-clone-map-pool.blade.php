<div class="modal fade" id="cloneMapPool" tabIndex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clone Map Pool</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="mappool/clone">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                    <input type="hidden" name="ladder_id" value="{{ $ladder->id }}" />
                    <input type="hidden" name="map_pool_id" value="" id="cloneMapPoolId">
                    <div class="form-group">
                        <label for="map_pool_name"> Map Pool</label>
                        <input type="text" id="map_pool_name" name="name" value="" class="form-control border" />
                        <button type="submit" class="btn btn-primary mt-2">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editMapPool" tabIndex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Map Pool</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <form method="POST" action="remove" onsubmit="return confirm('This action will delete the whole map pool and all maps permanently.');">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                    <button type="submit" class="btn btn-danger btn-md">Delete</button>
                </form>
                <form method="POST" action="rename">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />

                    <div class="form-group mb-2">
                        <label for="map_pool_name"> Pool Name </label>
                        <input type="text" id="map_pool_name" name="name" size="100" value="{{ $mapPool->name }}" class="form-control border" />
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-md">Rename</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

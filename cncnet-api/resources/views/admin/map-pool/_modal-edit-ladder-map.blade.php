<div class="modal fade modal-lg" id="editLadderMap" tabIndex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Map Hashes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <label>Show disabled maps</label>
                <input name="show_disabled" type="checkbox" />

                <form method="POST" action="remmap">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                    <input type="hidden" name="ladder_id" value="{{ $ladder->id }}" />
                    <p style="color: #fff">Map Hashes</p>

                    <select id="ladderMapSelector" name="map_id" size="6" class="form-control map_pool">
                       
                    </select>
                </form>

                <form method="POST" action="../../editmap" class="map" id="ladderMapEdit" enctype="multipart/form-data">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                    <input type="hidden" name="ladder_id" value="{{ $ladder->id }}" />
                    <input type="hidden" id="ladderMapId" name="map_id" value="new" />
                    <input type="hidden" id="mapSelected" name="map_selected" value="" />

                    <div class="form-group">
                        <label for="text_map_new_name"> Name </label>
                        <input type="text" id="ladderMapName" name="name" value="" class="form-control" />
                    </div>

                    <div class="form-group">
                        <label for="ladderMapImage"> Upload Image </label>
                        <input type="file" name="mapImage" id="ladderMapImage" />
                    </div>

                    <div class="form-group">
                        <label for="ladderMapFile"> Upload Map File (.map / .mpr) </label>
                        <input type="file" name="mapFile" id="ladderMapFile" />
                    </div>

                    <div class="form-group">
                        <label>Active map</label>
                        <input name="is_active" id="is_active" type="checkbox" class="is_active" checked />
                    </div>

                    <button type="submit" class="btn btn-primary">Update</button>
                </form>

                <div class="col-md-6">
                    <div class="player-card" id="thumbnailContainer">
                        <img class="img-fluid img-thumbnail" id="ladderMapThumbnail" src="">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


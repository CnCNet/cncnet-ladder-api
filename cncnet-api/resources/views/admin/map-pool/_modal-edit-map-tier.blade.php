<div class="modal fade" id="mapTierModal" tabindex="-1" role="dialog" aria-labelledby="mapTierModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="mapTierModalLabel">Map Tiers</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <select id="mapTierSelector" name="map_tier" size="6" class="form-control mt-2 mb-2">
                    @foreach ($mapTiers as $mapTier)
                        <option value="{{ $mapTier->tier }}">
                            {{ $mapTier->name }}
                        </option>
                    @endforeach

                    <?php
                    $new_map_tier = new \App\Models\MapTier();
                    $new_map_tier->tier = -1;
                    $new_map_tier->map_pool_id = $mapPool->id;
                    $new_map_tier->name = 'New Map Tier';
                    $new_map_tier->max_vetoes = 0;
                    $mapTiers->push($new_map_tier);
                    ?>
                    <option value="{{ $new_map_tier->tier }}">&lt;New Map Tier></option>
                </select>

                <!-- A form to add new map tiers -->
                @foreach ($mapTiers as $mapTier)
                    <form method="POST" id="{{$mapTier->tier}}_addMapTierForm" val="{{$mapTier->tier}}"
                          class="mapTierForm" action="editMapTier">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                        <input type="hidden" name="map_pool_id" value="{{ $mapPool->id }}"/>

                        <div class="form-group">
                            <label for="tier">Map Tier</label>
                            <input type="number" class="form-control border" id="tier" name="tier" min=1
                                   value="{{ $mapTier->tier }}">
                        </div>
                        <div class="form-group">
                            <label for="name">Map Tier Name</label>
                            <input type="text" class="form-control border" id="name" name="name"
                                   value="{{ $mapTier->name }}">
                        </div>
                        <div class="form-group">
                            <label for="max_vetoes">Veto Count</label>
                            <input type="number" class="form-control border" id="max_vetoes" name="max_vetoes" min="0"
                                   value="{{ $mapTier->max_vetoes }}">
                        </div>
                        <button type="submit" class="btn btn-primary" id="editMapTier">Save Map Tier</button>
                    </form>
                @endforeach

                <!-- A form to remove a map tier -->
                @foreach ($mapTiers as $mapTier)
                    <form method="POST" id="{{$mapTier->tier}}_deleteMapTierForm" val="{{$mapTier->tier}}"
                          class="mapTierForm" action="deleteMapTier">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                        <input type="hidden" name="map_pool_id" value="{{ $mapPool->id }}"/>
                        <input type="hidden" id="tier" name="tier" value="{{ $mapTier->tier }}"/>
                        <button type="submit" class="btn btn-danger" id="deleteMapTier">Remove Map Tier</button>
                    </form>
                @endforeach

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
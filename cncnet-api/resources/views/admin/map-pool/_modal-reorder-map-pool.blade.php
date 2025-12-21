<div class="modal fade" id="reorderMapPool" tabIndex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reorder the Map Pool</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-12 player-box player-card" style="padding:8px;margin:8px;">
                            <form method="POST" action="reorder">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                <input type="hidden" id="mapPoolId" name="id" value="{{ $mapPool->id }}" />
                                <ul id="mapList" style="overflow: auto;max-height: calc(100vh - 300px); list-style: none; margin: 0; padding: 0;">
                                    @foreach ($qmMaps as $qmMap)
                                        @if ($qmMap->id != 'new')
                                            <li class="map-in-list" value="{{ $qmMap->bit_idx }}">
                                                <input type="radio" id="rinput_idx_{{ $qmMap->bit_idx }}" name="maphandle" value="{{ $qmMap->id }},{{ $qmMap->bit_idx }},{{$qmMap->map_tier}}" class="maphandle">
                                                </radio>
                                                <label id="linput_idx_{{ $qmMap->bit_idx }}" for="rinput_idx_{{ $qmMap->bit_idx }}" style="margin-bottom: 0;">{{ $qmMap->admin_description }}</label>
                                                <input type="hidden" id="input_idx_{{ $qmMap->bit_idx }}" name="bit_idx_{{ $qmMap->bit_idx }}" value="{{ $qmMap->id }}" />
                                            </li>
                                        @endif
                                    @endforeach
                        </div>
                        <div style="margin-top: 8px;">
                            <a href="#reorderMapPool" class="move-up btn btn-secondary">
                                <i class="bi bi-arrow-up-circle-fill"></i>
                            </a>
                            <a href="#reorderMapPool" class="move-down btn btn-secondary">
                                <i class="bi bi-arrow-down-circle-fill"></i>
                            </a>
                            <a href="#reorderMapPool" class="alphabetical-sort btn btn-danger"><span>A-Z</span></a>
                            <a href="#reorderMapPool" class="map_tier-sort btn btn-danger"><span>Map Tier</span></a>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

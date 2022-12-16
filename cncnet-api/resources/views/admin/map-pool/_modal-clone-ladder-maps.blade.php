 <div class="modal fade" id="cloneLadderMaps" tabIndex="-1" role="dialog">
     <div class="modal-dialog">
         <div class="modal-content">
             <div class="modal-header">
                 <h5 class="modal-title">Seed the Map Pool</h5>
                 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
             </div>
             <div class="modal-body">
                 <form method="POST" action="cloneladdermaps">
                     <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                     <input type="hidden" name="ladder_id" value="{{ $ladder->id }}">
                     <div class="form-group">
                         <label for="cloneSelector">Clone From</label>
                         <select name="clone_ladder_id" id="cloneSelector" class="form-control">
                             @foreach ($allLadders as $ladder)
                                 <option value="{{ $ladder->id }}">{{ $ladder->name }}</option>
                             @endforeach
                         </select>
                     </div>
                     <button type="submit" value="clone" name="submit" class="btn btn-lg btn-primary">Clone</button>
                 </form>
             </div>
         </div>
     </div>
 </div>

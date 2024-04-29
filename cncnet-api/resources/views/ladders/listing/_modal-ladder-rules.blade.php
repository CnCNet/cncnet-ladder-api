 <div class="modal fade modal-lg" id="openLadderRules" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
     <div class="modal-dialog">
         <div class="modal-content">
             <div class="modal-header">
                 <h1 class="modal-title fs-5" id="exampleModalLabel">
                     {{ $history->ladder->name }} Ladder Rules
                 </h1>
                 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
             </div>
             <div class="modal-body">

                 <?php
                 $message = $history->ladder->qmLadderRules->ladder_rules_message;
                 $lines = explode("\n", $message);
                 ?>
                 <ul class="list-unstyled ps-2 pe-2">
                     @foreach ($lines as $line)
                         <li>{{ $line }} </li>
                     @endforeach
                 </ul>
             </div>
             <div class="modal-footer">
                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
             </div>
         </div>
     </div>
 </div>

 <div class="modal fade" id="openLadderRules" tabIndex="-1" role="dialog">
     <div class="modal-dialog modal-md" role="document">
         <div class="modal-content">
             <div class="modal-header">
                 <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                 <h3 class="modal-title"> {{ $history->ladder->name }} Ladder Rules </h3>
             </div>
             <div class="modal-body clearfix">
                 <div class="row">
                     <div class="col-md-12">
                         {{ $history->ladder->qmLadderRules->ladder_rules_message }}
                     </div>
                 </div>
             </div>
         </div>
     </div>
 </div>

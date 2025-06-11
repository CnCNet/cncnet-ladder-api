<div class="modal fade" id="editAlert" tabIndex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QM Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="alert">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                    <input type="hidden" id="alertId" name="id" value="" />
                    <input type="hidden" name="ladder_id" value="{{ $ladder->id }}" />

                    <div class="form-group">
                        <label for="alertText">Alert Text</label>
                        <textarea id="alertText" name="message" class="form-control border" rows="4" cols="50"> @if(isset($alert) && $alert != null) {{ $alert->message }} @endif</textarea>
                    </div>

                    <div class="form-group">
                        <label for="alertDate">Expiration</label>
                        <input type="text" id="alertDate" name="expires_at" class="form-control border"></input>
                    </div>
                    <button type="submit" name="submit" value="update" class="btn btn-primary mt-2">Save</button>
                    <button type="submit" name="submit" value="delete" class="btn btn-danger mt-2">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
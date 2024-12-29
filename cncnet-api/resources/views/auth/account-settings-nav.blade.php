<div class="mt-4 mb-4 pt-4">
    <h2>
        <span class="material-symbols-outlined icon pe-2">
            settings
        </span>
        Manage Account Settings
    </h2>
</div>

<a href="/account" class="btn btn-outline-primary {{ request()->is('account') ? 'btn-outline-secondary ' : '' }} me-3" }}>
    Manage Ladder Account
</a>
<a href="/account/settings" class="btn btn-outline-primary {{ request()->is('account/settings') ? 'btn-outline-secondary ' : '' }}">
    Ladder Account Settings
</a>

<div>
    <button type="button" class="btn btn-secondary btn-size-md me-3" data-bs-toggle="modal" data-bs-target="#deleteAccount">
        Delete Your Account
    </button>

    <div class="modal fade" id="deleteAccount" tabIndex="-1" role="dialog" aria-labelledby="deleteAccountLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteAccountLabel">Are you sure?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Deleting your account is permanent and cannot be undone. Are you sure you want to proceed?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    
                    <form action="/delete" method="POST" style="display: inline;">
                        @csrf
                        <input type="hidden" name="confirm_delete" value="true">
                        <button type="submit" class="btn btn-danger">Delete Account</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

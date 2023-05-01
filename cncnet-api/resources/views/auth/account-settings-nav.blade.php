<div class="mt-4 mb-4 pt-4">
    <h2>
        <span class="material-symbols-outlined icon pe-2">
            settings
        </span>
        Manage Account Settings
    </h2>
</div>

@if (isset($userSettings))
    <a href="/account" class="btn btn-outline {{ request()->is('account') ? 'active' : '' }} me-3" }}>Manage Ladder Account</a>
    <a href="/account/settings" class="btn btn-outline {{ request()->is('account/settings') ? 'active' : '' }}">Ladder Account Settings</a>
@endif

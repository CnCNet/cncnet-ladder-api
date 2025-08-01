@extends('layouts.app')
@section('title', 'Ladder Users')

@section('feature')
<x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
    <x-slot name="title">Edit {{ $user->name }}</x-slot>
    <x-slot name="description">
        View and manage ladder users
    </x-slot>

    <div class="mini-breadcrumb d-none d-lg-flex">
        <div class="mini-breadcrumb-item">
            <a href="/" class="">
                <span class="material-symbols-outlined">
                    home
                </span>
            </a>
        </div>
        <div class="mini-breadcrumb-item">
            <a href="/admin" class="">
                <span class="material-symbols-outlined">
                    admin_panel_settings
                </span>
            </a>
        </div>
    </div>
</x-hero-with-video>
@endsection

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12 mt-5 mb-5">
            <h2 class="mb-2">Edit {{ $user->name }}'s profile</h2>
            <h5>Email: {{ $user->email }}</h5>
            <h5>User ID: <a href="{{ route('admin.users', ['userId' => $user->id]) }}">#{{ $user->id }}</a></h5>
            <h5>Account type: {{ $user->accountType() }}</h5>
            <div class="user-info" id="settings">
                <h2>Basic user settings</h2>
                <form method="POST" action="/admin/users/edit/{{ $user->id }}">
                    {{ csrf_field() }}
                    <input type="hidden" name="userId" value="{{ $user->id }}" />

                    @include('components.avatar', ['avatar' => $user->getUserAvatar()])

                    <p class="mt-2 mb-2">
                        <label>
                            <input id="removeUserAvatar" type="checkbox" name="removeUserAvatar" />
                            Remove user avatar
                        </label>
                    </p>

                    <p class="mt-2 mb-2">
                        <label>
                            <input id="removeUserEmoji" type="checkbox" name="removeUserEmoji" />
                            Remove user emoji
                        </label>
                    </p>

                    <p class="mt-2 mb-2">
                        <label>
                            <input id="restrictAvatarUpload" type="checkbox" name="restrictAvatarUpload"
                                {{ $user->getIsAllowedToUploadAvatarOrEmoji() ? 'checked' : '' }} />
                            User is allowed to upload an Avatar or add an Emoji?
                        </label>
                    </p>

                    <p class="mt-2 mb-2">
                        <label>
                            <input id="userAllowedToChat" type="checkbox" name="userAllowedToChat" {{ $user->getIsAllowedToChat() ? 'checked' : '' }} />
                            User allowed to chat in game?
                        </label>
                    </p>

                    <p class="mt-2 mb-2">
                        <input id="can_observe" type="checkbox" name="can_observe"
                            {{ $user->isObserver() ? 'checked' : '' }}
                            {{ $user->isModerator() ? 'disabled' : '' }} />
                        <label for="can_observe">User can observe QM matches?</label>
                    </p>

                    <p class="mt-2 mb-2">
                        <label>
                            <input id="disabledPointFilter" type="checkbox" name="disabledPointFilter" {{ $user->userSettings->disabledPointFilter ? 'checked' : '' }} />
                            disabledPointFilter
                        </label>
                    </p>

                    <p class="mt-2 mb-2">
                        <label>
                            <input id="do_not_match_yuri" type="checkbox" name="do_not_match_yuri" {{ $user->userSettings->do_not_match_yuri ? 'checked' : '' }} />
                            do_not_match_yuri
                        </label>
                    </p>

                    <p class="mt-2 mb-2">
                        <label>
                            <input id="is_anonymous" type="checkbox" name="is_anonymous"
                                {{ $user->userSettings->is_anonymous && !$user->isModerator() ? 'checked' : '' }}
                                {{ $user->isModerator() ? 'disabled' : '' }} />
                            is_anonymous
                        </label>
                    </p>

                    <p class="mt-2 mb-2">
                        <label>
                            <input type="checkbox" id="allow_2v2_ladders" name="allow_2v2_ladders" {{ $user->userSettings->allow_2v2_ladders ? 'checked' : '' }} />
                            Allowed to play 2v2 ladders
                        </label>
                    </p>

                    <p class="mt-2 mb-2">
                        <label>
                            Update Alias <input type="text" id="alias" name="alias" class="form-control border"
                                value="{{ $user->alias }}" />
                        </label>
                    </p>

                    <button class="btn btn-primary mt-2">Update User</button>

                    @error('alias')
                    <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </form>
            </div>

            <div id="duplicate_handling" class="user-info">
                <h2>Duplicate handling</h2>

                <!-- Duplicate logic takes care of resetting everything, but in case of failures, this button can help -->
                @if ($user->isConfirmedPrimary() && empty($user->alias) && !$user->hasDuplicates())
                    <form action="/admin/users/duplicates/resetprimary" method="POST" style="display:inline-block;">
                        @csrf
                        <div class="d-flex align-items-center gap-2">
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <button class="btn btn-sm btn-outline-success">Reset to unconfirmed primary account</button>
                        </div>
                    </form>
                @endif

                @foreach (['confirmed' => 'Confirmed', 'unconfirmed' => 'Possible', 'rejected' => 'Rejected'] as $key => $label)
                    @if ($duplicates[$key]->isNotEmpty())
                        <h4 class="mt-4">{{ $label }} duplicates:</h4>
                        <ul class="list-group">
                            @foreach ($duplicates[$key] as $entry)
                                @php
                                    $dupe = $entry['user'];
                                    $reason = $entry['reason'];
                                    $displayName = $dupe->alias ?: $dupe->name ?: 'User #' . $dupe->id;
                                @endphp

                                <li>
                                    <a href="{{ route('admin.edit-user', ['userId' => $dupe->id]) }}">#{{ $dupe->id }} {{ $displayName }} - {{ $dupe->email }}</a>
                                    @if ($dupe->isConfirmedPrimary())
                                        <strong>(Primary account)</strong>
                                    @endif
                                    <span class="status-box {{ $key }}">{{ $reason }}</span>

                                    @if ($key === 'unconfirmed')
                                        <form action="/admin/users/duplicates/confirm" method="POST" style="display:inline-block;">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                                            <input type="hidden" name="duplicate_user_id" value="{{ $dupe->id }}">
                                            <button class="btn btn-sm btn-outline-success">Confirm</button>
                                        </form>
                                    @elseif ($key === 'confirmed')
                                        <form action="/admin/users/duplicates/unlink" method="POST" style="display:inline-block;">
                                            @csrf
                                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                                            <input type="hidden" name="duplicate_user_id" value="{{ $dupe->id }}">
                                            <button class="btn btn-sm btn-outline-success">Unlink</button>
                                        </form>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endforeach

                <h4 class="mt-4">Add duplicate manually:</h4>
                <form action="/admin/users/duplicates/confirm" method="POST" style="display:inline-block;"
                    onsubmit="return confirm('Are you really sure that the given user is a duplicate of {{ $user->id }}?');">
                    @csrf
                    <div class="d-flex align-items-center gap-2">
                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                        <input type="text" name="duplicate_user_id" class="form-control border" value="" />
                        <button class="btn btn-sm btn-outline-success">Add</button>
                    </div>
                </form>

                @if (session('status'))
                    <div id="message_box" class="alert alert-success mt-2">
                        {{ session('status') }}
                    </div>
                @endif

                @error('duplicate_action')
                    <div id="message_box" class="alert alert-danger mt-2">
                        {{ $message }}
                    </div>
                @enderror

                @error('duplicate_user_id')
                    <div id="message_box" class="alert alert-danger mt-2">The given user id is invalid or does not exist.</div>
                @enderror
            </div>
        </div>
    </div>
</div>
@endsection

<style>
    .status-box {
        display: inline-block;
        padding: 1px 10px;
        border-radius: 999px;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .status-box.confirmed {
        background-color: #14532d; /* dark green */
        color: #bbf7d0; /* light green */
    }

    .status-box.unconfirmed {
        background-color: #2e2e2e; /* dark gray */
        color: #cfcfcf; /* light gray */
    }

    .status-box.rejected {
        background-color: #7f1d1d; /* dark red */
        color: #fca5a5;            /* light red */
    }

    .user-info {
        margin-bottom: 50px;
        background: #1e212d;
        border-radius: 4px;
        padding: 2rem;
    }

    .user-info ul.list-group {
        padding-left: 0;
        list-style-position: inside;
        margin-left: 1.5rem;
    }
</style>

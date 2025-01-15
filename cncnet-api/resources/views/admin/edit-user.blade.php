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
                <p>Email: {{ $user->email }}</p>

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
                            <input id="is_anonymous" type="checkbox" name="is_anonymous" {{ $user->userSettings->is_anonymous ? 'checked' : '' }} />
                            is_anonymous
                        </label>
                    </p>

                    <p class="mt-2 mb-2">
                        <label>
                            Update Alias <input type="text" id="alias" type="checkbox" name="alias" class="form-control border"
                                value="{{ $user->alias }}" />
                        </label>
                    </p>
                    <button class="btn btn-primary mt-2">Update User</button>
                </form>
            </div>
        </div>
    </div>
@endsection

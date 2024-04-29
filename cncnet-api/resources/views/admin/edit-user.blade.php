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
                            <input id="restrictAvatarUpload" type="checkbox" name="restrictAvatarUpload"
                                {{ $user->getIsAllowedToUploadAvatar() ? 'checked' : '' }} />
                            User allowed to upload Avatar?
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

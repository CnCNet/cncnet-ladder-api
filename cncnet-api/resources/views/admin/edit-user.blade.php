@extends('layouts.app')
@section('title', 'Ladder Users')

@section('feature-image', '/images/feature/feature-index.jpg')
@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">CnCNet</strong>
                        <span>Ladder Admin</span>
                    </h1>
                    <p>Editing {{ $user->name }}</p>
                </div>
            </div>
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
        </div>
    </div>
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

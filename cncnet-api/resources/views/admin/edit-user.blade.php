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
        </div>
    </div>
@endsection

@section('content')
    <div class="admin-users">
        <section class="light-texture game-detail supported-games">
            <div class="container">
                <div class="feature">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="players">
                                <h2>Edit {{ $user->name }}'s profile</h2>

                                <div class="search" style="margin-bottom: 15px; max-width: 500px">
                                    <form method="POST" action="/admin/users/edit/{{ $user->id }}">
                                        {{ csrf_field() }}
                                        <input type="hidden" name="userId" value="{{ $user->id }}" />

                                        @include('components.avatar', ['avatar' => $user->getUserAvatar()])

                                        <p>
                                            <label>
                                                <input id="removeUserAvatar" type="checkbox" name="removeUserAvatar" />
                                                Remove user avatar
                                            </label>
                                        </p>

                                        <p>
                                            <label>
                                                <input id="restrictAvatarUpload" type="checkbox" name="restrictAvatarUpload" {{ $user->getIsAllowedToUploadAvatar() ? 'checked' : '' }} />
                                                User allowed to upload Avatar?
                                            </label>
                                        </p>
                                        <button class="btn btn-primary">Update</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

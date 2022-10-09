@extends('layouts.app')
@section('title', 'Ladder Users')

@section('cover')
/images/feature/feature-td.jpg
@endsection

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    CnCNet Admin
                </h1>
                <p>Editing {{ $user->name}}</p>

                <a href="/admin/" class="btn btn-transparent btn-lg">
                    <i class="fa fa-chevron-left fa-lg fa-fw" aria-hidden="true"></i> Back to Admin
                </a>
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
                            <h2>Edit {{ $user->name}}'s profile</h2>
                          
                            <div class="search" style="margin-bottom: 15px; max-width: 500px">
                                <form method="POST" action="/admin/users/edit/{{$user->id}}">
                                    {{ csrf_field() }}
                                    <input type="hidden" name="userId" value="{{ $user->id }}" />

                                    @include("components.avatar", ["avatar" => $user->getUserAvatar()])

                                    <p>
                                        <label>
                                            <input id="removeUserAvatar" type="checkbox" name="removeUserAvatar" />
                                            Remove user avatar
                                        </label>
                                    </p>

                                    <p>
                                        <label>
                                            <input id="restrictAvatarUpload" type="checkbox" name="restrictAvatarUpload" {{ $user->getIsAllowedToUploadAvatar()  ? "checked": "" }}/>
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
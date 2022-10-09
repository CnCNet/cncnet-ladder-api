@extends('layouts.app')
@section('title', 'Account')

@section('cover')
/images/feature/feature-td.jpg
@endsection

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    Account Settings
                </h1>

                <a href="/account" class="previous-link">
                    <i class="fa fa-caret-left" aria-hidden="true"></i>
                    <i class="fa fa-caret-left" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<style>
.form-group { padding-bottom: 10px; padding-top: 10px; }
</style>

<section class="cncnet-features dark-texture">
    <div class="container">

        <div class="row">
            <div class="col-md-12">
                @include("components.form-messages")
            </div>
        </div>

        <div class="col-md-12">
        
            <a href="/account" class="previous-link">
                <i class="fa fa-caret-left" aria-hidden="true"></i> Back to account
            </a>

            <form method="POST" action="/account/settings" enctype="multipart/form-data">
                {{ csrf_field() }}

                <div class="form-group">
                    <div class="checkbox">
                        @if(isset($userSettings))
                        <p class="lead" style="margin-bottom:0">Ladder Point Filter</p>
                        <p>
                            Disable the Point Filter to match against any player on the ladder regardless of your rank.<br/> 
                            Opponents will also need it disabled.
                        </p>
                        <p>
                            <label>
                                <input id="disablePointFilter" type="checkbox" name="disabledPointFilter" @if($userSettings->disabledPointFilter) checked @endif />
                                Disable Point Filter
                            </label>
                        </p>
                        @endif
                    </div>
                </div>

                {{-- TODO future functionality will use this value, no need to have users touch this yet
                    <input id="enableAnonymous" type="checkbox" name="enableAnonymous"  value="{{ $userSettings->enableAnonymous }}" @if($userSettings->enableAnonymous) checked @endif />
                    <label for="enableAnonymous"> Enable Anonymity </label>
                --}}

                @if($user->getIsAllowedToUploadAvatar() == false)
                    <p class="lead" style="margin-bottom:0">Ladder Avatar Disabled</p>
                @else
                <div class="form-group">
                    <p class="lead" style="margin-bottom:0">Ladder Avatar</p>
                    <p>
                        Avatars that are not deemed suitable will be removed without warning. <br/>
                        No obscenity or advertising other platforms similar to CnCNet is allowed. 
                    </p>

                    <p>
                        <strong>Recommended dimensions are 300x300. Max file size: 1mb.</strong>
                    </p>
                    
                    <div>
                        @include("components.avatar", ["avatar" => $user->getUserAvatar()])
                    </div>

                    <label for="avatar">Upload an avatar</label>
                    <input type="file" id="avatar" name="avatar">

                    @if($user->getUserAvatar())
                       <label>
                            <input id="removeAvatar" type="checkbox" name="removeAvatar" />
                            Remove avatar?
                        </label>
                    @endif
                </div>
                @endif

                <div class="form-group">
                    <p class="lead" style="margin-bottom:0;">
                        Your social profiles
                    </p>
                    <p style="margin: 0;">
                        These will be shown on all your ladder profiles. Do not enter URLs.
                    </p>
                </div>

                <div class="form-group">
                    <label for="twitch">Twitch username</label>
                    <input id="twitch" type="text" class="form-control" name="twitch_profile" value="{{ $user->getTwitchProfile() }}" 
                        placeholder="Enter your Twitch username only"
                    />
                </div>

                <div class="form-group">
                    <label for="discord">Discord username</label>
                    <input id="discord" type="text" class="form-control" name="discord_profile" value="{{ $user->getDiscordProfile() }}" 
                        placeholder="Enter your Discord username only"
                    />
                </div>

                <div class="form-group">
                    <label for="youtube">YouTube username</label>
                    <input id="youtube" type="text" class="form-control" name="youtube_profile" value="{{ $user->getYoutubeProfile() }}" 
                        placeholder="Enter your YouTube username only"
                    />
                </div>

                <div>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
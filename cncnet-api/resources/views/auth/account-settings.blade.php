@extends('layouts.app')
@section('title', 'Account')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">Ladder Account Settings</x-slot>
        <x-slot name="description">
            Global ladder account settings, manage point filters, ladder avatars.
        </x-slot>


        <div class="mini-breadcrumb d-none d-lg-flex">
            <div class="mini-breadcrumb-item">
                <a href="/">
                    <span class="material-symbols-outlined">
                        home
                    </span>
                </a>
            </div>
            <div class="mini-breadcrumb-item">
                <a href="/account">
                    <span class="material-symbols-outlined icon pe-3">
                        person
                    </span>
                    Manage Ladder Account
                </a>
            </div>
            <div class="mini-breadcrumb-item">
                <a href="#">
                    <span class="material-symbols-outlined icon pe-3">
                        settings
                    </span>
                    Ladder Account Settings
                </a>
            </div>
        </div>
    </x-hero-with-video>
@endsection

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="/">
                        <span class="material-symbols-outlined">
                            home
                        </span>
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="/account">
                        <span class="material-symbols-outlined icon pe-3">
                            person
                        </span>
                        Manage Ladder Account
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="#">
                        <span class="material-symbols-outlined icon pe-3">
                            settings
                        </span>
                        Ladder Account Settings
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section class="">
        <div class="container">

            <div class="row">
                <div class="col-md-12 mt-2">
                    @include('components.form-messages')
                </div>
            </div>

            <div class="mb-5">
                @include('auth.account-settings-nav')
            </div>

            <div>
                <h4>Ladder Elo: {{ $user->getOrCreateLiveUserRating()->rating }}</h4>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <form method="POST" action="/account/settings" enctype="multipart/form-data">
                        {{ csrf_field() }}

                        <div class="form-group mb-5">
                            <h3>Ladder Anonymity</h3>
                            <p>
                                Keeps ladder usernames anonymous. This will hide certain features or stats from your
                                profiles.
                            </p>
                            <input id="enableAnonymous" type="checkbox" name="enableAnonymous" @if ($userSettings->enableAnonymous) checked @endif />
                            <label for="enableAnonymous">Enable Anonymity</label>
                        </div>

                        @if ($user->getIsAllowedToUploadAvatarOrEmoji() == false)
                            <h4>Ladder Avatar Disabled</h4>
                        @else
                            <div class="form-group mb-5">
                                <h3>Ladder Avatar</h3>
                                <p>
                                    <strong>Recommended dimensions are 300x300. Max file size: 1mb.<br /> File types
                                        allowed: jpg, png, gif </strong>
                                </p>
                                <p>
                                    Avatars that are not deemed suitable by CnCNet will be removed without warning.
                                    <br />
                                    Inappropriate images and advertising is not allowed.
                                </p>

                                <div>
                                    @include('components.avatar', ['avatar' => $user->getUserAvatar()])
                                </div>

                                <label for="avatar">Upload an avatar</label>
                                <input type="file" id="avatar" name="avatar">

                                @if ($user->getUserAvatar())
                                    <br />
                                    <label>
                                        <input id="removeAvatar" type="checkbox" name="removeAvatar" />
                                        Remove avatar?
                                    </label>
                                @endif
                            </div>
                        @endif

                        <h3>Pick an emoji</h3>
                        <p>Emojis will show up alongside your username on the ladder.</p>
                        @if ($user->getIsAllowedToUploadAvatarOrEmoji())
                            <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
                            <input type="hidden" name="user_emoji" id="emojiInput" />
                            <span id="emojiPreview" class="emoji-container" style="font-size:2.3rem">{{ $user->getEmoji() }}</span>

                            <label>
                                <input type="checkbox" name="remove_emoji" />
                                Remove emoji
                            </label>
                            <br />

                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#emojiModal">
                                Change Emoji
                            </button>

                            <div class="modal" tabindex="-1" id="emojiModal">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Pick your emoji</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <emoji-picker id="emojiPicker"
                                                style="margin: auto; width: 100%; --background: transparent; --border-color: transparent;"></emoji-picker>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary">Save</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const emojiPicker = document.getElementById('emojiPicker');
                                    const emojiInput = document.getElementById('emojiInput');
                                    const emojiPreview = document.getElementById('emojiPreview');
                                    emojiPicker.addEventListener('emoji-click', event => {
                                        emojiInput.value = event.detail.unicode;
                                        console.log(event.detail);
                                        emojiPreview.innerHTML = event.detail.unicode;
                                    });
                                });
                            </script>
                        @else
                            <span id="emojiPreview" class="emoji-container" style="font-size:2.3rem">💩 </span>
                            You are not allowed to upload emojis.
                        @endif


                        @if (in_array($user->id, config('app.allowed_observer_user_ids')))
                            <div class="form-group mt-5 mb-5">
                                <h3>Observer Mode (Invite only)</h3>
                                <strong>Strictly for streaming games only</strong>
                                <p>
                                    Watch any match on any ladder. Enabling this will take any username you have in the
                                    ladder and make you an
                                    observer.
                                </p>
                                <p>
                                    <label>
                                        <input id="isObserver" type="checkbox" name="isObserver" @if ($userSettings->is_observer) checked @endif />
                                        Enable Observer Mode
                                    </label>
                                </p>
                            </div>
                        @endif

                        <div class="form-group">
                            <h3>Match Ladder AI Bot?</h3>
                            <p>
                                This preference is only for the {{ \App\Helpers\LeagueHelper::getLeagueNameByTier(2) }}.
                                If no matches are found after some time you are matched against an AI.
                            </p>
                            <p>
                                <label>
                                    <input id="matchAI" type="checkbox" name="matchAI" @if ($userSettings->match_ai) checked @endif />
                                    Enable Matches against AI
                                </label>
                            </p>
                        </div>

                        <div class="form-group mt-5 mb-5">
                            <div class="checkbox">
                                @if (isset($userSettings))
                                    <h3>Ladder Point Filter</h3>
                                    <p>
                                        <strong class="highlight">Advanced Players Only! Disabling this as a new player
                                            is strongly
                                            discouraged.
                                        </strong>
                                    </p>
                                    <p>
                                        Disabling the Point Filter will match you against any player on the ladder
                                        regardless of your rank. <br />
                                        Opponents will also need it disabled.
                                    </p>
                                    <p>
                                        <label>
                                            <input id="disablePointFilter" type="checkbox" name="disabledPointFilter"
                                                @if ($userSettings->disabledPointFilter) checked @endif />
                                            Disable Point Filter &amp; Match with anyone
                                        </label>
                                    </p>
                                    <p>
                                        <label>
                                            <input id="match_any_map" type="checkbox" name="match_any_map"
                                                @if ($userSettings->match_any_map) checked @endif />
                                            Allow matching on any map regardless of your rank. Is used when both matched
                                            players have this option
                                            selected.
                                            (Affects ladders which use map divisions like RA2.)
                                        </label>
                                    </p>
                                @endif
                            </div>
                        </div>


                        <div class="form-group mt-5 mb-5">
                            <h3>Skip Score Screen</h3>
                            <label>
                                <input id="skip_score_screen" type="checkbox" name="skip_score_screen"
                                    @if ($userSettings->skip_score_screen) checked @endif />
                                Skip the score screen after a match ends
                            </label>
                        </div>

                        <div class="form-group mt-5 mb-5">
                            <h3>Observers</h3>
                            <label>
                                <input id="allow_observers" type="checkbox" name="allowObservers" @if ($userSettings->allow_observers) checked @endif />
                                Allow CnCNet vetted streamers to stream your matches? <br />
                                (Note: While we maximise testing of this feature, your preference will not work yet and
                                there may be 1 streamer in
                                your
                                match)
                            </label>
                        </div>

                        <div class="form-group mt-5">
                            <h3>Social profiles</h3>
                            <p>
                                These will be shown on all your ladder profiles. <strong class="highlight">Do not enter
                                    URLs to your social
                                    profiles</strong>.
                            </p>
                        </div>

                        <div class="form-group
                            mt-2">
                            <label for="twitch">Twitch username <strong>E.g. myTwitchUsername</strong></label>
                            <input id="twitch" type="text" class="form-control" name="twitch_profile" value="{{ $user->twitch_profile }}"
                                placeholder="Enter your Twitch username only" style="max-width:300px;" />
                        </div>

                        <div class="form-group mt-2">
                            <label for="discord">Discord username, <strong>E.g. user#9999</strong></label>
                            <input id="discord" type="text" class="form-control" name="discord_profile" value="{{ $user->discord_profile }}"
                                placeholder="Enter your Discord username only" style="max-width:300px;" />
                        </div>

                        <div class="form-group mt-2">
                            <label for="youtube">YouTube channel name <strong>E.g. myYouTubeChannel</strong></label>
                            <input id="youtube" type="text" class="form-control" name="youtube_profile" value="{{ $user->youtube_profile }}"
                                placeholder="Enter your YouTube username only" style="max-width:300px;" />
                        </div>

                        <div class="form-group mt-2 mb-2">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

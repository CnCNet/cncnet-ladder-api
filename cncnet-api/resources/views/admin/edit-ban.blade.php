@extends('layouts.app')
@section('title', 'Ladder')

@section('feature-image', '/images/feature/feature-index.jpg')
@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">CnCNet</strong>
                        <span>Admin</span>
                    </h1>
                </div>
            </div>
        </div>
    </div>
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
                    <a href="/admin">
                        <span class="material-symbols-outlined pe-3">
                            admin_panel_settings
                        </span>
                        Admin
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <?php $card = \App\Card::find($player->card_id); ?>

    <div class="player mt-4">
        <div class="feature-background player-card {{ $card->short or 'no-card' }}">
            <div class="container">
                <div class="player-header">
                    <div class="player-stats">
                        <h1 class="username">
                            {{ $player->username }}
                        </h1>
                        {{ $user->getBan() }}
                    </div>
                </div>
            </div>
        </div>
        <div class="feature-footer-background">
            <div class="container">
                <div class="player-footer">
                    <div class="player-dials">
                    </div>
                    <div class="player-achievements">
                        @if ($player->game_count >= 200)
                            <div>
                                <img src="/images/badges/achievement-games.png" style="height:50px" />
                                <h5 style="font-weight: bold; text-transform:uppercase; font-size: 10px;">Played <br />200+ Games</h5>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="player mt-4">
        <section class="dark-texture">
            <div class="container">
                <div class="row">
                    <div class="col-md-8">
                        @include('components.form-messages')

                        <h3>{{ $banDesc }}</h3>
                        <form method="POST" action="/admin/moderate/{{ $ladder->id }}/player/{{ $player->id }}/editban/{{ $id }}">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="ban_type" value="{{ $ban_type }}">
                            <input type="hidden" name="admin_id" value="{{ $admin_id }}">
                            <input type="hidden" name="user_id" value="{{ $user_id }}">
                            <input type="hidden" name="expires" value="{{ $expires }}">
                            <input type="hidden" name="start_or_end" value="{{ $start_or_end }}">
                            <input type="hidden" name="ip_address_id" value="{{ $ip_address_id }}">

                            <div class="form-group">
                                <label for="internal_note">Note for Internal Use</label>
                                <textarea class="form-control" id="internal_note" name="internal_note">{{ $internal_note }}</textarea>
                            </div>
                            @if ($ban_type != \App\Ban::BAN_SHADOW)
                                <div class="form-group">
                                    <label for="plubic_reason">Publicly Viewable Reason</label>
                                    <textarea class="form-control" id="plubic_reason" name="plubic_reason">{{ $plubic_reason }}</textarea>
                                </div>
                            @else
                                <p>
                                    Public reason is diabled for this ban type
                                </p>
                            @endif
                            <button type="submit" class="btn btn-primary">Save</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

    </div>
@endsection

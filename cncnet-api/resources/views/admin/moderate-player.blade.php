@extends('layouts.app')
@section('title', 'Ladder')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">Moderate player</x-slot>

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
    <?php $card = \App\Models\Card::find($player->card_id); ?>

    <div class="player mt-2">
        <div class="feature-background player-card {{ $card->short ?? 'no-card' }}">
            <div class="container">

                <div class="player-header mt-3 mb-3">
                    <div class="player-stats">
                        <h1 class="username">
                            {{ $player->username }}
                        </h1>
                        {{ $user->getBan() }}
                    </div>
                </div>

                @include('components.form-messages')

                <div class="col-12 col-lg-5">
                    <a class="btn btn-sm btn-primary mb-2"
                        href="/admin/moderate/{{ $player->ladder->id }}/player/{{ $player->id }}/newban/{{ \App\Models\Ban::COOLDOWN1H }}">
                        1 Hour Cooldown</a>
                    <a class="btn btn-sm btn-primary mb-2"
                        href="/admin/moderate/{{ $player->ladder->id }}/player/{{ $player->id }}/newban/{{ \App\Models\Ban::COOLDOWN2H }}">
                        2 Hour Cooldown</a>
                    <a class="btn btn-sm btn-primary mb-2"
                        href="/admin/moderate/{{ $player->ladder->id }}/player/{{ $player->id }}/newban/{{ \App\Models\Ban::COOLDOWN4H }}">
                        4 Hour
                        Cooldown</a>
                    <a class="btn btn-sm btn-primary mb-2"
                        href="/admin/moderate/{{ $player->ladder->id }}/player/{{ $player->id }}/newban/{{ \App\Models\Ban::COOLDOWN12H }}">
                        12 Hour
                        Cooldown</a>
                    <a class="btn btn-sm btn-danger mb-2"
                        href="/admin/moderate/{{ $player->ladder->id }}/player/{{ $player->id }}/newban/{{ \App\Models\Ban::BAN48H }}">
                        48 Hour Ban</a>
                    <a class="btn btn-sm btn-danger mb-2"
                        href="/admin/moderate/{{ $player->ladder->id }}/player/{{ $player->id }}/newban/{{ \App\Models\Ban::BAN1WEEK }}">
                        1 Week Ban</a>
                    <a class="btn btn-sm btn-danger mb-2"
                        href="/admin/moderate/{{ $player->ladder->id }}/player/{{ $player->id }}/newban/{{ \App\Models\Ban::BAN2WEEK }}">
                        2 Week Ban</a>
                    <a class="btn btn-sm btn-danger"
                        href="/admin/moderate/{{ $player->ladder->id }}/player/{{ $player->id }}/newban/{{ \App\Models\Ban::BAN_SHADOW }}">
                        {{ \App\Models\Ban::typeToDescription(\App\Models\Ban::BAN_SHADOW) }}
                    </a>
                    @if (isset($mod) && $mod->isLadderAdmin($player->ladder))
                        <a class="btn btn-sm btn-danger"
                            href="/admin/moderate/{{ $player->ladder->id }}/player/{{ $player->id }}/newban/{{ \App\Models\Ban::PERMBAN }}">
                            Indefinite Ban
                        </a>
                    @endif
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
                                <h5 style="font-weight: bold; text-transform:uppercase; font-size: 10px;">Played <br />200+
                                    Games</h5>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="player">
        <section class="dark-texture">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 col-md-offset-2 player-box player-card" style="margin-bottom: 8px">
                        <p style="color: #fff">Player Alerts</p>
                        <select id="alertList" class="form-control" size="4">
                            @foreach ($player->alerts as $alert)
                                <option value="{{ $alert->id }}">{{ $alert->message }}</option>
                            @endforeach
                            <option value="new">&lt;new></option>
                        </select>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#editAlert">
                            Edit
                        </button>
                    </div>
                </div>

                <div class="row mt-5">
                    <div class="col-md-12">
                        <h3>Ban History</h3>
                    </div>

                    <div class="table-responsive">
                        <table class="table col-md-12">
                            <thead>
                                <tr>
                                    <th scope=" col">Admin</th>
                                    <th>Ban type</th>
                                    <th>Interal Notes</th>
                                    <th>Displayed Reason for ban</th>
                                    <th>IP</th>
                                    <th>Expiration</th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody class="table">
                                @foreach ($bans as $ban)
                                    <tr>
                                        <td>{{ $ban->admin->name }}</td>
                                        <td>{{ $ban->typeDescription() }}</td>
                                        <td>{{ $ban->internal_note }}</td>
                                        <td>{{ $ban->plubic_reason }}</td>
                                        <td>
                                            @if ($ban->ip && $mod->isLadderAdmin($player->ladder))
                                                {{ $ban->ip->address }}
                                            @else
                                                Hidden
                                            @endif
                                        </td>

                                        @if ($ban->expires === null || $ban->expires->eq(\App\Models\Ban::unstartedBanTime()))
                                            <td>Not Started</td>
                                            <td>
                                                <form method="POST"
                                                    action="/admin/moderate/{{ $ladder->id }}/player/{{ $player->id }}/editban/{{ $ban->id }}">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                    <input type="hidden" name="start_or_end" value="1">
                                                    <button type="submit" class="btn btn-danger btn-sm">Start</button>
                                                </form>
                                            </td>
                                        @elseif ($ban->expires !== null && $ban->expires->gt(\Carbon\Carbon::now()))
                                            <td>{{ $ban->expires }}</td>
                                            <td>
                                                <form method="POST"
                                                    action="/admin/moderate/{{ $ladder->id }}/player/{{ $player->id }}/editban/{{ $ban->id }}">
                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                    <input type="hidden" name="start_or_end" value="1">
                                                    <button type="submit" class="btn btn-danger btn-sm">Stop</button>
                                                </form>
                                            </td>
                                        @else
                                            <td>{{ $ban->expires }}</td>
                                            <td></td>
                                        @endif
                                        <td>
                                            <a href="/admin/moderate/{{ $ladder->id }}/player/{{ $player->id }}/editban/{{ $ban->id }}"
                                                class="btn btn-sm">
                                                Edit
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

    </div>

    <div class="modal fade" id="editAlert" tabIndex="-1" role="dialog">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h3 class="modal-title">Edit Alert</h3>
                </div>
                <div class="modal-body clearfix">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12 player-box player-card">
                                <form method="POST" action="{{ $player->id }}/alert">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
                                    <input type="hidden" id="alertId" name="id" value="" />
                                    <input type="hidden" name="player_id" value="{{ $player->id }}" />
                                    <div class="form-group">
                                        <label for="alertText">Alert Text</label>
                                        <textarea id="alertText" name="message" class="form-control" rows="4" cols="50"></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="alertDate">Expiration</label>
                                        <input type="text" id="alertDate" name="expires_at" class="form-control"></input>
                                    </div>
                                    <button type="submit" name="submit" value="update" class="btn btn-primary btn-md">
                                        Save
                                    </button>
                                    <button type="submit" name="submit" value="delete" class="btn btn-danger btn-md">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script type="text/javascript">
        let playerAlerts = {
            @foreach ($player->alerts as $alert)
                "{{ $alert->id }}": {
                    "id": "{{ $alert->id }}",
                    "message": {!! json_encode($alert->message) !!},
                    "expires_at": "{{ $alert->expires_at }}"
                },
            @endforeach
            "new": {
                "id": "new",
                "message": "",
                "expires_at": "{{ \Carbon\Carbon::now()->addMonth(1)->startOfMonth() }}"
            }
        };

        $(function() {
            $("#alertDate").datepicker({
                format: 'yyyy-mm-dd',
                startDate: "{{ \Carbon\Carbon::now()->addDay(1) }}"
            });
        });

        document.getElementById("alertList").onchange = function() {
            document.getElementById("alertId").value = playerAlerts[this.value]["id"];
            document.getElementById("alertText").innerHTML = playerAlerts[this.value]["message"];
            document.getElementById("alertText").value = playerAlerts[this.value]["message"];
            document.getElementById("alertDate").value = playerAlerts[this.value]["expires_at"];
        };

        (function() {
            if (document.getElementById("alertList").value === '')
                document.getElementById("alertList").selectedIndex = 0;

            window.addEventListener('load', function() {
                document.getElementById("alertList").onchange();
            });
        })();
    </script>
@endsection

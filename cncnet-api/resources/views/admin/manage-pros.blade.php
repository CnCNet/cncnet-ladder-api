@extends('layouts.app')
@section('title', 'Ladder Pros')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">CnCNet Ladder Pro Users</x-slot>
        <x-slot name="description">
            View and manage ladder pro users
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
    <section class="mt-5 mb-5">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h3>Pro users</h3>

                    <form action="{{ route('getProList') }}">
                        <select name="ladderId" class="form-control mb-2" onchange="this.form.submit()">
                            @foreach ($ladders as $ladder)
                                <option value="{{ $ladder->id }}" @if ($ladderId == $ladder->id) selected @endif>{{ $ladder->name }}</option>
                            @endforeach
                        </select>
                    </form>

                    <form action="{{ route('saveProList') }}" method="POST">
                        @csrf
                        <input type="hidden" name="ladderId" value="{{ $ladderId }}" />
                        <div class="mb-5">
                            <select class="form-control" name="userProIds[]" multiple style="min-height: 600px;">
                                @foreach ($players as $player)
                                    <option @if (in_array($player->player->user->id, $proUsersIds)) selected @endif value="{{ $player->player->user->id }}">
                                        {{ $player->player_name }}
                                        // {{ $player->player->user->name }}
                                        // Points: {{ $player->points }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

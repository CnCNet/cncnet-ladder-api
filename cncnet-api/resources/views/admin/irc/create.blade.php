@extends('layouts.app')
@section('title', 'Ladder')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">CnCNet IRC Management</x-slot>
        <x-slot name="description">
            CnCNet IRC Management
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
    <section class="pt-4 mt-5 mb-5">
        <div class="container">

            <div class="row mb-5">
                <div class="col-12">
                    <a href="{{ route('admin.irc.bans') }}" class="btn btn-secondary">View all bans</a>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <h3>Create new ban</h3>

                    <div class="text-end mt-2 mb-5">
                        <a href="{{ route('admin.irc.bans.create') }}" class="btn btn-primary">Create new ban</a>
                    </div>

                    @include('admin.irc.components.bans.ban-form', ['ban' => null])
                </div>
            </div>
        </div>
    </section>
@endsection

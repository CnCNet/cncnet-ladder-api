@extends('layouts.app')
@section('title', 'Ladder')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">CnCNet IRC Bans</x-slot>
        <x-slot name="description">
            Manage all IRC Bans
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
            <div class="mini-breadcrumb-item">
                <a href="/admin/irc" class="">
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
                <li class="breadcrumb-item">
                    <a href="/admin/irc">
                        <span class="material-symbols-outlined pe-3">
                            admin_panel_settings
                        </span>
                        IRC
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section class="pt-4 mt-5 mb-5">
        <div class="container">

            <div class="row">
                <div class="text-end mt-2 mb-5">
                    <a href="{{ route('admin.irc.bans.create') }}" class="btn btn-primary">Create new ban</a>
                </div>

                <div class="col-12">
                    {{ $bans->links() }}

                    @include('admin.irc.components.bans-table')

                    {{ $bans->links() }}
                </div>
            </div>
        </div>
    </section>
@endsection

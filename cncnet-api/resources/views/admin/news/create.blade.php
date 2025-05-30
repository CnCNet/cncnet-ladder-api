@extends('layouts.app')
@section('title', 'Ladder')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">CnCNet News</x-slot>
        <x-slot name="description">
            Create a news post
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
                <a href="/admin/news" class="">
                    <span class="material-symbols-outlined">
                        admin_panel_settings
                    </span>
                    Manage News
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
                    <a href="/admin/news">
                        <span class="material-symbols-outlined pe-3">
                            admin_panel_settings
                        </span>
                        News
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="/admin/news/create">
                        <span class="material-symbols-outlined pe-3">
                            admin_panel_settings
                        </span>
                        @if ($news)
                            Edit news post
                        @else
                            Create news post
                        @endif
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <div class="container">
        <div class="mt-5 mb-5">
            @if ($news)
                <h2>Edit news post</h2>
            @else
                <h2>Create news post</h2>
            @endif
        </div>
        @include('components.form-messages')
        @include('admin.components.news-editor')
    </div>
@endsection

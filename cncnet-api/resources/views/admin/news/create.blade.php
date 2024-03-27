@extends('layouts.app')
@section('title', 'Ladder')
@section('feature-video', \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2'))
@section('feature-video-poster', \App\Models\URLHelper::getVideoPosterUrlByAbbrev('ra2'))

@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">CnCNet</strong>
                        <span>Ladder News</span>
                    </h1>
                </div>
            </div>
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
                        News
                    </a>
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

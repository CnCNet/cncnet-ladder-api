@extends('layouts.app')
@section('title', 'Canceled Matches')

@section('feature-image', '/images/feature/feature-index.jpg')
@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3">
                        <strong>{{ $ladder->name }}</strong><br /> Canceled Matches
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
    <section class="mt-4">
        <div class="container">
            @include('components.pagination.paginate', ['paginator' => $canceled_matches->appends(request()->query())])

            <table class="table col-md-12">
                <thead>
                    <tr>
                        <th>Created At</th>
                        <th>Player Name</th>
                        <th>Quick Match Id</th>
                    </tr>
                </thead>
                <tbody class="table">
                    @foreach ($canceled_matches as $canceled_match)
                        <tr>
                            <td>{{ $canceled_match->created_at->format('F j, Y, g:i a T') }}</td>
                            <td>
                                @if ($canceled_match->player->clanPlayer)
                                    <i class="bi bi-flag-fill icon-clan ps-1 pe-1"></i>
                                    <span class="pe-3">
                                        [{{ $canceled_match->player->clanPlayer->clan->short }}]
                                    </span>
                                @endif
                                {{ $canceled_match->username }}
                            </td>
                            <td>{{ $canceled_match->qm_match_id }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @include('components.pagination.paginate', ['paginator' => $canceled_matches->appends(request()->query())])
        </div>
    </section>
@endsection

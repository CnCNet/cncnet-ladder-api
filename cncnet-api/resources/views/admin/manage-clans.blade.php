@extends('layouts.app')
@section('title', 'Ladder Users')

@section('feature-image', '/images/feature/feature-index.jpg')
@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3">
                        <strong class="fw-bold">CnCNet</strong>
                        <span>Ladder Admin</span>
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
    <div class="admin-users mt-5">
        <section class="light-texture game-detail supported-games">
            <div class="container">
                <div class="feature">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="search" style="margin-bottom: 15px; max-width: 500px">
                                <form>
                                    <input class="form-control" name="search" placeholder="Search by clan short" value="{{ $search }}"
                                        style="height: 50px" />
                                    <a href="?" style="color: silver;padding: 5px;margin-bottom: 5px; display: block;float: right;">
                                        Clear search
                                    </a>
                                </form>
                            </div>

                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Avatar</th>
                                        <th scope="col">Short</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Bio</th>
                                        <th scope="col">Edit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($clans as $clan)
                                        <tr>
                                            <th scope="row"></th>
                                            <td>
                                                @include('components.avatar', [
                                                    'avatar' => $clan->getClanAvatar(),
                                                    'size' => 100,
                                                    'type' => 'clan',
                                                ])
                                            </td>
                                            <th>{{ $clan->short }}</th>
                                            <th>{{ $clan->name }}</th>
                                            <th>{{ $clan->description }}</th>
                                            <td>
                                                <button type="button" class="btn btn-secondary" data-bs-toggle="modal"
                                                    data-bs-target="#clan_edit_{{ $clan->id }}">
                                                    Edit
                                                </button>

                                                <div class="modal fade" id="clan_edit_{{ $clan->id }}" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST" action="/admin/clans">
                                                                <input type="hidden" name="clan_id" value="{{ $clan->id }}" />
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Edit {{ $clan->short }}</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                        aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                                    <input type="hidden" name="ladder_id" value="{{ $ladder->id }}">

                                                                    <div class="form-group mb-2">
                                                                        <label for="short">Short Name</label>
                                                                        <input type="text" name="short" class="form-control border" id="short"
                                                                            value="{{ $clan->short }}">
                                                                    </div>

                                                                    <div class="form-group mb-2">
                                                                        <label for="name">Full Clan Name</label>
                                                                        <input type="text" name="name" class="form-control border" id="name"
                                                                            placeholder="New Clan Name" value="{{ $clan->name }}">
                                                                    </div>

                                                                    <div class="form-group mb-2">
                                                                        <label for="description"><strong>Clan Bio</strong></label>
                                                                        <textarea type="text" name="description" class="form-control border" id="description" placeholder="">{{ $clan->description }}</textarea>
                                                                    </div>
                                                                </div>

                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary"
                                                                        data-bs-dismiss="modal">Close</button>
                                                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            @include('components.pagination.paginate', ['paginator' => $clans->appends(request()->query())])

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

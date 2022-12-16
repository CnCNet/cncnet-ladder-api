@extends('layouts.app')
@section('title', 'Canceled Matches')

@section('feature-image', '/images/feature/feature-index.jpg')
@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong>{{ $ladder->name }}</strong><br /> Canceled Matches
                    </h1>
                </div>
            </div>
        </div>
    </div>
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
                            <td>{{ $canceled_match->username }}</td>
                            <td>{{ $canceled_match->qm_match_id }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @include('components.pagination.paginate', ['paginator' => $canceled_matches->appends(request()->query())])
        </div>
    </section>
@endsection

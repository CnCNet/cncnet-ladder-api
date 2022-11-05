@extends('layouts.app')
@section('title', 'Canceled Matches')

@section('cover')
/images/feature/feature-td.jpg
@endsection

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    {{ $ladder->name  }} Canceled Matches
                </h1>

                <a href="/admin/" class="btn btn-transparent btn-lg">
                    <i class="fa fa-chevron-left fa-lg fa-fw" aria-hidden="true"></i> Back to Admin
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container">
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
                <td>{{ $canceled_match->created_at->format("F j, Y, g:i a") }}</td>
                <td>{{ $canceled_match->username }}</td>
                <td>{{ $canceled_match->qm_match_id }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
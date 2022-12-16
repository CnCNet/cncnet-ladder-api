@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
    /images/feature/feature-index.jpg
@endsection

@section('feature')
    <div class="feature-background sub-feature-background">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-8 col-md-offset-2">
                    <h1>
                        CnCNet <strong>Clan Ladders</strong>
                    </h1>
                    <p>
                        Play, Compete, <strong>Conquer!</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="light-texture game-detail supported-games">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h3>CnCNet <strong>Clan Competition</strong> </h3>
                </div>
            </div>
            <div class="feature">
                <div class="row">
                    @foreach ($clan_ladders as $history)
                        <div class="col-xs-12 col-sm-6 col-md-4" style="margin-bottom:20px">
                            <a href="/clans/{{ $history->ladder->abbreviation . '/leaderboards/' . $history->short }}/" title="{{ $history->ladder->name }}" class="ladder-link">
                                <div class="ladder-cover cover-{{ $history->ladder->abbreviation }}" style="background-image: url('/images/ladder/{{ $history->ladder->abbreviation . '-cover.png' }}')">
                                    <div class="details">
                                        <div class="type">
                                            <h1>{{ $history->ladder->name }}</h1>
                                            <p class="lead"><strong>Clan</strong></p>
                                        </div>
                                    </div>
                                    <div class="badge-cover">
                                        <ul class="list-inline">
                                            <li>
                                                <p>{{ Carbon\Carbon::parse($history->starts)->format('F Y') }} Competition</p>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection

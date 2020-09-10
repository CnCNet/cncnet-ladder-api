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
<section class="cncnet-features general-texture game-detail">
    <div class="container">
        <div class="feature">
            <div class="row">
                <div class="col-md-12">
                    <div class="header">
                        <div class="row">
                            <div class="col-md-3">
                                @if($history->qmLadderRules && $history->ladder->qmLadderRules->tier2_rating > 0)
                                    @if($tier == 1 || $tier === null)
                                        <h3><strong>Clan</strong> Masters League Rankings</h3>
                                    @elseif($tier == 2)
                                        <h3><strong>Clan</strong> Contenders League Rankings</h3>
                                    @endif
                                @else
                                    <h3><strong>Clan</strong> Battle Rankings</h3>
                                @endif
                            </div>

                            <div class="col-md-9 text-right">
                                <ul class="list-inline">
                                    <li>
                                        <a href="/account/{{ $history->ladder->abbreviation }}/list" class="btn btn-secondary text-uppercase" style="font-size: 15px;">
                                            <i class="fa fa-user fa-lg fa-fw" aria-hidden="true" style="margin-right: 0;"></i> Your Account
                                        </a>
                                    </li>
                                    <li>
                                        <div class="btn-group filter">
                                            <button type="button" class="btn btn-secondary dropdown-toggle text-uppercase" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="font-size: 15px;">
                                                <i class="fa fa-industry fa-fw" aria-hidden="true" style="margin-right: 5px;"></i> Previous Month <span class="caret"></span>
                                            </button>
                                            <ul class="dropdown-menu">
                                            @foreach($ladders_previous as $previous)
                                            <li>
                                                <a href="/ladder/{{ $previous->short . "/" . $previous->ladder->abbreviation }}/" title="{{ $previous->ladder->name }}">
                                                    Rankings - {{ $previous->short }}
                                                </a>
                                            </li>
                                            @endforeach
                                            </ul>
                                        </div>
                                    </li>
                                    <li>
                                        <form>
                                            <div class="form-group" method="GET">
                                                <div class="search" style="position:relative;">
                                                    <label for="search-input" style="position: absolute;left: 12px;top: 7px;">
                                                        <i class="fa fa-search" aria-hidden="true"></i>
                                                    </label>
                                                    <input class="form-control" name="search" value="{{ $search }}" placeholder="Clan Name..." style="padding-left:40px;"/>
                                                </div>
                                            </div>
                                        </form>
                                    </li>
                                </ul>
                                <div class="text-right">
                                    @if ($search)
                                        <small>
                                            Searching for <strong>{{ $search }}</strong> returned {{ count($players) }} results
                                            <a href="?search=">Clear?</a>
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

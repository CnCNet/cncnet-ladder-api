@extends('layouts.app')
@section('title', 'Command &amp; Conquer Online')

@section('cover')
images/feature/feature-index.png
@endsection

@section('feature')
<div class="feature-background slider-header">
    <div class="container">
        <div class="row">
            <div class="col-md-10 col-md-offset-2">
                <h1>CnCNet Ladder</h1>
                <p>
                    It's finally arrived. Comrades it's now time to compete.
                </p>
                <ul class="list-inline">
                    <li>
                        <p>
                            <a class="btn btn-primary btn-lg" href="download" role="button" title="{{trans('homepage.feature_cta_primary')}}">
                                View Ladders
                            </a>
                        </p>
                    </li>
                    <li>
                        <p>
                            <a class="btn btn-secondary btn-lg" href="what-is-cncnet" role="button" title="{{trans('homepage.feature_cta_secondary')}}">
                                {{trans('homepage.feature_cta_secondary')}}
                            </a>
                        </p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="supported-games light-texture">
    <div class="container text-center">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <h1>{{trans('homepage.supported_games_h1')}}</h1>
                <p class="lead">{{trans('homepage.supported_games_description')}}</p>
                <a class="btn btn-primary btn-lg" href="download" role="button" title="{{trans('homepage.supported_games_cta_primary')}}">
                    {{trans('homepage.supported_games_cta_primary')}}
                </a>
            </div>
        </div>
    </div>
</section>

<section id="test" class="cncnet-features dark-texture">
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-md-offset-3 text-center">
                <h1>{!! trans('homepage.feature_list_h1') !!}</h1>
                <p>{!! trans('homepage.feature_list_description') !!}</p>
            </div>
        </div>
        <div class="row text-center">
            <div class="features">
                <div class="col-md-1"></div>
                <div class="col-xs-6 col-sm-4 col-md-2 item">
                    <div class="content">
                        <i class="fa fa-user-o" aria-hidden="true"></i>
                        <p>{{ trans('homepage.feature_list_li_1') }}</p>
                    </div>
                </div>
                <div class="col-xs-6 col-sm-4 col-md-2 item">
                    <div class="content">
                        <i class="fa fa-desktop" aria-hidden="true"></i>
                        <p>{{ trans('homepage.feature_list_li_2') }}</p>
                    </div>
                </div>
                <div class="col-xs-6 col-sm-4 col-md-2 item">
                    <div class="content">
                        <i class="fa fa-rocket" aria-hidden="true"></i>
                        <p>{{ trans('homepage.feature_list_li_3') }}</p>
                    </div>
                </div>
                <div class="col-xs-6 col-sm-4 col-md-2 item">
                    <div class="content">
                        <i class="fa fa-github" aria-hidden="true"></i>
                        <p>{{ trans('homepage.feature_list_li_4') }}</p>
                    </div>
                </div>
                <div class="col-xs-6 col-sm-4 col-md-2 item">
                    <div class="content">
                        <i class="fa fa-shield" aria-hidden="true"></i>
                        <p>{{ trans('homepage.feature_list_li_5') }}</p>
                    </div>
                </div>
                <div class="col-md-1"></div>
            </div>
        </div>
    </div>
</section>

<section class="freeware dark-texture-invert">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-6 col-md-offset-3">
                <h1>{{ trans('homepage.free_games_h1') }}</h1>
                <p>{{ trans('homepage.free_games_description') }}</p>
                <a class="btn btn-tertiary btn-md" href="buy" title="{{ trans('homepage.free_games_cta_primary') }}">
                    {{ trans('homepage.free_games_cta_primary') }}
                </a>
            </div>
        </div>
    </div>
</section>

@endsection

@section('js')
<script src="js/jquery.bxslider.min.js"></script>
<script src="js/cncnet-home.js"></script>
@endsection
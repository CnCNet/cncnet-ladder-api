@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
/images/feature/feature-yr.jpg
@endsection

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    <img src="/images/games/yuris-revenge/logo.png" alt="Yuri's Revenge logo" class="logo" />
                </h1>
                <p>
                   Competitive Ladders for Yuri's Revenge
                </p>
                <ul class="list-inline">
                    <li>
                        <p>
                            <a class="btn btn-primary btn-lg" href="#download">Primary</a>
                        </p>
                    </li>
                    <li>
                        <p>
                            <a class="btn btn-secondary btn-lg" href="what-is-cncnet">Secondary</a>
                        </p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection


@section('content')
<div class="container">
    <div class="row">

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th class="rank">Rank <i class="fa fa-trophy fa-fw"></i></th>
                    <th>Profile <i class="fa fa-user fa-fw"></i></th>
                    <th class="hidden-xs">Points <i class="fa fa-bolt fa-fw"></i></th>
                    <th>Won <i class="fa fa-level-up fa-fw"></i></th>
                    <th>Lost <i class="fa fa-level-down fa-fw"></i></th>
                    <th>Winning % </th>
                </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
        </div>
    </div>
</div>


@endsection


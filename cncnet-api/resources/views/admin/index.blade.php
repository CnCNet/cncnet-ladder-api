@extends('layouts.app')
@section('title', 'Ladder')

@section('cover')
/images/feature/feature-td.jpg
@endsection

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    CnCNet Admin
                </h1>
                <p>Admin panel
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="light-texture game-detail supported-games">
    <div class="container">
        <div class="feature">

            <div class="row">
                <div class="col-md-4">
                    <a href="/admin/users" class="profile-link">
                        <div class="profile-listing">
                            <h3>Users</h3>
                            <p>Manage everything to do with Users</p>
                        </div>
                    </a>
                </div>  
              
                <div class="col-md-4">
                    <a href="/admin/setup" class="profile-link">
                        <div class="profile-listing">
                            <h3>Ladder Setup</h3>
                            <p>Manage maps, rules, and all things related to the ladder</p>
                        </div>
                    </a>
                </div>
              
                <div class="col-md-4">
                    <div class="profile-link">
                        <div class="profile-listing">
                            <h3>Games</h3>
                            <p>Manage maps, rules, and all things related to the ladder</p>
                            
                            <ul class="list-unstyled">
                            @foreach($ladders as $ladder)
                            <li><a href="/admin/games/{{ $ladder->abbreviation }}">{{ $ladder->name }}</a></li>
                            @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection





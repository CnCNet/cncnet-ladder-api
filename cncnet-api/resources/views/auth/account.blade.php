@extends('layouts.app')
@section('title', 'Account')

@section('cover')
/images/feature/feature-td.jpg
@endsection

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    CnCNet Ladder Account
                </h1>
                <p class="text-uppercase">
                   Play. Compete. <strong>Conquer.</strong>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<section>
    <div class="container">
        <div class="feature">
            <div class="row">
                <div class="col-md-12">
                    <div class="text-center" style="padding-bottom: 40px;">
                        <h1>Hi {{ $user->name }} </h1>
                        <p class="lead">Manage everything to do with your CnCNet Ladder Account here.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cncnet-features dark-texture">
    <div class="container">
        <div class="row">

            <div class="col-md-4">
                <h2>Add a new username?</h2>

                @if (count($errors) > 0)
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @elseif (Session::has('error'))
                <div class="alert alert-danger">
                    {{Session::get('error')}}
                </div>
                @endif

               

                <form method="POST" action="account/username">
                  <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="form-group">
                        <p>Usernames will be the name shown when you login to CnCNet clients and play games.</p>
                        <label for="username">Username</label>
                        <input type="text" name="username" class="form-control" id="username" placeholder="Username">
                    </div>
                    <div class="form-group">
                        <label for="ladder">Ladder</label>
                        <select name="ladder" id="ladder" class="form-control">
                        @foreach($ladders as $ladder)
                        <option value="{{$ladder->id}}">{{$ladder->name}}</option>
                        @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">Create</button>
                </form>
            </div>
            <div class="col-md-6 col-md-offset-2">
                <h2>Your Usernames</h2>

                <div class="table-responsive">
                    <table class="table table-hover player-games">
                        <thead>
                            <tr>
                                <th>Username <i class="fa fa-user-o fa-fw"></i></th>
                                <th>Points <i class="fa fa-bolt fa-fw"></i></th>
                                <th>Wins <i class="fa fa-level-up fa-fw"></i></th>
                                <th>Losses <i class="fa fa-level-down fa-fw"></i></th>
                                <th>Winning % </th>
                                <th>Ladder <i class="fa fa-trophy fa-fw"></i></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($user->usernames()->get() as $u)
                            <tr>
                                @if(isset($u->ladder()->first()->abbreviation))
                                    <td><a href="/ladder/{{ $u->ladder()->first()->abbreviation }}/player/{{ $u->username }}">{{ $u->username }}</a></td>
                                @else
                                    Ladder not found for player {{ $u->username }}
                                @endif
                                <td>
                                    {{ $u->points }}
                                </td>          
                                <td>
                                    {{ $u->win_count }}
                                </td>          
                                <td>
                                    {{ $u->loss_count }}
                                </td>
                                <td>
                                @if($u->win_count > 0)  
                                    {{ $u->win_count / ($u->win_count + $u->loss_count) * 100 }}%
                                @else
                                    0%
                                @endif
                                </td>
                                <td>
                                @if(isset($u->ladder()->first()->abbreviation))
                                    {{ $u->ladder()->first()->name }}
                                @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection


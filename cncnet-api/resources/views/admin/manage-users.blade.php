@extends('layouts.app')
@section('title', 'Ladder Users')

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
                <p>Admin panel for managing Users</p>
                                                       
                <a href="/admin/" class="btn btn-transparent btn-lg">
                    <i class="fa fa-chevron-left fa-lg fa-fw" aria-hidden="true"></i> Back to Admin
                </a>
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
                    <h3>Users</h3>
                    <form>

                    </form>
                </div>  
            </div>
        </div>
    </div>
</section>
@endsection




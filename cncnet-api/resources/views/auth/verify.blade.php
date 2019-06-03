@extends('layouts.app')
@section('title', 'Ladder Email Verification')

@section('cover')
/images/feature/feature-td.jpg
@endsection

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    Ladder Email Verification
                </h1>
            </div>
        </div>
    </div>
</div>
@endsection

@section('body_onload')document.verify.submit()@endsection

@section('content')
<section class="light-texture game-detail supported-games" style="color: silver;">
    <div class="container">
	    <div class="row">
		    <div class="col-md-8 col-md-offset-2">
                        <form class="form" method="POST" name="verify" action="/account/verify" >
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <button type="submit" class="btn btn-primary" name="verify_submit" id="verify_submit">
                                Click Here
                            </button>
                        </form>
                    </div>
            </div>
    </div>
</section>
@endsection

@extends('layouts.app')
@section('title', 'Ladder Sign up')

@section('cover')
/images/feature/feature-td.jpg
@endsection

@section('feature')
<div class="feature-background sub-feature-background">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-8 col-md-offset-2">
                <h1>
                    Alpha Ladder Account Sign Up
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
<section class="light-texture game-detail supported-games" style="color: silver;">
    <div class="container">
	    <div class="row">
		    <div class="col-md-8 col-md-offset-2">
                <h2>Create your CnCNet Ladder Account</h2>
                <p style="color:silver">
                    <strong>Note: We are in an Alpha testing stage</strong>, bugs are likely to occur. The Quick Match Client and related sites are likely to change during development.  
                    Report bugs in <a href="https://forums.cncnet.org/forum/66-cncnet-ladder/" target="_blank">our forums</a>.</p>
                <h2>How to play</h2>
                <ol style="font-size:15px;">
                    <li>Create your Ladder Account below.</li>
                    <li>Once complete, create a Nickname to play online with.</li>
                    <li>Login to the CnCNet Quick Match client with your Ladder Account.</li>
                    <li>Click Quick Match and wait to play ladder games!</li>
                </ol>
                <br/>

			    <div class="panel panel-default">
				    <div class="panel-heading">Step 1 - Create your Ladder Account</div>
				    <div class="panel-body">
					    @if (count($errors) > 0)
						    <div class="alert alert-danger">
							    <strong>Whoops!</strong> There were some problems with your input.<br><br>
							    <ul>
								    @foreach ($errors->all() as $error)
									    <li>{{ $error }}</li>
								    @endforeach
							    </ul>
						    </div>
					    @endif

					    <form class="form-horizontal" role="form" method="POST" action="/auth/register">
						    <input type="hidden" name="_token" value="{{ csrf_token() }}">

						    <div class="form-group">
							    <label class="col-md-4 control-label">Name</label>
							    <div class="col-md-6">
								    <input type="text" class="form-control" name="name" value="{{ old('name') }}">
							    </div>
						    </div>

						    <div class="form-group">
							    <label class="col-md-4 control-label">E-Mail Address</label>
							    <div class="col-md-6">
								    <input type="email" class="form-control" name="email" value="{{ old('email') }}">
							    </div>
						    </div>

						    <div class="form-group">
							    <label class="col-md-4 control-label">Password</label>
							    <div class="col-md-6">
								    <input type="password" class="form-control" name="password">
							    </div>
						    </div>

						    <div class="form-group">
							    <label class="col-md-4 control-label">Confirm Password</label>
							    <div class="col-md-6">
								    <input type="password" class="form-control" name="password_confirmation">
							    </div>
						    </div>

						    <div class="form-group">
							    <div class="col-md-6 col-md-offset-4">
								    <button type="submit" class="btn btn-primary">
									    Register
								    </button>
                                    <p>
                                    <small>By registering and using the Quick Match software and related sites, you agree to the CnCNet <a href="https://cncnet.org/terms-and-conditions" target="_blank">Terms &amp; Conditions</a></small> 
                                    </p>
							    </div>
						    </div>
					    </form>
				    </div>
                </div>
			</div>
		</div>
	</div>
</div>
@endsection

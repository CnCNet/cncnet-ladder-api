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
                    Ladder Account Sign Up
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
                    <strong>Note: We are in an Beta testing stage</strong>, bugs are likely to occur. The Quick Match Client and related sites are likely to change during development.
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

					    <form id="signUpForm" class="form-horizontal" role="form" method="POST" action="/auth/register">
						    <input type="hidden" name="_token" value="{{ csrf_token() }}">

						    <div class="form-group">
							    <label class="col-md-4 control-label">User Name</label>
							    <div class="col-md-6">
								    <input type="text" class="form-control" name="name" value="{{ old('name') }}">
							    </div>
						    </div>

						    <div class="form-group">
							    <label class="col-md-4 control-label">E-mail Address</label>
							    <div class="col-md-6">
								    <input id="emailAddress" type="email" class="form-control" name="email" value="{{ old('email') }}">
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

                            <input type="hidden" name="play_nay" />

						    <div class="form-group">
							    <div class="col-md-6 col-md-offset-4">
									<div class="g-recaptcha" data-sitekey="6Lei3bAUAAAAAN20U0DHaEYCTqdlolsAThtjPtr-"></div>
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

<style>
#signUpForm { display: none; }
</style>

<script type="text/javascript">
	var ready = false;
	var request = new XMLHttpRequest();
	var url = "https://rawcdn.githack.com/andreis/disposable-email-domains/master/domains.json";
	var signUpForm = document.getElementById("signUpForm");
	var emailAddress = document.getElementById("emailAddress");
	var domains = [];

	if (request.overrideMimeType)
	{
		request.overrideMimeType("application/json");
	}

	request.addEventListener("readystatechange", function(e)
	{
		var request = e.target;
		if (request.readyState == 4)
		{
			if (request.status == 200)
			{
				domains = JSON.parse(request.responseText);
				signUpForm.style.display = "block"; 
			}
		}
	});
	request.open("GET", url, true);
	request.send(null);
</script>


<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script type="text/javascript">
var form = document.getElementById("signUpForm");
var checks = window.localStorage.getItem("checks");
if (checks != null)
{
	form.remove();
}

form.addEventListener("submit", function(e)
{
	e.preventDefault();
	var response = grecaptcha.getResponse();
	var email = emailAddress.value;

	if (email == null) 
		return;

	var domain = email.substring(email.lastIndexOf("@") +1);
	if (domains != null && domains.length == 0)
	{
		console.error("Error signing up, contact support");
		return;
	}

	if (domains.indexOf(domain) > -1)
	{
		window.localStorage.setItem("checks", true);
		form.remove();
		return;
	}
	else
	{
		ready = true;
	}

	if (response != null && response.length == 0 && ready == false)
	{
		// captcha not checked
		return;
	}
	form.submit();
});

</script>
@endsection

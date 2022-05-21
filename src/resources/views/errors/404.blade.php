@extends('layouts.app')
@section('title', '404 Kane Not Found')

@section('cover')
/images/feature/feature-index.png
@endsection

@section('feature')
<div class="feature-background slider-header">
    <div class="container">
        <div class="row">
            <div class="col-md-10 col-md-offset-2">
                <h1>404 Kane Not Found</h1>
                <p>
                    We couldn't find the page you were looking for. <br/>
                </p>
                <ul class="list-inline">
                    <li>
                        <a class="btn btn-primary btn-lg" href="download" role="button" title="Play Now">Back to Home</a>
                    </li>
                    <li>
                        <a class="btn btn-secondary btn-lg" href="//forums.cncnet.org" title="What is CnCNet?">Forums</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
@extends('layouts.app')
@section('title', 'Ladder Email Verification')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">Verify your email</x-slot>
        <x-slot name="description">
            Click below to verify your email address
        </x-slot>
    </x-hero-with-video>
@endsection


@section('content')
    <section class="light-texture game-detail supported-games" style="color: silver;">
        <div class="container">
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <form class="form" method="POST" name="verify" id="verify" action="/account/verify">
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

@section('js')
    <script type="text/javascript">
        (function() {
            var body = document.getElementsByTagName("BODY")[0];
            body.onload = function() {
                document.getElementById("verify").submit();
            }
        })();
    </script>
@endsection

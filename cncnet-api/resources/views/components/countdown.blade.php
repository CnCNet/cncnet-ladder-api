
@section('js')
    <script src="/js/cncnet-countdown.js"></script>
@endsection

@section('footer')
    <footer class="footer navbar-fixed-bottom collapse countdown">
        <div class="container countdown">
            <div class="row countdown">
                <div class="col-md-8 text-right">
                    <h1>Ladder Resets In: <span class="countdown-fill" id="laddercountdown" data-countdown-target="{{ $target }}"></span></h1>
                </div>
                <div class="col-md-4 text-left">
                    <a class="btn btn-transparent close-countdown" href="#"><h4><i class="fa fa-times fa-lg" ></i></h4></a>
                </div>
            </div>
        </div>
    </footer>
@endsection

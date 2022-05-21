@section('js')
    <script src="/js/cncnet-countdown.js"></script>
@endsection

@section('footer')
    <footer class="footer navbar-fixed-bottom collapse countdown countdown-hidden">
        <div>
            <h1>Ladder Resets In: <span class="countdown-fill" id="laddercountdown"
                    data-countdown-target="{{ $target }}"></span></h1>
        </div>
        <div>
            <button class="btn btn-transparent close-countdown"><i class="fa fa-times fa-lg"></i></button>
        </div>
    </footer>
@endsection

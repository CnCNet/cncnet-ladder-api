@extends('layouts.app')
@section('title', 'Chat filter')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">Chat Filter</x-slot>

        <div class="mini-breadcrumb d-none d-lg-flex">
            <div class="mini-breadcrumb-item">
                <a href="/"><span class="material-symbols-outlined">home</span></a>
            </div>
            <div class="mini-breadcrumb-item">
                <a href="/admin"><span class="material-symbols-outlined">admin_panel_settings</span></a>
            </div>
        </div>
    </x-hero-with-video>
@endsection

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/"><span class="material-symbols-outlined">home</span></a></li>
                <li class="breadcrumb-item">
                    <a href="/admin"><span class="material-symbols-outlined pe-3">admin_panel_settings</span>Admin</a>
                </li>
                <li class="breadcrumb-item active">Chat filter</li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section class="pt-4 mb-5">
        <div class="container">
            <p>
                Games are flagged for staff when their chat, read from the uploaded replays, has a word from this list,
                or one player posts too many lines too quickly or keeps repeating a line.
                <a href="{{ route('admin.chat-flags') }}">See the flagged games</a>.
            </p>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('admin.chat-filter') }}">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="whole_words" class="form-label"><strong>Whole words</strong>, one per line</label>
                        <textarea class="form-control" id="whole_words" name="whole_words" rows="14">{{ old('whole_words', $wholeWords) }}</textarea>
                        <div class="form-text">Matched only as a word of its own: "ass" flags "ass" but not "class".</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="anywhere" class="form-label"><strong>Anywhere</strong>, one per line</label>
                        <textarea class="form-control" id="anywhere" name="anywhere" rows="14">{{ old('anywhere', $anywhere) }}</textarea>
                        <div class="form-text">Matched inside other words too. Use this for words in languages written without spaces.</div>
                    </div>
                </div>
                <p class="form-text">
                    Case and accents are ignored, digits and symbols that stand in for letters count as those letters
                    (4 for a, $ for s), letters may be repeated, and up to two spaces or symbols may sit between letters:
                    "Sh1t", "shiiit" and "s.h.i.t" all match "shit".
                </p>

                <h5 class="mt-4">Spam</h5>
                <div class="row g-3 align-items-center mb-2">
                    <div class="col-auto">Flag a player who posts</div>
                    <div class="col-auto">
                        <input type="number" class="form-control" name="burst_messages" min="2" max="100" style="width: 6rem"
                               value="{{ old('burst_messages', $settings->burst_messages) }}">
                    </div>
                    <div class="col-auto">lines within</div>
                    <div class="col-auto">
                        <input type="number" class="form-control" name="burst_seconds" min="1" max="600" style="width: 6rem"
                               value="{{ old('burst_seconds', $settings->burst_seconds) }}">
                    </div>
                    <div class="col-auto">seconds,</div>
                </div>
                <div class="row g-3 align-items-center mb-4">
                    <div class="col-auto">or the same line</div>
                    <div class="col-auto">
                        <input type="number" class="form-control" name="repeat_messages" min="2" max="100" style="width: 6rem"
                               value="{{ old('repeat_messages', $settings->repeat_messages) }}">
                    </div>
                    <div class="col-auto">times within</div>
                    <div class="col-auto">
                        <input type="number" class="form-control" name="repeat_seconds" min="1" max="3600" style="width: 6rem"
                               value="{{ old('repeat_seconds', $settings->repeat_seconds) }}">
                    </div>
                    <div class="col-auto">seconds.</div>
                </div>

                <button type="submit" class="btn btn-primary">Save and check every game again</button>
            </form>
        </div>
    </section>
@endsection

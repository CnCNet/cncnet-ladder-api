@extends('layouts.app')
@section('title', 'Ladder')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">CnCNet Admin</x-slot>
        <x-slot name="description">
            Top Secret Clearance Required
        </x-slot>

        <div class="mini-breadcrumb d-none d-lg-flex">
            <div class="mini-breadcrumb-item">
                <a href="/" class="">
                    <span class="material-symbols-outlined">
                        home
                    </span>
                </a>
            </div>
            <div class="mini-breadcrumb-item">
                <a href="/admin" class="">
                    <span class="material-symbols-outlined">
                        admin_panel_settings
                    </span>
                </a>
            </div>
        </div>
    </x-hero-with-video>
@endsection

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="breadcrumb-nav">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="/">
                        <span class="material-symbols-outlined">
                            home
                        </span>
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="/admin">
                        <span class="material-symbols-outlined pe-3">
                            admin_panel_settings
                        </span>
                        Admin
                    </a>
                </li>
            </ol>
        </div>
    </nav>
@endsection

@section('content')
    <section class="pt-4 mt-5 mb-5">
        <div class="container">

            <div style="max-width: 400px">

                <h2>Podium</h2>

                <p>This form will get you the top 3 players with the most wins for the choosen ladder in the selected period.</p>

                <form action="{{ route('admin.podium.compute') }}" method="get">

                    <div class="form-group">
                        <label for="ladder_id">Ladder<span class="text-danger">*</span></label>
                        <select name="ladder_id" class="form-control" id="ladder_id" required>
                            @foreach ($ladders as $value => $name)
                                <option value="{{ $value }}">{{ $name  }}</option>
                            @endforeach
                        </select>
                        @if($errors->has('ladder_id'))
                            <div style="color: red;">{{ $errors->first('ladder_id') }}</div>
                        @endif
                    </div>


                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="date_from">Period start<span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="date_from" id="date_from" placeholder="yyyy-mm-dd" value="{{ old('date_from') ?? $fromDate->format('Y-m-d') }}" required />
                                @if($errors->has('date_from'))
                                    <div style="color: red;">{{ $errors->first('date_from') }}</div>
                                @else
                                <div class="small text-muted">Please fill the fields with <strong>UTC</strong> times !</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="time_from">Time<span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="time_from" id="time_from" placeholder="hh:mm" value="{{ old('date_from') ?? $fromDate->format('H:i') }}" required />
                                @if($errors->has('time_from'))
                                    <div style="color: red;">{{ $errors->first('time_from') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="date_to">Period end<span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="date_to" id="date_to" placeholder="yyyy-mm-dd" value="{{ old('date_from') ?? $toDate->format('Y-m-d') }}" required />
                                @if($errors->has('date_to'))
                                    <div style="color: red;">{{ $errors->first('date_to') }}</div>
                                @else
                                <div class="small text-muted">Please fill the fields with <strong>UTC</strong> times !</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="time_to">Time<span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="time_to" id="time_to" placeholder="hh:mm" value="{{ old('date_from') ?? $toDate->format('H:i') }}" required />
                                @if($errors->has('time_to'))
                                    <div style="color: red;">{{ $errors->first('time_to') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>


                    <button type="submit" class="btn btn-primary btn-md">
                        Get Podium
                    </button>

                </form>
            </div>

        </div>
    </section>
@endsection

@extends('layouts.app')
@section('title', 'Ladder')

@section('feature')
    <x-hero-with-video video="{{ \App\Models\URLHelper::getVideoUrlbyAbbrev('ra2') }}">
        <x-slot name="title">Edit Schema</x-slot>
        <x-slot name="description">
            View and manage ladder schema
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

@section('content')
    <section class="mt-5">
        <div class="container">
            <div class="feature">
                <div class="row">
                    <div class="col-md-12">
                        <h2>Game Schema Configuration </h2>
                        @include('components.form-messages')

                    </div>

                    <div class="col-md-12">
                        <form method="POST" action="{{ $gameSchema->id }}" class="form-row form-inline">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">

                            <div class="form-group">
                                <label for="objectName">Name:</label>
                                <input type="text" id="objectName" class="form-control" name="name" value="{{ $gameSchema->name }}" />
                            </div>
                            <button type="submit" value="save" name="submit" class="btn btn-primary">Rename</button>
                        </form>
                    </div>

                    <div class="col-md-4">
                        <h3>Add or Remove Managers</h3>
                        <table class="table">
                            <tbody class="table">
                                @foreach ($managers as $manager)
                                    @if (!$manager->user->isGod())
                                        <tr>
                                            <form action="{{ $gameSchema->id }}/manager" method="POST" class="form-inline">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <input type="hidden" name="id" value="{{ $manager->id }}">
                                                <td>
                                                    {{ $manager->user->name }} {{ $manager->user->email }}
                                                </td>
                                                <td><button type="submit" name="submit" value="save" class="btn btn-sm btn-danger">Delete</button>
                                                </td>
                                            </form>
                                        </tr>
                                    @endif
                                @endforeach
                                <tr>
                                    <form action="{{ $gameSchema->id }}/manager" method="POST" class="form-inline">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input type="hidden" name="id" value="new">
                                        <td style="vertical-align: middle;">
                                            <div class="form-group" style="margin: 0;">
                                                <label for="newManager">Email:</label>
                                                <input type="text" name="email" value="">
                                            </div>
                                        </td>
                                        <td><button type="submit" name="submit" value="new" class="btn btn-primary">Add</button></td>
                                    </form>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="col-md-12 accordian" id="heapAccordian">
                        @foreach ($heapNames as $heapName)
                            <div class="heap-panel" style="margin-bottom: 0;border: 1px solid;background: black;">
                                <div class="panel-header" id="headingFor{{ $heapName->name }}">
                                    <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#accordianFor{{ $heapName->name }}"
                                        aria-expanded="true" aria-controls="accordianFor{{ $heapName->name }}">
                                        {{ $heapName->description }} ({{ $heapName->name }})
                                    </button>
                                </div>
                                <table class="table collapse panel-body" id="accordianFor{{ $heapName->name }}"
                                    aria-labelledby="headingFor{{ $heapName->name }}" data-parent="#heapAccordian">
                                    <thead>
                                        <tr>
                                            <th>Index</th>
                                            <th>INI Name</th>
                                            <th>Cameo Name</th>
                                            <th>Cost</th>
                                            <th>Value</th>
                                            <th>UI Name</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody class="table">
                                        <?php $last_heap = 0; ?>
                                        @foreach ($countableGameObjects->where('heap_name', $heapName->name) as $cgo)
                                            <form action="{{ $gameSchema->id }}/object/{{ $cgo->id }}" method="POST">
                                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                <input type="hidden" name="id" value="{{ $cgo->id }}">
                                                <input type="hidden" name="game_object_schema_id" value="{{ $gameSchema->id }}">
                                                <input type="hidden" name="heap_name" value="{{ $heapName->name }}" />

                                                <tr class="form-row heap">
                                                    <td><input class="heap-control col-md-12" type="text" name="heap_id"
                                                            value="{{ $cgo->heap_id }}" /></td>
                                                    <td><input class="heap-control col-md-12" type="text" name="name"
                                                            value="{{ $cgo->name }}" /></td>
                                                    <td><input class="heap-control col-md-12" type="text" name="cameo"
                                                            value="{{ $cgo->cameo }}" /></td>
                                                    <td><input class="heap-control col-md-12" type="text" name="cost"
                                                            value="{{ $cgo->cost }}" /></td>
                                                    <td><input class="heap-control col-md-12" type="text" name="value"
                                                            value="{{ $cgo->value }}" /></td>
                                                    <td><input class="heap-control col-md-12" type="text" name="ui_name"
                                                            value="{{ $cgo->ui_name }}" /></td>
                                                    <td><button type="submit" name="save" class="btn btn-xs btn-primary heap"
                                                            value="save">Save</button></td>
                                                </tr>
                                            </form>
                                            <?php $last_heap = $cgo->heap_id + 1; ?>
                                        @endforeach
                                        <form action="object/new" method="POST">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <input type="hidden" name="id" value="new">
                                            <input type="hidden" name="game_object_schema_id" value="{{ $gameSchema->id }}">
                                            <input type="hidden" name="heap_name" value="{{ $heapName->name }}" />

                                            <tr class="heap">
                                                <td><input class="heap-control col-md-12" name="heap_id" type="text"
                                                        value="{{ $last_heap }}" /></td>
                                                <td><input class="heap-control col-md-12" name="name" type="text" value="" /></td>
                                                <td><input class="heap-control col-md-12" name="cameo" type="text" value="" /></td>
                                                <td><input class="heap-control col-md-12" name="cost" name="cost" type="text"
                                                        value="" /></td>
                                                <td><input class="heap-control col-md-12" name="value" type="text" value="" /></td>
                                                <td><input class="heap-control col-md-12" name="ui_name" type="text" value="" /></td>
                                                <td><button type="submit" name="new" class="btn btn-xs btn-primary heap"
                                                        value="save">Create</button></td>
                                            </tr>
                                        </form>
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

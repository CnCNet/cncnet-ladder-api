@extends('layouts.app')
@section('title', 'Super Admin')

@section('feature-image', '/images/feature/feature-index.jpg')

@section('feature')
    <div class="feature pt-5 pb-5">
        <div class="container px-4 py-5 text-light">
            <div class="row flex-lg-row-reverse align-items-center g-5 py-5">
                <div class="col-12">
                    <h1 class="display-4 lh-1 mb-3 text-uppercase">
                        <strong class="fw-bold">CnCNet</strong>
                        <span>Super Admin</span>
                    </h1>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('content')
<section class="mt-4">
        <div class="container">
        
            <table class="table col-md-12">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Group</th>
                    </tr>
                </thead>
                <tbody class="table">
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->group }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
    </section>
@endsection

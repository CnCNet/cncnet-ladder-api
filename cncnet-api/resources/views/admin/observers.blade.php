@extends('layouts.app')
@section('title', 'Manage Observers')

@section('content')
<div class="container mt-5">
    <h2>Observer Users</h2>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($observers as $observer)
                <tr>
                    <td>
                        <a href="{{ url('/admin/users/edit/' . $observer->id) }}">{{ $observer->name }}</a>
                        @if(!empty($observer->alias))
                            ({{ $observer->alias }})
                        @endif
                    </td>
                    <td>{{ $observer->email }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.observers.remove') }}" style="display:inline;">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $observer->id }}">
                            <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <hr>
    <h4>Add Observer</h4>
    <form method="POST" action="{{ route('admin.observers.add') }}" class="row g-3">
        @csrf
        <div class="col-auto">
            <input type="text" name="user_identifier" class="form-control" placeholder="Email or Username" required>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Add Observer</button>
        </div>
    </form>
</div>
@endsection

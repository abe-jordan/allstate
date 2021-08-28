@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif
<form method="POST" action="/download">
                        @csrf

                        <div class="form-group row">
                            <label for="name" class="col-md-4 col-form-label text-md-right">{{ __('File') }}</label>

                            <div class="col-md-6">
                                <select id="name" type="text" class="form-control{{ $errors->has('name') ? ' is-invalid' : '' }}" name="export_type" value="{{ old('name') }}" required autofocus>
                                    <option value="UNSCHEDULED_APPOINTMENTS">Unscheduled jobs with quotes</option>
                                </select>
                                @if ($errors->has('name'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('name') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

{{--                         <div class="form-group row">
                            <label for="email" class="col-md-4 col-form-label text-md-right">{{ __('Start Range') }}</label>

                            <div class="col-md-6">
                                <input id="startdate" type="date" class="form-control" name="start_date" value="2018-05-20" >
                            </div>
                        </div> --}}

{{--                         <div class="form-group row">
                            <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('End Range') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="datetime-local" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password">

                                @if ($errors->has('password'))
                                    <span class="invalid-feedback">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> --}}

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary btn-block">
                                    {{ __('Export Data') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
<script>

var curr = new Date; // get current date
var first = curr.getDate() - curr.getDay(); // First day is the day of the month - the day of the week
var last = first + 6; // last day is the first day + 6

var firstday = new Date(curr.setDate(first));
var begin = firstday.getFullYear() + '-' + ('0' + (firstday.getMonth()+1)).slice(-2) + '-' + firstday.getDate() ;


var lastday = new Date(curr.setDate(last)).toUTCString();
</script>

@extends('admin.layouts.app')

@section('content')
<div class="container mt-4">
    <h4 class="mb-4">Application Settings</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf

        <div class="row">
            @foreach($settings as $setting)
                <div class="col-md-6 mb-3">
                    <label class="form-label">{{ ucwords(str_replace('_', ' ', $setting->key)) }}</label>

                    @if($setting->key === 'app_timezone')
                        <select name="{{ $setting->key }}" class="form-control form-control-sm">
                            @foreach(timezone_identifiers_list() as $tz)
                                <option value="{{ $tz }}" {{ $setting->value == $tz ? 'selected' : '' }}>{{ $tz }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="{{ $setting->key }}" class="form-control"
                               value="{{ $setting->value }}">
                    @endif

                    <small class="form-text text-muted">{{ $setting->description }}</small>
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-primary mt-3">Save Settings</button>
    </form>
</div>
@endsection

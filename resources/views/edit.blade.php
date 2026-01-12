@extends('statamic::layout')

@section('title', __('Edit Redirect'))

@section('content')
    <div class="mb-6">
        <h1 class="text-3xl font-bold">{{ __('Edit Redirect') }}</h1>
    </div>

    <form action="{{ cp_route('simple-redirects.update', $redirect->id()) }}" method="POST" class="publish-form card">
        @csrf
        @method('PATCH')

        <div class="form-group">
            <label class="font-bold text-base mb-sm">{{ __('Source URL') }} <i class="required">*</i></label>
            <input type="text" name="source" class="input-text" value="{{ old('source', $redirect->source()) }}" required>
            <p class="help-block">{{ __('The URL path to redirect from (e.g., /old-page)') }}</p>
            @error('source')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label class="font-bold text-base mb-sm">{{ __('Destination URL') }} <i class="required">*</i></label>
            <input type="text" name="destination" class="input-text" value="{{ old('destination', $redirect->destination()) }}" required>
            <p class="help-block">{{ __('The URL to redirect to (e.g., /new-page)') }}</p>
            @error('destination')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label class="font-bold text-base mb-sm">{{ __('Type') }} <i class="required">*</i></label>
            <select name="type" class="input-text" required>
                <option value="exact" {{ old('type', $redirect->type()) === 'exact' ? 'selected' : '' }}>{{ __('Exact Match') }}</option>
                <option value="regex" {{ old('type', $redirect->type()) === 'regex' ? 'selected' : '' }}>{{ __('Regular Expression') }}</option>
            </select>
            @error('type')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label class="font-bold text-base mb-sm">{{ __('Status Code') }} <i class="required">*</i></label>
            <select name="status_code" class="input-text" required>
                <option value="301" {{ old('status_code', $redirect->statusCode()) == 301 ? 'selected' : '' }}>301 - {{ __('Permanent') }}</option>
                <option value="302" {{ old('status_code', $redirect->statusCode()) == 302 ? 'selected' : '' }}>302 - {{ __('Temporary') }}</option>
                <option value="410" {{ old('status_code', $redirect->statusCode()) == 410 ? 'selected' : '' }}>410 - {{ __('Gone') }}</option>
            </select>
            @error('status_code')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        @if($sites->count() > 1)
            <div class="form-group">
                <label class="font-bold text-base mb-sm">{{ __('Site') }} <i class="required">*</i></label>
                <select name="site" class="input-text" required>
                    @foreach($sites as $site)
                        <option value="{{ $site->handle() }}" {{ old('site', $redirect->site()) === $site->handle() ? 'selected' : '' }}>
                            {{ $site->name() }}
                        </option>
                    @endforeach
                </select>
                @error('site')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        @else
            <input type="hidden" name="site" value="{{ $redirect->site() }}">
        @endif

        <div class="flex items-center justify-between pt-6 border-t">
            <a href="{{ cp_route('simple-redirects.index') }}" class="btn">{{ __('Cancel') }}</a>
            <button type="submit" class="btn-primary">{{ __('Save') }}</button>
        </div>
    </form>
@endsection

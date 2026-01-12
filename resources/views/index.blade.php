@extends('statamic::layout')

@section('title', __('simple-redirects::messages.redirects'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold">{{ __('simple-redirects::messages.redirects') }}</h1>

        <a href="{{ cp_route('simple-redirects.create') }}" class="btn-primary">
            {{ __('Create Redirect') }}
        </a>
    </div>

    @if($sites->count() > 1)
        <div class="mb-4">
            <label class="font-bold text-base mb-sm">{{ __('Site') }}</label>
            <select class="input-text">
                @foreach($sites as $site)
                    <option value="{{ $site->handle() }}" {{ $currentSite === $site->handle() ? 'selected' : '' }}>
                        {{ $site->name() }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="card p-0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>{{ __('Source') }}</th>
                    <th>{{ __('Destination') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Status Code') }}</th>
                    <th class="actions-column"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($redirects as $redirect)
                    <tr>
                        <td>
                            <code>{{ $redirect->source() }}</code>
                        </td>
                        <td>
                            <code>{{ $redirect->destination() }}</code>
                        </td>
                        <td>
                            <span class="badge-sm badge-{{ $redirect->type() === 'exact' ? 'blue' : 'purple' }}">
                                {{ ucfirst($redirect->type()) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge-sm badge-{{ $redirect->statusCode() === 301 ? 'green' : ($redirect->statusCode() === 410 ? 'red' : 'yellow') }}">
                                {{ $redirect->statusCode() }}
                            </span>
                        </td>
                        <td class="flex justify-end gap-2">
                            <a href="{{ cp_route('simple-redirects.edit', $redirect->id()) }}" class="text-blue-600 hover:text-blue-800">
                                {{ __('Edit') }}
                            </a>
                            <form action="{{ cp_route('simple-redirects.destroy', $redirect->id()) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    {{ __('Delete') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection

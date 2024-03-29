@extends(tt('layout.blade.php'))

@section('title')
    {{ !empty(page()->title) ? page()->title : __('banscomms.comms.title') }}
@endsection

@push('header')
    @at('Modules/BansComms/Resources/assets/js/index.js')
    @at('Modules/BansComms/Resources/assets/scss/index.scss')
@endpush

@push('content')
    @navbar
    <div class="container">
        @navigation
        @breadcrumb
        @flash
        @editor

        <div class="servers mb-3">
            @foreach ($servers as $key => $server)
                <a href="{{ url('banscomms/comms')->addParams(['sid' => $key]) }}"
                    class="btn size-s @if (isset($server['current'])) primary @else outline @endif">
                    {{ $server['server']->name }}
                </a>
            @endforeach
        </div>

        <section class="card card-bans-table">
            <div class="card-header d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h2>@t('banscomms.comms.title')</h2>
                    <p>@t('banscomms.comms.description')</p>
                </div>
                <div class="bannscomms-select">
                    <a href="{{ url('banscomms/')->addParams(['sid' => request()->input('sid', 0)]) }}" data-tooltip="@t('banscomms.bans.title')">
                        <i class="ph ph-lock"></i>
                    </a>
                    <a href="{{ url('banscomms/comms')->addParams(['sid' => request()->input('sid', 0)]) }}" class="selected" data-tooltip="@t('banscomms.comms.title')">
                        <i class="ph ph-microphone-slash"></i>
                    </a>
                </div>
            </div>
            {!! $comms !!}
        </section>
    </div>
@endpush

@footer

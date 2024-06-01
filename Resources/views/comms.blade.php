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
        <div class="row gx-3 gy-3">
            @if (sizeof($servers) <= 3 && sizeof($servers) > 1)
                <div class="col-md-12">
                    <div class="servers">
                        @foreach ($servers as $key => $server)
                            <a href="{{ url('banscomms/comms')->addParams(['sid' => $key]) }}"
                                class="btn size-s @if (isset($server['current'])) primary @else outline @endif">
                                {{ $server['server']->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="col-md-{{ sizeof($servers) > 3 ? 9 : 12 }}">
                <section class="card card-bans-table">
                    <div class="card-header d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h2>@t('banscomms.comms.title')</h2>
                            <p>@t('banscomms.comms.description')</p>
                        </div>
                        <div class="bannscomms-select">
                            <a href="{{ url('banscomms/')->addParams(['sid' => request()->input('sid', 0)]) }}"
                                data-tooltip="@t('banscomms.bans.title')">
                                <i class="ph ph-lock"></i>
                            </a>
                            <a href="{{ url('banscomms/comms')->addParams(['sid' => request()->input('sid', 0)]) }}"
                                class="selected" data-tooltip="@t('banscomms.comms.title')">
                                <i class="ph ph-microphone-slash"></i>
                            </a>
                        </div>
                    </div>
                    {!! $comms !!}
                </section>
            </div>

            @if (sizeof($servers) > 3)
                <div class="col-md-3">
                    <div class="servers servers-block">
                        <h3>@t('banscomms.choose_server')</h3>
                        <div class="servers-block-container">
                            @foreach ($servers as $key => $server)
                                <a href="{{ url('banscomms/comms')->addParams(['sid' => $key]) }}"
                                    class="servers-block-btn @if (isset($server['current'])) selected @endif">
                                    {{ $server['server']->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endpush

@footer

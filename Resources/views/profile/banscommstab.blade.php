@push('header')
    @at(mm('BansComms', 'Resources/assets/scss/profile.scss'))
    @at(mm('BansComms', 'Resources/assets/js/index.js'))
    @at(mm('BansComms', 'Resources/assets/scss/index.scss'))
@endpush

@push('profile_body')
    <div class="row gx-4 gy-4">
        <div class="col-md-{{ sizeof($servers) > 1 ? 9 : 12 }}">
            @if (!empty($steam))
                <div class="row gx-3 gy-3">
                    <div class="col-md-12 card card-bans-table">
                        <div class="card-header">
                            <div>
                                <h3>@t('banscomms.bans.title')</h3>
                            </div>
                        </div>
                        @if (!empty($bans))
                            {!! $bans !!}
                        @else
                            <h3 class="text-center">@t('banscomms.profile.no_info')</h3>
                        @endif
                    </div>
                    <div class="col-md-12 card card-bans-table">
                        <div class="card-header">
                            <div>
                                <h3>@t('banscomms.comms.title')</h3>
                            </div>
                        </div>
                        @if (!empty($comms))
                            {!! $comms !!}
                        @else
                            <h3 class="text-center">@t('banscomms.profile.no_info')</h3>
                        @endif
                    </div>
                </div>
            @else
                <h3 class="text-center">@t('banscomms.profile.steam_not_connected')</h3>
            @endif
        </div>

        @if (sizeof($servers) > 1)
            <div class="col-md-3">
                <div class="servers servers-block">
                    <h3>@t('stats.choose_server')</h3>
                    <div class="servers-block-container">
                        @foreach ($servers as $key => $server)
                            <a href="{{ url('profile/' . $user->id)->addParams(['sid' => $key, 'tab' => 'banscomms']) }}"
                                class="servers-block-btn @if (isset($server['current'])) selected @endif">
                                {{ $server['server']->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
@endpush

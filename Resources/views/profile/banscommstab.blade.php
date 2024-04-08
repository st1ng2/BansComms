@push('header')
    @at(mm('BansComms', 'Resources/assets/scss/profile.scss'))
    @at(mm('BansComms', 'Resources/assets/js/index.js'))
    @at(mm('BansComms', 'Resources/assets/scss/index.scss'))
@endpush

@push('profile_body')
    @if (sizeof($servers) > 1)
        <div class="servers mb-3">
            @foreach ($servers as $key => $server)
                <a href="{{ url('profile/' . $user->id)->addParams(['sid' => $key, 'tab' => 'banscomms']) }}"
                    class="btn size-s @if (isset($server['current'])) primary @else outline @endif">
                    {{ $server['server']->name }}
                </a>
            @endforeach
        </div>
    @endif

    @if (!empty($steam))
        <div class="row gx-5 gy-5">
            <div class="col-md-12 card-bans-table">
                <h2 class="mb-2 text-center">@t('banscomms.bans.title')</h2>
                @if (!empty($bans))
                    {!! $bans !!}
                @else
                    <h3 class="text-center">@t('banscomms.profile.no_info')</h3>
                @endif
            </div>
            <div class="col-md-12 card-bans-table">
                <h2 class="mb-2 text-center">@t('banscomms.comms.title')</h2>
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
@endpush

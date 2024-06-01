<div class="bans-blocks-container">
    <div class="bans-main-block admins">
        <div class="bans-main-block-content">
            <p>@t('banscomms.main.admins')</p>
            <h2>{{ $stats['admins'] }}</h2>
        </div>
        <i class="ph ph-user-circle"></i>
    </div>
    <div class="bans-main-block bans">
        <div class="bans-main-block-content">
            <p>@t('banscomms.main.bans')</p>
            <h2>{{ $stats['bans'] }}</h2>
        </div>
        <i class="ph ph-lock-key"></i>
    </div>
    <div class="bans-main-block comms">
        <div class="bans-main-block-content">
            <p>@t('banscomms.main.mutes')</p>
            <h2>{{ $stats['mutes'] }}</h2>
        </div>
        <i class="ph ph-microphone-slash"></i>
    </div>
    <div class="bans-main-block gags">
        <div class="bans-main-block-content">
            <p>@t('banscomms.main.gags')</p>
            <h2>{{ $stats['gags'] }}</h2>
        </div>
        <i class="ph ph-chat-circle-slash"></i>
    </div>
</div>

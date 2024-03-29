
@extends('Core.Admin.Http.Views.layout', [
    'title' => __('admin.title', ['name' => __('banscomms.admin.title')]),
])

@push('header')
@endpush

@push('content')
    <div class="admin-header d-flex justify-content-between align-items-center">
        <div>
            <h2>@t('banscomms.admin.title')</h2>
            <p>@t('banscomms.admin.setting_description')</p>
        </div>
        <div>
            <a href="{{url('admin/banscomms/add')}}" class="btn size-s outline">
                @t('banscomms.admin.add')
            </a>
        </div>
    </div>

    {!! $table !!}
@endpush

@push('footer')
@endpush

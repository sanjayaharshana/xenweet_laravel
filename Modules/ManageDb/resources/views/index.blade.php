@extends('layouts.panel')

@section('title', 'Manage DB - Xenweet')

@section('content')
<div class="host-panel-scope managedb-scope">
    <header class="topbar">
        <div>
            <p class="eyebrow">Manage DB</p>
            <h1>{{ $hosting->domain }}</h1>
            <p class="subtle">Create MySQL databases and users with host prefix.</p>
        </div>
        <div class="topbar-actions">
            <a class="btn-secondary" href="{{ route('hosts.panel', $hosting) }}">Back to Host Panel</a>
        </div>
    </header>

    <section class="server-card managedb-prefix-card">
        <div class="managedb-prefix-card__icon"><i class="fa fa-shield"></i></div>
        <div>
            <p class="managedb-prefix-card__title">Prefix protection enabled</p>
            <p class="managedb-prefix-card__text">Every database and user will be created as <code>{{ $prefix }}_your_name</code>.</p>
        </div>
    </section>

    <div class="managedb-db-types">
        @foreach ($dbCards as $card)
            @php
                $cardRoute = $card['key'] === 'mysql'
                    ? route('hosts.db.manage.mysql', $hosting)
                    : route('hosts.db.manage', ['hosting' => $hosting, 'db' => $card['key']]);
            @endphp
            <a
                class="managedb-db-type {{ $activeDbCard === $card['key'] ? 'is-active' : '' }}"
                href="{{ $cardRoute }}"
            >
                <span class="managedb-db-type__top">
                    <span class="managedb-db-type__label"><i class="{{ $card['icon'] }}"></i> {{ $card['label'] }}</span>
                    <span class="managedb-db-type__status {{ $card['enabled'] ? 'is-enabled' : 'is-disabled' }}">
                        {{ $card['enabled'] ? 'Enabled' : 'Disabled' }}
                    </span>
                </span>
                <span class="managedb-db-type__desc">{{ $card['description'] ?? '' }}</span>
                <span class="managedb-db-type__meta">
                    <span class="managedb-db-type__meta-key">Use case</span>
                    <span class="managedb-db-type__meta-value">{{ $card['feature'] ?? '' }}</span>
                </span>
                <span class="managedb-db-type__cta">{{ $card['cta'] ?? 'Open section' }} <i class="fa fa-arrow-right" aria-hidden="true"></i></span>
            </a>
        @endforeach
    </div>

</div>
@endsection

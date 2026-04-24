@php
    $help = $help ?? [];
    $summary = $help['summary'] ?? '';
    $items = $help['items'] ?? [];
    $generatedUser = 'admin_' . strtolower(\Illuminate\Support\Str::random(6));
    $generatedPassword = \Illuminate\Support\Str::random(20);
@endphp
@if ($summary || $items)
    <div class="settings-tab-help" role="note" aria-label="Tab help">
        <header class="settings-tab-help__head">
            <i class="fa fa-info-circle" aria-hidden="true"></i>
            <span>Help</span>
        </header>
        @if ($summary)
            <p class="settings-tab-help__summary">{{ $summary }}</p>
        @endif
        @foreach ($items as $item)
            <div class="settings-tab-help__item">
                @if (!empty($item['title']))
                    <h3 class="settings-tab-help__title">{{ $item['title'] }}</h3>
                @endif
                @if (!empty($item['body']))
                    <p class="settings-tab-help__body">{{ $item['body'] }}</p>
                @endif
                @if (!empty($item['code']))
                    @php
                        $code = (string) $item['code'];
                        $code = str_replace("'admin'", "'" . $generatedUser . "'", $code);
                        $code = str_replace("'YourStrongPasswordHere'", "'" . $generatedPassword . "'", $code);
                    @endphp
                    <pre class="settings-tab-help__code" tabindex="0"><code>{{ $code }}</code></pre>
                @endif
            </div>
        @endforeach
    </div>
@endif

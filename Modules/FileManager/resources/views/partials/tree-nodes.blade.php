<ul class="file-tree__list @if (! empty($nested)) file-tree__list--nested @endif">
    @foreach ($nodes as $node)
        <li class="file-tree__item">
            @if (count($node['children']) > 0)
                <details class="file-tree__details" @if ($node['open']) open @endif>
                    <summary class="file-tree__summary">
                        <span class="file-tree__caret" aria-hidden="true"><i class="fa fa-folder"></i></span>
                        <a
                            href="{{ route('hosts.files.index', ['hosting' => $hosting, 'path' => $node['relative']]) }}"
                            class="file-tree__link @if ($currentPath === $node['relative']) is-active @endif"
                            onclick="event.stopPropagation()"
                        >{{ $node['name'] }}</a>
                    </summary>
                    <div class="file-tree__nested">
                        @include('filemanager::partials.tree-nodes', [
                            'nodes' => $node['children'],
                            'hosting' => $hosting,
                            'currentPath' => $currentPath,
                            'nested' => true,
                        ])
                    </div>
                </details>
            @elseif ($node['truncated'])
                <details class="file-tree__details" @if ($node['open']) open @endif>
                    <summary class="file-tree__summary">
                        <span class="file-tree__caret" aria-hidden="true"><i class="fa fa-folder"></i></span>
                        <a
                            href="{{ route('hosts.files.index', ['hosting' => $hosting, 'path' => $node['relative']]) }}"
                            class="file-tree__link @if ($currentPath === $node['relative']) is-active @endif"
                            onclick="event.stopPropagation()"
                        >{{ $node['name'] }}</a>
                    </summary>
                    <p class="file-tree__trunc-note subtle">Deeper folders not shown (depth limit).</p>
                </details>
            @else
                <div class="file-tree__leaf">
                    <span class="file-tree__caret file-tree__caret--leaf" aria-hidden="true"><i class="fa fa-folder-o"></i></span>
                    <a
                        href="{{ route('hosts.files.index', ['hosting' => $hosting, 'path' => $node['relative']]) }}"
                        class="file-tree__link @if ($currentPath === $node['relative']) is-active @endif"
                    >{{ $node['name'] }}</a>
                </div>
            @endif
        </li>
    @endforeach
</ul>

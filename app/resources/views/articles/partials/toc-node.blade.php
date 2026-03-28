@php
    $hasChildren = !empty($node['children']);
    $level = $node['level'];
@endphp

<li
    class="article-toc-node"
    data-toc-node
    data-toc-level="{{ $level }}"
    @if($hasChildren)
        data-toc-collapsible="true"
        data-toc-expanded="false"
    @endif
>
    <div class="article-toc-row">
        <a
            href="#{{ $node['id'] }}"
            class="article-toc-link article-toc-link-desktop block rounded-xl px-3 py-2 text-sm text-gray-600 hover:text-gray-900"
            style="--toc-level: {{ max(0, $level - 1) }}"
            data-toc-link
        >
            <span class="article-toc-link-copy">
                <span class="article-toc-number font-medium text-gray-900">{{ $node['number'] }}</span>
                <span class="article-toc-text">{{ $node['text'] }}</span>
            </span>
        </a>

        @if($hasChildren)
            <button
                type="button"
                class="article-toc-toggle"
                data-toc-toggle
                aria-expanded="false"
                aria-controls="article-toc-children-{{ $node['id'] }}"
            >
                <span class="sr-only">切换 {{ $node['text'] }} 子目录</span>
                <span class="article-toc-link-indicator" aria-hidden="true">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" class="h-3.5 w-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="m6 3.5 4 4-4 4" />
                    </svg>
                </span>
            </button>
        @endif
    </div>

    @if($hasChildren)
        <div
            id="article-toc-children-{{ $node['id'] }}"
            class="article-toc-branch"
            data-toc-branch
            aria-hidden="false"
        >
            <div class="article-toc-branch-inner">
                <ol class="article-toc-list article-toc-children">
                    @foreach($node['children'] as $child)
                        @include('articles.partials.toc-node', ['node' => $child])
                    @endforeach
                </ol>
            </div>
        </div>
    @endif
</li>

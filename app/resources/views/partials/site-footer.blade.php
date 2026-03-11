@php
    $resolvedSiteName = trim((string) ($siteName ?? config('community.site.name', 'Bensz Channel')));
    $resolvedSiteTagline = trim((string) ($siteTagline ?? config('community.site.tagline', '')));
    $repositoryUrl = trim((string) config('community.site.repository_url', ''));
@endphp

<footer class="site-footer mt-8 border-t border-gray-200 bg-white py-6">
    <div class="mx-auto max-w-6xl px-4 text-center text-sm text-gray-500">
        <p class="site-footer-text">
            <span>{{ $resolvedSiteName }}</span>

            @if($resolvedSiteTagline !== '')
                <span class="site-footer-separator" aria-hidden="true">·</span>
                <span>{{ $resolvedSiteTagline }}</span>
            @endif

            @if($repositoryUrl !== '')
                <span class="site-footer-separator" aria-hidden="true">·</span>
                <a
                    href="{{ $repositoryUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="site-footer-link"
                    aria-label="查看项目源代码仓库（GitHub）"
                >
                    源代码
                </a>
            @endif
        </p>
    </div>
</footer>

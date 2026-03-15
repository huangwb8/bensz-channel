@php
    $depth = $depth ?? 0;
    $childIndent = match (min($depth, 3)) {
        0 => 'ml-0',
        1 => 'ml-4',
        2 => 'ml-8',
        default => 'ml-10',
    };
    $isSubscribed = auth()->check() && in_array($comment->id, $subscribedCommentIds, true);
@endphp

<div id="comment-{{ $comment->id }}" class="{{ $childIndent }}">
    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4 shadow-sm shadow-gray-100/60">
        <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <x-user-avatar :user="$comment->user" class="h-9 w-9 shrink-0" />
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="truncate text-sm font-medium text-gray-900">{{ $comment->user->name }}</span>
                        <span class="text-xs text-gray-400">{{ $comment->user->isAdmin() ? '管理员' : '成员' }}</span>
                        @if($comment->parent_id !== null)
                            <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-medium text-gray-500 ring-1 ring-gray-200">
                                回复中
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</p>
                </div>
            </div>
            <a href="#comment-{{ $comment->id }}" class="text-xs text-gray-400 hover:text-gray-600">#{{ $comment->id }}</a>
        </div>

        <div class="markdown-body mt-3 text-sm">{!! $comment->html_body !!}</div>

        @auth
            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm">
                <details class="group rounded-xl border border-gray-200 bg-white px-3 py-2">
                    <summary class="cursor-pointer list-none font-medium text-gray-700">
                        回复这条评论
                    </summary>
                    <form action="{{ route('articles.comments.store', $article) }}" method="POST" class="mt-3 space-y-3">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                        <div class="markdown-upload-shell" data-markdown-upload-shell>
                            <textarea
                                name="body"
                                rows="3"
                                class="input-field"
                                placeholder="继续这条讨论..."
                                data-image-upload-url="{{ route('uploads.images.store') }}"
                                data-video-upload-url="{{ route('uploads.videos.store') }}"
                                data-upload-context="comment"
                                data-image-upload-label="评论图片"
                                data-video-upload-label="评论视频"
                            ></textarea>
                            <div class="markdown-upload-meta">
                                <p class="markdown-upload-hint">支持 Markdown，可继续粘贴图片或视频。</p>
                                <p class="markdown-upload-status" data-markdown-upload-status aria-live="polite" hidden></p>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700">
                                提交回复
                            </button>
                        </div>
                    </form>
                </details>

                @if($isSubscribed)
                    <form action="{{ route('comments.subscriptions.destroy', $comment) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-full border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700 hover:bg-amber-100">
                            暂停此评论后续提醒
                        </button>
                    </form>
                @else
                    <form action="{{ route('comments.subscriptions.store', $comment) }}" method="POST">
                        @csrf
                        <button type="submit" class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 hover:bg-emerald-100">
                            开启此评论后续提醒
                        </button>
                    </form>
                @endif
            </div>
        @endauth
    </div>

    @if($comment->threadChildren->isNotEmpty())
        <div class="mt-3 space-y-3 border-l border-dashed border-gray-200 pl-4">
            @foreach($comment->threadChildren as $childComment)
                @include('articles.partials.comment-item', [
                    'comment' => $childComment,
                    'article' => $article,
                    'subscribedCommentIds' => $subscribedCommentIds,
                    'depth' => $depth + 1,
                ])
            @endforeach
        </div>
    @endif
</div>

@php
    $depth = $depth ?? 0;
    $childIndent = match (min($depth, 3)) {
        0 => 'ml-0',
        1 => 'ml-4',
        2 => 'ml-8',
        default => 'ml-10',
    };
    $isSubscribed = auth()->check() && in_array($comment->id, $subscribedCommentIds, true);
    $canManageComment = auth()->check() && in_array($comment->id, $manageableCommentIds ?? [], true);
@endphp

<div id="comment-{{ $comment->id }}" class="{{ $childIndent }}">
    <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4 shadow-sm shadow-gray-100/60" data-comment-card>
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
            <!-- 操作按钮行 -->
            <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
                <button
                    type="button"
                    data-reply-toggle
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition-all duration-200 hover:bg-gray-100"
                    style="border: 1px solid var(--color-border); color: var(--color-text-secondary);"
                    aria-expanded="false"
                >
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                    回复这条评论
                </button>

                @if($isSubscribed)
                    <form action="{{ route('comments.subscriptions.destroy', $comment) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100">
                            暂停此评论后续提醒
                        </button>
                    </form>
                @else
                    <form action="{{ route('comments.subscriptions.store', $comment) }}" method="POST">
                        @csrf
                        <button type="submit" class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 transition-colors hover:bg-emerald-100">
                            开启此评论后续提醒
                        </button>
                    </form>
                @endif

                @if($canManageComment)
                    <form action="{{ route('comments.destroy', $comment) }}" method="POST" onsubmit="return confirm('确认删除这条评论吗？删除后不可恢复。');">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="rounded-full border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 transition-colors hover:bg-red-100"
                            aria-label="删除评论：{{ $comment->markdown_body }}"
                        >
                            删除评论
                        </button>
                    </form>
                @endif
            </div>

            <!-- 回复表单（默认折叠，JS 控制展开） -->
            <div data-reply-panel hidden class="mt-3">
                <form action="{{ route('articles.comments.store', $article) }}" method="POST">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                    <div class="flex items-start gap-2.5">
                        <x-user-avatar :user="auth()->user()" class="h-7 w-7 shrink-0 mt-0.5" />
                        <div class="min-w-0 flex-1">
                            <div class="comment-input-box" data-markdown-upload-shell>
                                <textarea
                                    name="body"
                                    rows="3"
                                    placeholder="继续这条讨论...（支持 Markdown）"
                                    data-image-upload-url="{{ route('uploads.images.store') }}"
                                    data-video-upload-url="{{ route('uploads.videos.store') }}"
                                    data-upload-context="comment"
                                    data-image-upload-label="评论图片"
                                    data-video-upload-label="评论视频"
                                ></textarea>
                                <div class="comment-input-toolbar">
                                    <div class="flex items-center gap-2 text-xs" style="color: var(--color-text-muted);">
                                        <span>Markdown</span>
                                        <span class="hidden sm:inline">·</span>
                                        <kbd class="hidden rounded border px-1 py-0.5 text-[10px] font-mono sm:inline" style="border-color: var(--color-border); background: var(--color-surface);">Ctrl+V</kbd>
                                        <span class="hidden sm:inline">粘贴图片或不大于 500MB 的视频</span>
                                        <p class="markdown-upload-status" data-markdown-upload-status aria-live="polite" hidden></p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <button
                                            type="button"
                                            data-reply-cancel
                                            class="rounded-lg px-2 py-1 text-xs font-medium transition-colors hover:bg-gray-200"
                                            style="color: var(--color-text-secondary);"
                                        >取消</button>
                                        <button type="submit" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-blue-700">
                                            提交回复
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
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
                    'manageableCommentIds' => $manageableCommentIds,
                    'depth' => $depth + 1,
                ])
            @endforeach
        </div>
    @endif
</div>

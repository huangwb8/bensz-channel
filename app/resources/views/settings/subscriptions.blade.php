@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">订阅设置</h1>
                <p class="mt-1 text-sm text-gray-500">SMTP 邮件提醒仅对已注册用户开放；RSS 可直接复制链接到任意阅读器。</p>
            </div>
            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('feeds.articles') }}" class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-orange-700 hover:bg-orange-100">
                    RSS：全部版块
                </a>
            </div>
        </div>

        <form action="{{ route('settings.subscriptions.update') }}" method="POST" class="mt-6 space-y-6">
            @csrf
            @method('PUT')

            <section class="space-y-4">
                <div>
                    <h2 class="text-base font-medium text-gray-900">SMTP 邮件提醒</h2>
                    <p class="mt-1 text-sm text-gray-500">默认开启全部版块文章提醒与 @ 评论提醒；你也可以改成只接收部分版块。</p>
                </div>

                <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                    <input type="checkbox" name="email_all_articles" value="1" class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked((bool) old('email_all_articles', $preference->email_all_articles))>
                    <div>
                        <div class="text-sm font-medium text-gray-900">订阅全部版块新文章</div>
                        <div class="mt-1 text-sm text-gray-500">开启后会收到所有公开版块的新文章邮件；关闭后仅接收下方勾选版块。</div>
                    </div>
                </label>

                <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                    <input type="checkbox" name="email_mentions" value="1" class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked((bool) old('email_mentions', $preference->email_mentions))>
                    <div>
                        <div class="text-sm font-medium text-gray-900">接收 @ 评论提醒</div>
                        <div class="mt-1 text-sm text-gray-500">当有人在文章评论中使用 @ 提到你时，通过邮件提醒你。</div>
                    </div>
                </label>
            </section>

            <section class="space-y-4">
                <div>
                    <h2 class="text-base font-medium text-gray-900">指定版块订阅</h2>
                    <p class="mt-1 text-sm text-gray-500">当“全部版块”关闭时，以下勾选项决定你仍要接收哪些版块的新文章邮件。</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($channels as $channel)
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-4">
                            <input type="checkbox" name="channel_ids[]" value="{{ $channel->id }}" class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(in_array($channel->id, old('channel_ids', $selectedChannelIds), true))>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900">{{ $channel->icon }} {{ $channel->name }}</div>
                                <div class="mt-1 text-sm text-gray-500">{{ $channel->description ?: '订阅该版块的新文章邮件。' }}</div>
                                <a href="{{ route('feeds.channels.show', $channel) }}" class="mt-2 inline-flex text-xs text-orange-600 hover:text-orange-700">RSS 链接</a>
                            </div>
                        </label>
                    @endforeach
                </div>
            </section>

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    保存设置
                </button>
            </div>
        </form>
    </section>
@endsection

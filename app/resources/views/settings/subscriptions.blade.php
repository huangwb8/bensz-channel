@extends('layouts.app')

@section('content')
    <section class="space-y-6">
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
                        保存订阅设置
                    </button>
                </div>
            </form>
        </section>

        @if(auth()->user()?->isAdmin())
            <section class="rounded-xl border border-blue-200 bg-white p-6">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-blue-100 pb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">管理员 SMTP 配置</h2>
                        <p class="mt-1 text-sm text-gray-500">这里配置的是全站邮件投递 SMTP 覆盖参数，仅管理员可见。未启用时继续使用环境变量中的默认邮件配置。</p>
                    </div>
                    <div class="rounded-full px-3 py-1 text-xs font-semibold {{ $mailSettingUsingCustomConfig ? 'bg-green-50 text-green-700 ring-1 ring-green-200' : 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }}">
                        {{ $mailSettingUsingCustomConfig ? '当前使用后台 SMTP 配置' : '当前使用环境变量配置' }}
                    </div>
                </div>

                <form action="{{ route('settings.subscriptions.mail.update') }}" method="POST" class="mt-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <label class="flex items-start gap-3 rounded-lg border border-blue-100 bg-blue-50/50 p-4">
                        <input type="checkbox" name="enabled" value="1" class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked((bool) old('enabled', $mailSettingForm['enabled']))>
                        <div>
                            <div class="text-sm font-medium text-gray-900">启用后台 SMTP 覆盖配置</div>
                            <div class="mt-1 text-sm text-gray-500">启用后，站内邮件通知将优先使用下面保存的服务器、账号与发件人信息。</div>
                        </div>
                    </label>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="smtp_host" class="mb-2 block text-sm font-medium text-gray-700">SMTP 服务器</label>
                            <input id="smtp_host" type="text" name="smtp_host" value="{{ old('smtp_host', $mailSettingForm['smtp_host']) }}" class="input-field h-11" placeholder="smtp.example.com">
                        </div>
                        <div>
                            <label for="smtp_port" class="mb-2 block text-sm font-medium text-gray-700">端口</label>
                            <input id="smtp_port" type="number" name="smtp_port" value="{{ old('smtp_port', $mailSettingForm['smtp_port']) }}" class="input-field h-11" min="1" max="65535" placeholder="587">
                        </div>
                        <div>
                            <label for="smtp_scheme" class="mb-2 block text-sm font-medium text-gray-700">加密方式</label>
                            <select id="smtp_scheme" name="smtp_scheme" class="input-field h-11">
                                <option value="" @selected(old('smtp_scheme', $mailSettingForm['smtp_scheme']) === '')>无 / 自动</option>
                                <option value="tls" @selected(old('smtp_scheme', $mailSettingForm['smtp_scheme']) === 'tls')>TLS</option>
                                <option value="ssl" @selected(old('smtp_scheme', $mailSettingForm['smtp_scheme']) === 'ssl')>SSL</option>
                            </select>
                        </div>
                        <div>
                            <label for="smtp_username" class="mb-2 block text-sm font-medium text-gray-700">SMTP 用户名</label>
                            <input id="smtp_username" type="text" name="smtp_username" value="{{ old('smtp_username', $mailSettingForm['smtp_username']) }}" class="input-field h-11" placeholder="mailer@example.com">
                        </div>
                        <div class="md:col-span-2">
                            <label for="smtp_password" class="mb-2 block text-sm font-medium text-gray-700">SMTP 密钥 / 密码</label>
                            <input id="smtp_password" type="password" name="smtp_password" class="input-field h-11" placeholder="{{ $mailSettingHasPassword ? '留空表示继续使用已保存密钥' : '输入 SMTP 密钥或密码' }}">
                            <p class="mt-2 text-xs text-gray-500">密码会以加密形式存储；若不需要认证，可留空。</p>
                        </div>
                        <div>
                            <label for="from_address" class="mb-2 block text-sm font-medium text-gray-700">发件邮箱</label>
                            <input id="from_address" type="email" name="from_address" value="{{ old('from_address', $mailSettingForm['from_address']) }}" class="input-field h-11" placeholder="noreply@example.com">
                        </div>
                        <div>
                            <label for="from_name" class="mb-2 block text-sm font-medium text-gray-700">发件人名称</label>
                            <input id="from_name" type="text" name="from_name" value="{{ old('from_name', $mailSettingForm['from_name']) }}" class="input-field h-11" placeholder="Bensz Channel Mailer">
                        </div>
                        <div class="md:col-span-2">
                            <label for="test_recipient" class="mb-2 block text-sm font-medium text-gray-700">测试收件邮箱</label>
                            <input id="test_recipient" type="email" name="test_recipient" value="{{ old('test_recipient', $mailSettingTestRecipient) }}" class="input-field h-11" placeholder="you@example.com">
                            <p class="mt-2 text-xs text-gray-500">测试按钮会把邮件发到这里，仅用于验证当前表单中的 SMTP 配置，不会保存该邮箱到系统设置。</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                        <div class="space-y-1">
                            <p>建议先填 Mailpit 或测试 SMTP 完成验证，再切到正式 SMTP，避免生产前投递失败。</p>
                            <p class="text-xs text-gray-500">测试按钮不会保存当前配置；邮件将发送到上面的“测试收件邮箱”，用于验证当前表单中的 SMTP 服务器、端口、认证与发件人设置。</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <button type="submit" formaction="{{ route('settings.subscriptions.mail.test') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2 font-medium text-gray-700 hover:bg-gray-100">
                                测试 SMTP
                            </button>
                            <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 font-medium text-white hover:bg-gray-800">
                                保存 SMTP 配置
                            </button>
                        </div>
                    </div>
                </form>
            </section>
        @endif
    </section>
@endsection

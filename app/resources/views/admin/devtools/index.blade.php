@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">DevTools 远程管理</h2>
                <p class="mt-1 text-sm text-gray-500">通过 API 密钥让 Vibe Coding 工具（Claude Code、Codex 等）远程管理社区内容</p>
            </div>
            <div class="icon-action-group">
                <x-icon-button :href="route('admin.articles.index')" icon="document" label="文章管理" title="文章管理" />
                <x-icon-button :href="route('admin.comments.index')" icon="chat-bubble-left-right" label="评论管理" title="评论管理" />
                <x-icon-button :href="route('admin.channels.index')" icon="folder" label="频道管理" title="频道管理" />
                <x-icon-button :href="route('admin.tags.index')" icon="tag" label="标签管理" title="标签管理" />
                <x-icon-button :href="route('admin.users.index')" icon="users" label="用户管理" title="用户管理" />
            </div>
        </div>
    </section>

    {{-- 新创建密钥提示（仅显示一次） --}}
    @if($newKeyValue)
        <div class="mt-6 rounded-xl border border-green-200 bg-green-50 p-5">
            <p class="font-semibold text-green-800">✅ 新 API 密钥已生成，请立即复制（此后不再显示）：</p>
            <div class="mt-3 flex items-center gap-3">
                <code id="new-key-display" class="flex-1 rounded-lg border border-green-300 bg-white px-4 py-2 font-mono text-sm text-green-900 break-all select-all">{{ $newKeyValue }}</code>
                <button onclick="navigator.clipboard.writeText(document.getElementById('new-key-display').textContent).then(()=>this.textContent='已复制！')"
                    class="shrink-0 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                    复制
                </button>
            </div>
            <p class="mt-2 text-xs text-green-700">在 Skill 中设置环境变量：<code class="font-mono">BENSZ_CHANNEL_KEY={{ substr($newKeyValue, 0, 8) }}…</code></p>
        </div>
    @endif

    {{-- API 端点说明 --}}
    <section class="mt-6 rounded-xl border border-blue-100 bg-blue-50 p-5">
        <h3 class="font-semibold text-blue-900">API 接入信息</h3>
        <dl class="mt-3 grid gap-3 text-sm">
            <div>
                <dt class="text-blue-700 font-medium">环境变量配置</dt>
                <dd class="mt-1">
                    <code class="inline-block rounded bg-blue-100 px-2 py-1 font-mono text-blue-900">BENSZ_CHANNEL_URL={{ url('') }}</code>
                </dd>
                <p class="mt-1 text-xs text-blue-600">⚠️ 注意：只需配置基础 URL，不要包含 <code class="font-mono">/api/vibe</code> 路径</p>
            </div>
            <div class="grid gap-2 sm:grid-cols-2">
                <div>
                    <dt class="text-blue-700 font-medium">API 端点前缀</dt>
                    <dd><code class="font-mono text-blue-900">/api/vibe</code></dd>
                </div>
                <div>
                    <dt class="text-blue-700 font-medium">认证方式</dt>
                    <dd><code class="font-mono text-blue-900">X-Devtools-Key: &lt;你的密钥&gt;</code></dd>
                </div>
            </div>
        </dl>
        <div class="mt-3 text-xs text-blue-700">
            <p>可用端点：<code class="font-mono">GET /api/vibe/ping</code> · <code class="font-mono">GET /api/vibe/channels</code> · <code class="font-mono">GET /api/vibe/articles</code> · <code class="font-mono">GET /api/vibe/tags</code> · <code class="font-mono">GET /api/vibe/comments</code> · <code class="font-mono">GET /api/vibe/users</code></p>
        </div>
    </section>

    {{-- API 密钥管理 --}}
    <section class="mt-6 rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex items-center justify-between gap-4">
            <h3 class="font-semibold text-gray-900">API 密钥</h3>
        </div>

        <form action="{{ route('admin.devtools.keys.create') }}" method="POST" class="mt-4 flex flex-wrap items-end gap-3 rounded-lg border border-gray-100 bg-gray-50 p-4">
            @csrf
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-600 mb-1">密钥名称（可选）</label>
                <input type="text" name="name" class="input-field h-9 text-sm" placeholder="如：my-claude-code" maxlength="80">
            </div>
            <button type="submit" class="btn-primary h-9 text-sm">生成新密钥</button>
        </form>

        @if($keys->isEmpty())
            <p class="mt-4 text-sm text-gray-400">暂无 API 密钥，请先生成一个。</p>
        @else
            <div class="mt-4 space-y-2">
                @foreach($keys as $key)
                    <div class="flex flex-wrap items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 {{ $key->isRevoked() ? 'opacity-50 bg-gray-50' : 'bg-white' }}">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-sm text-gray-900">{{ $key->name }}</span>
                                @if($key->isRevoked())
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">已撤销</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">有效</span>
                                @endif
                            </div>
                            <p class="mt-0.5 text-xs text-gray-500">
                                前缀：<code class="font-mono">{{ $key->key_prefix }}…</code>
                                · 创建于 {{ $key->created_at->diffForHumans() }}
                                @if($key->isRevoked())· 撤销于 {{ $key->revoked_at->diffForHumans() }}@endif
                            </p>
                        </div>
                        @unless($key->isRevoked())
                            <form action="{{ route('admin.devtools.keys.revoke', $key->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-sm text-red-600 hover:text-red-800 hover:underline"
                                    onclick="return confirm('确认撤销该密钥？撤销后将无法恢复。')">撤销</button>
                            </form>
                        @endunless
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- 连接记录 --}}
    <section class="mt-6 rounded-xl border border-gray-200 bg-white p-6">
        <h3 class="font-semibold text-gray-900">连接记录 <span class="text-sm font-normal text-gray-400">（最近 100 条）</span></h3>

        @if($connections->isEmpty())
            <p class="mt-4 text-sm text-gray-400">暂无连接记录。安装 bensz-channel-devtools Skill 后，通过 Vibe 工具连接即可看到记录。</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left text-xs text-gray-500">
                            <th class="pb-2 pr-4 font-medium">客户端</th>
                            <th class="pb-2 pr-4 font-medium">密钥</th>
                            <th class="pb-2 pr-4 font-medium">工作目录</th>
                            <th class="pb-2 pr-4 font-medium">最后心跳</th>
                            <th class="pb-2 pr-4 font-medium">状态</th>
                            <th class="pb-2 font-medium">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($connections as $conn)
                            <tr class="{{ $conn->terminated_at ? 'opacity-50' : '' }}">
                                <td class="py-2 pr-4">
                                    <div class="font-medium text-gray-900">{{ $conn->client_name ?: '-' }}</div>
                                    <div class="text-xs text-gray-400">{{ $conn->client_version ?: '' }} {{ $conn->machine ?: '' }}</div>
                                </td>
                                <td class="py-2 pr-4 text-gray-600">{{ $conn->key_name }}</td>
                                <td class="py-2 pr-4 max-w-[200px] truncate text-gray-500 text-xs font-mono">{{ $conn->workdir ?: '-' }}</td>
                                <td class="py-2 pr-4 text-gray-500">{{ $conn->last_seen_at?->diffForHumans() ?? '-' }}</td>
                                <td class="py-2 pr-4">
                                    @if($conn->terminated_at)
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">已断开</span>
                                    @elseif($conn->terminate_requested_at)
                                        <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">终止中</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">活跃</span>
                                    @endif
                                    @if($conn->last_error)
                                        <span class="ml-1 text-xs text-red-500" title="{{ $conn->last_error }}">⚠</span>
                                    @endif
                                </td>
                                <td class="py-2">
                                    @if(! $conn->terminated_at && ! $conn->terminate_requested_at)
                                        <form action="{{ route('admin.devtools.connections.terminate', $conn->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-600 hover:underline">终止</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection

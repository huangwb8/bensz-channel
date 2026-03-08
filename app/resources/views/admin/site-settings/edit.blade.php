@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">站点设置</h2>
                <p class="mt-1 text-sm text-gray-500">管理员可以在这里覆盖 `config/config.toml` 中的站点名称与标语默认值。</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.articles.index') }}" class="btn-secondary">文章管理</a>
                <a href="{{ route('admin.channels.index') }}" class="btn-secondary">频道管理</a>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">用户管理</a>
            </div>
        </div>
    </section>

    <section class="mt-6 rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">前台展示信息</h3>
                <p class="mt-1 text-sm text-gray-500">保存后会立即覆盖运行时配置，并自动重建游客静态页。</p>
            </div>
            <div class="rounded-full px-3 py-1 text-xs font-semibold {{ $siteSettingsUsingOverrides ? 'bg-green-50 text-green-700 ring-1 ring-green-200' : 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }}">
                {{ $siteSettingsUsingOverrides ? '当前使用后台覆盖配置' : '当前使用 config/config.toml 默认值' }}
            </div>
        </div>

        <form action="{{ route('admin.site-settings.update') }}" method="POST" class="mt-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="app_name" class="mb-2 block text-sm font-medium text-gray-700">APP_NAME</label>
                <input id="app_name" type="text" name="app_name" value="{{ old('app_name', $siteSettingsForm['app_name']) }}" class="input-field h-11" placeholder="Bensz Channel">
                <p class="mt-2 text-xs text-gray-500">用于框架级应用名、系统标题与部分通知默认文案。</p>
            </div>

            <div>
                <label for="site_name" class="mb-2 block text-sm font-medium text-gray-700">SITE_NAME</label>
                <input id="site_name" type="text" name="site_name" value="{{ old('site_name', $siteSettingsForm['site_name']) }}" class="input-field h-11" placeholder="Bensz Channel">
                <p class="mt-2 text-xs text-gray-500">用于前台导航、页面标题与登录页品牌名称展示。</p>
            </div>

            <div>
                <label for="site_tagline" class="mb-2 block text-sm font-medium text-gray-700">SITE_TAGLINE</label>
                <textarea id="site_tagline" name="site_tagline" rows="3" class="input-field min-h-[96px] py-3" placeholder="类 QQ 频道的 Web 社区原型，支持静态游客访问与成员互动">{{ old('site_tagline', $siteSettingsForm['site_tagline']) }}</textarea>
                <p class="mt-2 text-xs text-gray-500">用于首页、副标题与页脚说明展示。</p>
            </div>

            <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                建议：如果你只是临时调整审查文案，优先在这里修改；`config/config.toml` 仍保留作为启动默认值来源。
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">保存站点设置</button>
            </div>
        </form>
    </section>
@endsection

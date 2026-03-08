@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">站点设置</h2>
                <p class="mt-1 text-sm text-gray-500">管理员可以在这里覆盖 `config/config.toml` 中的站点名称、标语，以及允许用户使用的登录/注册方式默认值。</p>
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
                <h3 class="text-lg font-semibold text-gray-900">前台展示与认证入口</h3>
                <p class="mt-1 text-sm text-gray-500">保存后会立即覆盖运行时配置，并自动重建游客静态页与登录页展示。</p>
            </div>
            <div class="rounded-full px-3 py-1 text-xs font-semibold {{ $siteSettingsUsingOverrides ? 'bg-green-50 text-green-700 ring-1 ring-green-200' : 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }}">
                {{ $siteSettingsUsingOverrides ? '当前使用后台覆盖配置' : '当前使用 config/config.toml 默认值' }}
            </div>
        </div>

        <form action="{{ route('admin.site-settings.update') }}" method="POST" class="mt-6 space-y-6">
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

            <div>
                <label for="cdn_asset_url" class="mb-2 block text-sm font-medium text-gray-700">静态资源 CDN</label>
                <input id="cdn_asset_url" type="url" name="cdn_asset_url" value="{{ old('cdn_asset_url', $siteSettingsForm['cdn_asset_url']) }}" class="input-field h-11" placeholder="https://cdn.example.com">
                <p class="mt-2 text-xs text-gray-500">用于加速 CSS、JS、图片和其它公开静态资源。留空则继续使用当前站点域名。</p>
            </div>

            <section class="space-y-4 rounded-xl border border-blue-100 bg-blue-50/40 p-5">
                <div>
                    <h4 class="text-base font-semibold text-gray-900">允许用户使用的登录 / 注册方式</h4>
                    <p class="mt-1 text-sm text-gray-500">至少保留一种方式。邮箱验证码与扫码方式可承担注册入口；邮箱密码主要用于已有账号登录。</p>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    @foreach($authMethodOptions as $methodKey => $methodOption)
                        <label class="flex items-start gap-3 rounded-lg border border-white bg-white p-4 shadow-sm">
                            <input type="checkbox" name="auth_enabled_methods[]" value="{{ $methodKey }}" class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(in_array($methodKey, old('auth_enabled_methods', $siteSettingsForm['auth_enabled_methods']), true))>
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $methodOption['label'] }}</div>
                                <div class="mt-1 text-sm text-gray-500">{{ $methodOption['description'] }}</div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </section>

            <div class="rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                建议：如果你只是临时调整审查文案或开放方式，优先在这里修改；`config/config.toml` 仍保留作为启动默认值来源。
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn-primary">保存站点设置</button>
            </div>
        </form>
    </section>
@endsection

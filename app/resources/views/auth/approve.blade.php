@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-2xl rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="text-xl font-semibold text-gray-900">{{ $providerLabel }}授权确认</h2>
        <p class="mt-2 text-sm text-gray-500">确认将此设备登录到 {{ config('community.site.name') }}</p>

        @if($qrLoginRequest->isExpired() || $qrLoginRequest->status !== \App\Models\QrLoginRequest::STATUS_PENDING)
            <div class="mt-6 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                当前二维码已失效，请回到桌面端重新生成。
            </div>
        @else
            <p class="mt-4 text-sm text-gray-600">填写一个昵称即可完成演示扫码授权；如填入邮箱或手机号，后续还能和验证码登录打通。</p>

            <form action="{{ route('auth.qr.approve.store', [$qrLoginRequest->provider, $qrLoginRequest]) }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">昵称</label>
                    <input type="text" name="name" class="input-field h-11" placeholder="例如：微信用户小张" required>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">{{ $providerLabel }} 账号标识 <span class="text-gray-400">（可选）</span></label>
                    <input type="text" name="provider_user_id" class="input-field h-11" placeholder="留空则自动生成，用于下次复用同一身份">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">邮箱 <span class="text-gray-400">（可选）</span></label>
                    <input type="email" name="email" class="input-field h-11" placeholder="name@example.com">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">手机号 <span class="text-gray-400">（可选）</span></label>
                    <input type="text" name="phone" class="input-field h-11" placeholder="13800138000">
                </div>
                <button type="submit" class="btn-primary w-full">确认授权</button>
            </form>
        @endif
    </section>
@endsection

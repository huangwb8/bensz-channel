@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-3xl panel p-6 lg:p-8">
        <p class="text-xs uppercase tracking-[0.35em] text-cyan-300/80">{{ $providerLabel }}授权确认</p>
        <h2 class="mt-3 text-3xl font-semibold">确认将此设备登录到 Bensz Channel</h2>
        <p class="mt-4 text-sm leading-7 text-slate-300">填写一个昵称即可完成演示扫码授权；如填入邮箱或手机号，后续还能和验证码登录打通。</p>

        @if($qrLoginRequest->isExpired() || $qrLoginRequest->status !== \App\Models\QrLoginRequest::STATUS_PENDING)
            <div class="mt-6 rounded-3xl border border-rose-500/30 bg-rose-500/10 p-5 text-sm text-rose-100">
                当前二维码已失效，请回到桌面端重新生成。
            </div>
        @else
            <form action="{{ route('auth.qr.approve.store', [$qrLoginRequest->provider, $qrLoginRequest]) }}" method="POST" class="mt-6 grid gap-4">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-200">昵称</label>
                    <input type="text" name="name" class="input-area h-12" placeholder="例如：微信用户小张" required>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-200">{{ $providerLabel }} 账号标识（可选）</label>
                    <input type="text" name="provider_user_id" class="input-area h-12" placeholder="留空则自动生成，用于下次复用同一身份">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-200">邮箱（可选）</label>
                    <input type="email" name="email" class="input-area h-12" placeholder="name@example.com">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-200">手机号（可选）</label>
                    <input type="text" name="phone" class="input-area h-12" placeholder="13800138000">
                </div>
                <button type="submit" class="btn-primary">确认授权</button>
            </form>
        @endif
    </section>
@endsection

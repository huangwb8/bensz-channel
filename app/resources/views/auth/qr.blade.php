@extends('layouts.app')

@section('content')
    <section class="panel p-6 lg:p-8">
        <p class="text-xs uppercase tracking-[0.35em] text-cyan-300/80">{{ $providerLabel }}扫码登录</p>
        <h2 class="mt-3 text-3xl font-semibold">请使用 {{ $providerLabel }} 打开二维码</h2>
        <p class="mt-4 text-sm leading-7 text-slate-300">移动端或新标签页打开授权链接后，本页会自动轮询登录结果。</p>

        <div class="mt-8 grid gap-6 lg:grid-cols-[340px_minmax(0,1fr)]">
            <div class="rounded-3xl border border-white/8 bg-white/[0.03] p-5 text-center">
                <div class="mx-auto flex h-[280px] w-[280px] items-center justify-center rounded-3xl bg-white p-4 text-slate-950" data-qr-value="{{ $approvalUrl }}">
                    正在生成二维码…
                </div>
                <p class="mt-4 text-xs text-slate-500">有效期至 {{ $qrLoginRequest->expires_at->format('H:i:s') }}</p>
            </div>

            <div class="space-y-4">
                <div class="rounded-3xl border border-white/8 bg-white/[0.03] p-5">
                    <p class="text-sm font-semibold text-slate-100">授权链接</p>
                    <a href="{{ $approvalUrl }}" target="_blank" rel="noreferrer" class="mt-3 block break-all text-sm text-cyan-300 hover:text-cyan-200">{{ $approvalUrl }}</a>
                </div>

                <div class="rounded-3xl border border-white/8 bg-white/[0.03] p-5">
                    <p class="text-sm font-semibold text-slate-100">状态</p>
                    <p class="mt-3 text-sm text-slate-300" data-qr-status-text>等待扫码确认…</p>
                </div>
            </div>
        </div>

        <div data-qr-status-url="{{ route('auth.qr.status', $qrLoginRequest) }}"></div>
    </section>
@endsection

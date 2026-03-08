@extends('layouts.app')

@section('content')
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="text-xl font-semibold text-gray-900">{{ $providerLabel }}扫码登录</h2>
        <p class="mt-2 text-sm text-gray-500">请使用 {{ $providerLabel }} 打开二维码，本页会自动轮询登录结果。</p>

        <div class="mt-6 grid gap-6 lg:grid-cols-[300px_minmax(0,1fr)]">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 text-center">
                <div class="mx-auto flex h-[260px] w-[260px] items-center justify-center rounded-xl bg-white p-4 text-gray-900" data-qr-value="{{ $approvalUrl }}">
                    正在生成二维码…
                </div>
                <p class="mt-4 text-xs text-gray-400">有效期至 {{ $qrLoginRequest->expires_at->format('H:i:s') }}</p>
            </div>

            <div class="space-y-4">
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p class="text-sm font-medium text-gray-700">授权链接</p>
                    <a href="{{ $approvalUrl }}" target="_blank" rel="noreferrer" class="mt-2 block break-all text-sm text-blue-600 hover:text-blue-700">{{ $approvalUrl }}</a>
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p class="text-sm font-medium text-gray-700">状态</p>
                    <p class="mt-2 text-sm text-gray-600" data-qr-status-text>⏳ 等待扫码确认…</p>
                </div>

                <div class="rounded-lg bg-blue-50 border border-blue-200 p-4 text-sm text-blue-700">
                    <p class="font-medium">演示模式说明</p>
                    <p class="mt-1 text-blue-600">点击授权链接，在新页面完成授权后，本页将自动登录。</p>
                </div>
            </div>
        </div>

        <div data-qr-status-url="{{ route('auth.qr.status', $qrLoginRequest) }}"></div>
    </section>
@endsection

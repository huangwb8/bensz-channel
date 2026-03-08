@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-2xl rounded-xl border border-gray-200 bg-white p-6 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-3xl">✅</div>
        <h2 class="mt-6 text-xl font-semibold text-gray-900">{{ $providerLabel }}扫码成功</h2>
        <p class="mt-3 text-sm text-gray-600">桌面端会在数秒内自动登录</p>
        <p class="mt-4 text-sm text-gray-500">当前授权账号：<span class="font-medium text-gray-700">{{ $user->name }}</span></p>
        <p class="mt-2 text-xs text-gray-400">你现在可以关闭这个页面，回到二维码发起页继续操作。</p>
    </section>
@endsection

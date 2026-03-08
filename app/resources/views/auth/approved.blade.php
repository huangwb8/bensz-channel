@extends('layouts.app')

@section('content')
    <section class="mx-auto max-w-2xl panel p-6 text-center lg:p-8">
        <div class="mx-auto flex h-18 w-18 items-center justify-center rounded-full bg-emerald-500/15 text-4xl">✅</div>
        <p class="mt-6 text-xs uppercase tracking-[0.35em] text-emerald-300/80">{{ $providerLabel }}扫码成功</p>
        <h2 class="mt-3 text-3xl font-semibold">桌面端会在数秒内自动登录</h2>
        <p class="mt-4 text-sm leading-7 text-slate-300">当前授权账号：{{ $user->name }}。你现在可以关闭这个页面，回到二维码发起页继续操作。</p>
    </section>
@endsection

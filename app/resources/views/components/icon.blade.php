@props([
    'name',
])

@switch($name)
    @case('arrow-left')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
        @break

    @case('plus')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5.25v13.5M5.25 12h13.5" />
        </svg>
        @break

    @case('users')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 18.75a3.75 3.75 0 0 0-7.5 0m7.5 0v.75H10.5v-.75m7.5 0a3.75 3.75 0 1 1 3.75-3.75M15 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm-9 11.25a3.75 3.75 0 0 1 7.5 0v.75H6v-.75Zm0 0A3.75 3.75 0 1 1 9.75 15" />
        </svg>
        @break

    @case('folder')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12.75V7.5A2.25 2.25 0 0 1 4.5 5.25h4.19a2.25 2.25 0 0 1 1.59.66l1.31 1.3a2.25 2.25 0 0 0 1.59.66h6.32a2.25 2.25 0 0 1 2.25 2.25v6.63A2.25 2.25 0 0 1 19.5 19.5h-15A2.25 2.25 0 0 1 2.25 17.25v-4.5Z" />
        </svg>
        @break

    @case('document')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-8.625a2.625 2.625 0 0 0-2.625-2.625H8.25A2.25 2.25 0 0 0 6 5.25v13.5A2.25 2.25 0 0 0 8.25 21h8.625a2.625 2.625 0 0 0 2.625-2.625V14.25Zm0 0H16.875a1.125 1.125 0 0 1-1.125-1.125V10.5m-6 3.75h3.75m-3.75 3h6.75" />
        </svg>
        @break

    @case('eye')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12S5.25 5.25 12 5.25 21.75 12 21.75 12 18.75 18.75 12 18.75 2.25 12 2.25 12Z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
        </svg>
        @break

    @case('pencil')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m16.862 4.487 1.687-1.688a2.25 2.25 0 1 1 3.182 3.182L10.582 17.13a4.5 4.5 0 0 1-1.897 1.13L6 19.125l.864-2.685a4.5 4.5 0 0 1 1.13-1.897L16.862 4.487ZM15 6.75l2.25 2.25" />
        </svg>
        @break

    @case('trash')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0V4.875A2.25 2.25 0 0 0 13.5 2.625h-3A2.25 2.25 0 0 0 8.25 4.875V5.25m7.5 0h-7.5" />
        </svg>
        @break

    @case('save')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 4.5h9.879c.597 0 1.169.237 1.591.659l.621.621c.422.422.659.994.659 1.591V18A2.25 2.25 0 0 1 17.25 20.25H6.75A2.25 2.25 0 0 1 4.5 18V6.75A2.25 2.25 0 0 1 6.75 4.5Z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.25 4.5v4.875A1.125 1.125 0 0 0 9.375 10.5h5.25a1.125 1.125 0 0 0 1.125-1.125V4.5M9 15.75h6" />
        </svg>
        @break

    @case('chevron-down')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6" />
        </svg>
        @break

    @case('chevron-up')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m18 15-6-6-6 6" />
        </svg>
        @break

    @case('rss')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5.25 19.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm-1.5-8.25a9.75 9.75 0 0 1 9.75 9.75m-9.75-15a15 15 0 0 1 15 15" />
        </svg>
        @break

    @case('mail')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21.75 6.75v10.5A2.25 2.25 0 0 1 19.5 19.5h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15A2.25 2.25 0 0 0 2.25 6.75m19.5 0-8.69 5.214a2.25 2.25 0 0 1-2.32 0L2.25 6.75" />
        </svg>
        @break

    @case('chat-bubble-left-right')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7.5 8.25h9m-9 3h5.25M6.375 18.75 3.75 21V5.625A2.625 2.625 0 0 1 6.375 3h11.25a2.625 2.625 0 0 1 2.625 2.625v8.25a2.625 2.625 0 0 1-2.625 2.625H6.375Z" />
        </svg>
        @break

    @case('pin')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.25 4.5v3.188l3.058 3.057a1.5 1.5 0 0 1-1.06 2.56h-2.123v4.195l-2.625 1.875v-6.07H9.377a1.5 1.5 0 0 1-1.06-2.56l3.058-3.057V4.5h2.875Z" />
        </svg>
        @break

    @case('star')
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor']) }}>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m11.48 3.499 2.151 4.36 4.812.7-3.481 3.393.822 4.793L11.48 14.48 7.176 16.745l.823-4.793-3.482-3.393 4.813-.7 2.15-4.36Z" />
        </svg>
        @break

    @default
        <svg {{ $attributes->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor']) }}>
            <circle cx="12" cy="12" r="8" stroke-width="1.8" />
        </svg>
@endswitch

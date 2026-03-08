<?php

namespace App\Console\Commands;

use App\Support\StaticPageBuilder;
use Illuminate\Console\Command;

class BuildStaticSite extends Command
{
    protected $signature = 'site:build-static';

    protected $description = '重新构建游客可访问的静态 HTML 页面';

    public function handle(StaticPageBuilder $staticPageBuilder): int
    {
        $staticPageBuilder->buildAll();

        $this->info('静态页面已重建完成。');

        return self::SUCCESS;
    }
}

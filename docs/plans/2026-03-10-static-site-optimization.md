# 静态站点生成优化方案

## 背景

当前静态页面生成机制（`StaticPageBuilder`）在小规模场景下运行良好，但在大规模场景（文章数 > 2000）下存在明显的性能瓶颈和服务器压力问题。

### 当前实现的问题

| 问题类型 | 具体表现 | 风险等级 |
|---------|---------|---------|
| **内存压力** | 使用 `get()` 一次性加载所有数据到内存 | 🔴 高 |
| **CPU 压力** | Gzip 压缩级别 9（最高），CPU 密集型 | ⚠️ 中 |
| **执行时间** | 同步执行，大规模场景可能超过 PHP 执行时间限制 | 🔴 高 |
| **无增量构建** | 每次都重建所有页面，即使只修改了一篇文章 | ⚠️ 中 |
| **无并发控制** | 同步写入文件，无法利用多核 CPU | ⚠️ 中 |

### 压力估算

| 数据规模 | 文章数 | 预估内存 | 预估时间 | 当前风险 |
|---------|-------|---------|---------|---------|
| 小型 | < 500 | ~50MB | ~5秒 | ✅ 可接受 |
| 中型 | 500-2000 | ~200MB | ~30秒 | ⚠️ 需监控 |
| 大型 | 2000-5000 | ~800MB | ~2分钟 | 🔴 需优化 |
| 超大型 | > 5000 | ~4GB | ~10分钟 | 🔴 不可用 |

## 优化目标

### 性能目标

- **内存占用**：峰值 < 256MB（无论文章数量）
- **执行时间**：10000 篇文章 < 5 分钟
- **CPU 占用**：平均 < 50%（允许短时峰值）
- **增量构建**：单篇文章更新 < 1 秒

### 功能目标

- 保持现有功能完全兼容
- 支持增量构建（只重建变更的页面）
- 支持异步执行（队列系统）
- 支持并行处理（可选）

## 技术方案

### 方案一：分批处理 + 降低压缩级别（短期优化）

**优先级**：🔴 高（立即实施）

#### 核心改动

1. **使用 `chunk()` 分批加载数据**
   ```php
   // 替换：Channel::query()->ordered()->get()->each(...)
   Channel::query()->ordered()->chunk(50, function ($channels) {
       foreach ($channels as $channel) {
           // 处理单个频道
       }
   });

   // 替换：Article::query()->published()->get()->each(...)
   Article::query()->published()->chunk(100, function ($articles) {
       foreach ($articles as $article) {
           // 处理单篇文章
       }
   });
   ```

2. **降低 Gzip 压缩级别**
   ```php
   // 从级别 9 降到 6
   gzencode($minified, 6)  // 压缩率损失 < 5%，速度提升 ~50%
   ```

3. **添加进度输出**
   ```php
   // 在命令中显示进度
   $this->info("正在构建频道页面...");
   $this->info("正在构建文章页面 (共 {$totalArticles} 篇)...");
   ```

#### 预期效果

- 内存占用：从 ~4GB 降到 ~256MB（10000 篇文章）
- 执行时间：提升 ~30%
- CPU 占用：降低 ~40%

#### 实施成本

- 开发时间：2 小时
- 测试时间：1 小时
- 风险：低（向后兼容）

---

### 方案二：异步队列执行（中期优化）

**优先级**：⚠️ 中（1-2 周内实施）

#### 核心改动

1. **创建队列任务**
   ```php
   // app/Jobs/BuildStaticSiteJob.php
   class BuildStaticSiteJob implements ShouldQueue
   {
       public function handle(StaticPageBuilder $builder): void
       {
           $builder->buildAll();
       }
   }
   ```

2. **修改命令触发方式**
   ```php
   // BuildStaticSite.php
   public function handle(): int
   {
       if ($this->option('sync')) {
           app(StaticPageBuilder::class)->buildAll();
       } else {
           dispatch(new BuildStaticSiteJob());
           $this->info('静态页面构建任务已加入队列。');
       }
       return self::SUCCESS;
   }
   ```

3. **添加自动触发机制**
   ```php
   // 在文章发布/更新时自动触发
   Article::saved(function ($article) {
       if ($article->is_published) {
           dispatch(new BuildStaticSiteJob());
       }
   });
   ```

#### 预期效果

- 不阻塞 Web 请求
- 支持后台执行
- 可配置队列优先级

#### 实施成本

- 开发时间：4 小时
- 测试时间：2 小时
- 依赖：需要配置队列系统（Redis/Database）

---

### 方案三：增量构建（长期优化）

**优先级**：✅ 低（按需实施）

#### 核心改动

1. **记录页面构建时间**
   ```php
   // 在数据库中记录每个页面的最后构建时间
   Schema::create('static_page_builds', function (Blueprint $table) {
       $table->id();
       $table->string('page_type'); // 'home', 'channel', 'article'
       $table->unsignedBigInteger('resource_id')->nullable();
       $table->timestamp('built_at');
       $table->string('file_hash'); // 用于检测内容变化
   });
   ```

2. **智能判断是否需要重建**
   ```php
   public function buildArticle(Article $article): void
   {
       $lastBuild = StaticPageBuild::where([
           'page_type' => 'article',
           'resource_id' => $article->id,
       ])->first();

       if ($lastBuild && $article->updated_at <= $lastBuild->built_at) {
           return; // 跳过未变更的文章
       }

       // 构建页面...
   }
   ```

3. **提供增量/全量构建选项**
   ```bash
   php artisan site:build-static --incremental  # 增量构建
   php artisan site:build-static --full         # 全量构建
   ```

#### 预期效果

- 单篇文章更新：< 1 秒
- 减少 95% 的重复构建
- 显著降低服务器压力

#### 实施成本

- 开发时间：8 小时
- 测试时间：4 小时
- 复杂度：中等

---

### 方案四：并行处理（可选优化）

**优先级**：✅ 低（性能极限场景）

#### 核心改动

使用 Laravel 的并行处理功能：

```php
use Illuminate\Support\Facades\Parallel;

public function buildAll(): void
{
    $articles = Article::query()->published()->get();

    // 将文章分成 4 批并行处理
    $results = Parallel::map($articles->chunk(250), function ($chunk) {
        foreach ($chunk as $article) {
            $this->buildArticle($article);
        }
    });
}
```

#### 预期效果

- 执行时间：提升 2-4 倍（取决于 CPU 核心数）
- CPU 占用：短时峰值可能达到 100%

#### 实施成本

- 开发时间：6 小时
- 测试时间：3 小时
- 风险：需要充分测试并发安全性

## 实施计划

### 阶段一：立即实施（本周）

**目标**：解决当前最紧迫的内存和 CPU 压力问题

1. ✅ 实施方案一：分批处理 + 降低压缩级别
2. ✅ 添加单元测试验证功能正确性
3. ✅ 在测试环境验证性能提升
4. ✅ 部署到生产环境

**交付物**：
- 优化后的 `StaticPageBuilder.php`
- 性能测试报告
- 更新文档

### 阶段二：短期实施（1-2 周）

**目标**：支持异步执行，避免阻塞主进程

1. ✅ 实施方案二：异步队列执行
2. ✅ 配置队列系统（推荐 Redis）
3. ✅ 添加自动触发机制
4. ✅ 监控队列执行状态

**交付物**：
- `BuildStaticSiteJob.php`
- 队列配置文档
- 监控面板

### 阶段三：中期实施（1 个月）

**目标**：支持增量构建，减少重复工作

1. ✅ 实施方案三：增量构建
2. ✅ 数据库迁移
3. ✅ 添加构建缓存机制
4. ✅ 性能对比测试

**交付物**：
- 增量构建功能
- 性能对比报告

### 阶段四：长期优化（按需）

**目标**：极限性能优化

1. ✅ 评估是否需要并行处理
2. ✅ 实施方案四（如果需要）
3. ✅ CDN 集成（可选）

## 风险评估

### 技术风险

| 风险项 | 影响 | 概率 | 缓解措施 |
|-------|------|------|---------|
| 分批处理导致内存泄漏 | 中 | 低 | 在每批处理后显式释放资源 |
| 队列系统故障 | 高 | 低 | 保留同步执行选项作为备用 |
| 增量构建逻辑错误 | 高 | 中 | 充分的单元测试 + 定期全量构建 |
| 并行处理竞态条件 | 中 | 中 | 文件锁 + 充分测试 |

### 业务风险

| 风险项 | 影响 | 概率 | 缓解措施 |
|-------|------|------|---------|
| 优化后功能不兼容 | 高 | 低 | 完整的回归测试 |
| 性能提升不明显 | 中 | 低 | 分阶段实施，每阶段验证效果 |
| 增加系统复杂度 | 中 | 中 | 充分的文档 + 代码注释 |

## 验证方法

### 性能测试

创建测试脚本 `scripts/test/test_static_build_performance.sh`：

```bash
#!/bin/bash

# 创建测试数据
php artisan db:seed --class=LargeScaleTestSeeder

# 测试优化前性能（如果有备份）
echo "=== 优化前性能 ==="
time php artisan site:build-static

# 测试优化后性能
echo "=== 优化后性能 ==="
time php artisan site:build-static

# 内存使用监控
echo "=== 内存使用 ==="
php artisan site:build-static --memory-profile
```

### 功能测试

1. **完整性测试**：验证所有页面都已生成
2. **内容测试**：验证生成的 HTML 内容正确
3. **压缩测试**：验证 Gzip 文件可正常解压
4. **增量测试**：验证增量构建只重建变更的页面

### 监控指标

- 内存峰值使用量
- 执行总时间
- CPU 平均占用率
- 生成的文件数量
- 文件总大小

## 回滚方案

### 快速回滚

如果优化后出现问题，可以快速回滚到原始实现：

1. **代码回滚**
   ```bash
   git revert <commit-hash>
   ```

2. **配置回滚**
   ```bash
   # 禁用队列执行
   php artisan site:build-static --sync
   ```

3. **数据回滚**
   ```bash
   # 如果添加了新表，可以回滚迁移
   php artisan migrate:rollback
   ```

### 降级策略

- 保留原始的同步执行选项
- 增量构建失败时自动降级到全量构建
- 队列系统故障时自动切换到同步执行

## 成功标准

### 必须达成（阶段一）

- ✅ 10000 篇文章场景下，内存占用 < 512MB
- ✅ 执行时间相比优化前提升 > 30%
- ✅ 所有现有功能测试通过
- ✅ 无生产环境故障

### 期望达成（阶段二）

- ✅ 支持异步执行，不阻塞 Web 请求
- ✅ 文章发布后自动触发静态页面构建
- ✅ 队列执行成功率 > 99%

### 理想达成（阶段三）

- ✅ 支持增量构建，单篇文章更新 < 1 秒
- ✅ 减少 90% 以上的重复构建
- ✅ 服务器 CPU 平均占用 < 30%

## 参考资料

- [Laravel 队列文档](https://laravel.com/docs/queues)
- [Laravel 并行处理](https://laravel.com/docs/helpers#method-parallel)
- [PHP 内存优化最佳实践](https://www.php.net/manual/en/features.gc.php)
- [Gzip 压缩级别性能对比](https://tukaani.org/lzma/benchmarks.html)

## 附录

### 相关文件

- [app/app/Support/StaticPageBuilder.php](../app/app/Support/StaticPageBuilder.php)
- [app/app/Console/Commands/BuildStaticSite.php](../app/app/Console/Commands/BuildStaticSite.php)
- [app/tests/Feature/Static/StaticBuildTest.php](../app/tests/Feature/Static/StaticBuildTest.php)

### 配置项

```toml
# app/config.toml
[env]
STATIC_SITE_ENABLED = true
STATIC_SITE_OUTPUT_DIR = "static"
STATIC_SITE_GZIP_LEVEL = 6  # 新增：可配置压缩级别
STATIC_SITE_CHUNK_SIZE = 100  # 新增：分批处理大小
```

---

**文档版本**：v1.0
**创建日期**：2026-03-10
**最后更新**：2026-03-10
**负责人**：开发团队
**状态**：待审核

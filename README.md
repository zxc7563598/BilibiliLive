# 项目部署说明

### 当前环境

系统版本：CentOS 7.9 X64

各项环境版本：

|  名称 | 版本 |
|:-----|:-----|
|PHP |  8.1.9  |
|Nginx  |  1.22.0  |
|Mysql |  8.0.24  |
|Redis |  7.0.4  |

---

框架以及组件版本

| 名称 | 版本 | 详情 |
|:-----|:-----|:-----|
| webman | ∞ | composer create-project workerman/webman "项目名称" |
|  数据库驱动 | ∞ | composer require -W psr/container ^1.1.1 illuminate/database illuminate/pagination illuminate/events symfony/var-dumper |
|  数据库迁移 | ∞ | composer require robmorgan/phinx -W |
|  redis扩展 | ∞ | composer require psr/container ^1.1.1 illuminate/redis illuminate/events |
|  redis队列 | ∞ | composer require webman/redis-queue |
|  env环境变量组件 | ∞ | composer require vlucas/phpdotenv |
|  定时任务扩展 | ∞ | composer require workerman/crontab |
|  日期扩展 | ∞ | composer require nesbot/carbon |
|  UUID  | ∞ | composer require ramsey/uuid |
|  网络请求  | ∞ | composer require suqingan/network |
|  Email | ∞ |  composer require yzh52521/webman-mailer |

---

数据库迁移指令
` php vendor/bin/phinx migrate `

---

启动指令
` php start.php start -d `

停止运行
` php start.php stop `
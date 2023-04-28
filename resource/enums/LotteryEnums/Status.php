<?php

namespace resource\enums\LotteryEnums;

/**
 * 状态
 */
enum Status: int
{
    case InProgress = 0;
    case Completed = 1;
    

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::InProgress => '进行中',
            static::Completed => '已完成'
        };
    }

    // 获取全部的枚举
    public static function all(): array
    {
        $cases = self::cases();
        $enums = [];
        foreach ($cases as $_cases) {
            $enums[] = [
                'key' => $_cases->value,
                'value' => $_cases->label()
            ];
        }
        return $enums;
    }
}

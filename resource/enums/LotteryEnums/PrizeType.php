<?php

namespace resource\enums\LotteryEnums;

/**
 * 开奖类型
 */
enum PrizeType: int
{
    case Manual = 0;
    case Automatic = 1;



    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::Manual => '手动',
            static::Automatic => '自动'
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

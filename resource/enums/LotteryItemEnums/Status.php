<?php

namespace resource\enums\LotteryItemEnums;

/**
 * 状态
 */
enum Status: int
{
    case Unbound = 0;
    case Bound = 1;
    case Lottery = 2;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::Unbound => '未绑定',
            static::Bound => '已绑定',
            static::Lottery => '已开奖'
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

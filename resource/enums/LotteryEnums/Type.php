<?php

namespace resource\enums\LotteryEnums;

/**
 * 类型
 */
enum Type: int
{
    case Number = 0;
    case Barrage = 1;
    case Gift = 2;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::Number => '编号',
            static::Barrage => '弹幕',
            static::Gift => '礼物'
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

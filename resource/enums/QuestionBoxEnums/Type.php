<?php

namespace resource\enums\QuestionBoxEnums;

/**
 * 投稿类型
 */
enum Type: int
{
    case Truth = 1;
    case Ask = 2;
    case Bad = 3;
    case Trouble = 4;
    case Share = 5;
    case Trivial = 6;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::Truth => '真心话',
            static::Ask => '有问必答箱',
            static::Bad => '缺德箱',
            static::Trouble => '烦恼箱',
            static::Share => '小树洞',
            static::Trivial => '碎碎念'
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

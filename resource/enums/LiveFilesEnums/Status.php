<?php

namespace resource\enums\LiveFilesEnums;

/**
 * 文件上传状态
 */
enum Status: int
{
    case Recording = 0;
    case ToUpload = 1;
    case InUpload = 2;
    case UploadSuccessful = 3;
    case UploadFailed = 4;

    //定义一个转换函数，用来显示
    public function label(): string
    {
        return match ($this) {
            static::Recording => '录制中',
            static::ToUpload => '待上传',
            static::InUpload => '上传中',
            static::UploadSuccessful => '上传成功',
            static::UploadFailed => '上传失败'
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

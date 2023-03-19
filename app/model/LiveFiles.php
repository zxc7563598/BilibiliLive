<?php

namespace app\model;

use support\Model;
use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LiveFiles extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'bili_live_files';

    /**
     * 重定义主键，默认是id
     *
     * @var string
     */
    protected $primaryKey = 'files_id';

    /**
     * 指示是否自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = true;
    protected $dateFormat = 'U';

    /**
     * 指示模型主键是否递增
     *
     * @var bool
     */
    public $incrementing = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->{$model->getKeyName()} = Uuid::uuid4()->toString();
        });
    }
}

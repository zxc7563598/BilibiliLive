<?php

namespace app\model;

use support\Model;
use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lottery extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'bili_lottery';

    /**
     * 重定义主键，默认是id
     *
     * @var string
     */
    protected $primaryKey = 'lottery_id';

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

        static::saved(function ($model) {
            if ($model->num != $model->getOriginal('num')) {
                // 删除先前的盒子，创建新盒子
                LotteryItem::where('lottery_id', $model->lottery_id)->delete();
                // 创建新数量的盒子
                for ($i = 0; $i < $model->num; $i++) {
                    $box = $i + 1;
                    switch (strlen($box)) {
                        case 1:
                            $box = '00' . $box;
                            break;
                        case 2:
                            $box = '0' . $box;
                            break;
                    }
                    $items = new LotteryItem();
                    $items->lottery_id = $model->lottery_id;
                    $items->box_number = $box;
                    $items->save();
                }
            }
        });
    }
}

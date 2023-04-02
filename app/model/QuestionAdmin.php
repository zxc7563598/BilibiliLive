<?php

namespace app\model;

use support\Model;
use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuestionAdmin extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'bili_question_admin';

    /**
     * 重定义主键，默认是id
     *
     * @var string
     */
    protected $primaryKey = 'admin_id';

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

        static::saving(function ($model) {
            if ($model->getOriginal('password') != $model->password) {
                if ($model->getOriginal('salt') == $model->salt) {
                    $model->salt = mt_rand(1000, 9999);
                }
                $model->password = sha1(sha1($model->password) . $model->salt);
            }
        });
    }
}

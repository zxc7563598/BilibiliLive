<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;
use resource\enums\LiveBlackEnums;

final class CreateLiveBlack extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('live_black', ['id' => false, 'primary_key' => 'black_id', 'comment' => '黑名单记录表']);
        $table->addColumn('black_id', 'uuid', ['comment' => '记录id', 'null' => false])
            ->addColumn('uid', 'integer', ['comment' => '用户id', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('uname', 'string', ['comment' => '用户名', 'null' => true])
            ->addColumn('msg', 'string', ['comment' => '弹幕信息', 'null' => true])
            ->addColumn('price', 'integer', ['comment' => '多少电池可以解除禁言，单位电池*100', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('use_price', 'integer', ['comment' => '已付电池，单位电池*100', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('end', 'integer', ['comment' => '常规禁言结束时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('status', 'integer', ['comment' => '禁言状态', 'default' => LiveBlackEnums\Status::Normal->value, 'null' => false, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('created_at', 'integer', ['comment' => '创建时间', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('updated_at', 'integer', ['comment' => '更新时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('deleted_at', 'integer', ['comment' => '逻辑删除', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->create();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->table('live_black')->drop()->save();
    }
}

<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateBiliLiveGift extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bili_live_gift', ['id' => false, 'primary_key' => 'gift_id', 'comment' => '直播文件表']);
        $table->addColumn('gift_id', 'uuid', ['comment' => '礼物id', 'null' => false])
            ->addColumn('live_id', 'uuid', ['comment' => '直播记录id', 'null' => false])
            ->addColumn('action', 'string', ['comment' => '赠送类型', 'null' => true])
            ->addColumn('id', 'string', ['comment' => '礼物id（B站）', 'null' => true])
            ->addColumn('gift_name', 'string', ['comment' => '礼物名称', 'null' => true])
            ->addColumn('gift_type', 'integer', ['comment' => '礼物类型', 'null' => true, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('gift_level', 'integer', ['comment' => '礼物级别', 'null' => true, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('num', 'integer', ['comment' => '赠送数量', 'null' => true, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('price', 'integer', ['comment' => '价格（分）', 'null' => true, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('uid', 'string', ['comment' => '赠送人id', 'null' => true])
            ->addColumn('uname', 'string', ['comment' => '赠送人名称', 'null' => true])
            ->addColumn('status', 'integer', ['comment' => '状态', 'null' => true, 'limit' => MysqlAdapter::INT_TINY])
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
        $this->table('bili_live_gift')->drop()->save();
    }
}

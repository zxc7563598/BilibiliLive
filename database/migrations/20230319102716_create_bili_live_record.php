<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateBiliLiveRecord extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bili_live_record', ['id' => false, 'primary_key' => 'live_id', 'comment' => '直播记录表']);
        $table->addColumn('live_id', 'uuid', ['comment' => '记录id', 'null' => false])
            ->addColumn('room_id', 'string', ['comment' => '房间号', 'null' => true])
            ->addColumn('short_id', 'string', ['comment' => '短号，如果没有则为0', 'null' => true])
            ->addColumn('name', 'string', ['comment' => '主播名字', 'null' => true])
            ->addColumn('area_name_parent', 'string', ['comment' => '直播间父分区', 'null' => true])
            ->addColumn('area_name_child', 'string', ['comment' => '直播间子分区', 'null' => true])
            ->addColumn('start_time', 'integer', ['comment' => '开播时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('down_time', 'integer', ['comment' => '下播时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('duration', 'integer', ['comment' => '直播时长', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('created_at', 'integer', ['comment' => '创建时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('updated_at', 'integer', ['comment' => '更新时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('deleted_at', 'integer', ['comment' => '逻辑删除', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->create();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->table('bili_live_record')->drop()->save();
    }
}

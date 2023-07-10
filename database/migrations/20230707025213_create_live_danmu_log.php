<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateLiveDanmuLog extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('live_danmu_log', ['id' => false, 'primary_key' => 'log_id', 'comment' => '弹幕记录表']);
        $table->addColumn('log_id', 'uuid', ['comment' => '记录id', 'null' => false])
            ->addColumn('uid', 'integer', ['comment' => '用户id', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('uname', 'string', ['comment' => '用户名', 'null' => true])
            ->addColumn('uguard', 'integer', ['comment' => '勋章大航海等级	1: 总督 2: 提督 3:舰长', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('ulevel', 'integer', ['comment' => '用户等级', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('ulevel_ranking', 'string', ['comment' => '用户等级排名', 'null' => true])
            ->addColumn('medal_level', 'integer', ['comment' => '勋章等级', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('medal_name', 'string', ['comment' => '勋章名', 'null' => true])
            ->addColumn('medal_anchor_name', 'string', ['comment' => '勋章主播名', 'null' => true])
            ->addColumn('medal_room_id', 'integer', ['comment' => '勋章直播间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('msg', 'string', ['comment' => '弹幕信息', 'null' => true])
            ->addColumn('timestamp', 'integer', ['comment' => '弹幕发送时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
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
        $this->table('live_danmu_log')->drop()->save();
    }
}

<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateBiliLiveDanmu extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bili_live_danmu', ['id' => false, 'primary_key' => 'danmu_id', 'comment' => '直播文件表']);
        $table->addColumn('danmu_id', 'uuid', ['comment' => '礼物id', 'null' => false])
            ->addColumn('live_id', 'uuid', ['comment' => '直播记录id', 'null' => false])
            ->addColumn('manager', 'integer', ['comment' => '是否是房管', 'null' => true, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('level', 'integer', ['comment' => '牌子等级（不一定是当前房间的牌子）', 'null' => true, 'limit' => MysqlAdapter::INT_SMALL])
            ->addColumn('uguard', 'integer', ['comment' => '舰长类型（1总2提3舰）', 'null' => true, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('uid', 'string', ['comment' => '赠送人id', 'null' => true])
            ->addColumn('uname', 'string', ['comment' => '赠送人名称', 'null' => true])
            ->addColumn('msg', 'string', ['comment' => '发送消息', 'null' => true])
            ->addColumn('timestamp', 'integer', ['comment' => '发送时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
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
        $this->table('bili_live_danmu')->drop()->save();
    }
}

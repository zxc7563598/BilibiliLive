<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateLiveGiftLog extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('live_gift_log', ['id' => false, 'primary_key' => 'log_id', 'comment' => '礼物记录表']);
        $table->addColumn('log_id', 'uuid', ['comment' => '记录id', 'null' => false])
            ->addColumn('action', 'string', ['comment' => '动作类型，通常为投喂', 'null' => true])
            ->addColumn('face', 'string', ['comment' => '投喂用户头像', 'null' => true])
            ->addColumn('uid', 'integer', ['comment' => '送礼用户id', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('uname', 'string', ['comment' => '送礼用户名称', 'null' => true])
            ->addColumn('wealth_level', 'integer', ['comment' => '财富等级', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('gift_id', 'integer', ['comment' => '礼物id', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('gift_name', 'string', ['comment' => '礼物名称', 'null' => true])
            ->addColumn('gift_type', 'integer', ['comment' => '待观察，礼物类型', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('gold', 'integer', ['comment' => '待观察', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('num', 'integer', ['comment' => '赠送数量', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('price', 'integer', ['comment' => '金额，单位分', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('receive_uid', 'integer', ['comment' => '收礼人id', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('receive_uname', 'integer', ['comment' => '收礼人名称', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('medal_level', 'integer', ['comment' => '粉丝牌等级', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('medal_name', 'string', ['comment' => '粉丝牌名称', 'null' => true])
            ->addColumn('target_id', 'integer', ['comment' => '粉丝牌对应的主播uid', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('guard_level', 'integer', ['comment' => '勋章大航海等级	1: 总督 2: 提督 3:舰长', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('timestamp', 'integer', ['comment' => '送礼时间戳', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
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
        $this->table('live_gift_log')->drop()->save();
    }
}

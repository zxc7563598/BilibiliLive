<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateBiliLottery extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bili_lottery', ['id' => false, 'primary_key' => 'lottery_id', 'comment' => '抽奖表']);
        $table->addColumn('lottery_id', 'uuid', ['comment' => '抽奖id', 'null' => false])
            ->addColumn('lottery_name', 'string', ['comment' => '抽奖名称', 'null' => true])
            ->addColumn('num', 'integer', ['comment' => '奖品数量', 'null' => false, 'default' => 0, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('medal_name', 'string', ['comment' => '限制牌子名称', 'null' => true])
            ->addColumn('medal_level', 'integer', ['comment' => '限制牌子等级', 'null' => false, 'default' => 0, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('type', 'integer', ['comment' => '抽奖类型', 'null' => true, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('status', 'integer', ['comment' => '当前状态', 'null' => true, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('expand', 'text', ['comment' => '拓展信息', 'null' => true])
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
        $this->table('bili_lottery')->drop()->save();
    }
}

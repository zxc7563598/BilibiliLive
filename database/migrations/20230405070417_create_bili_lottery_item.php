<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateBiliLotteryItem extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bili_lottery_item', ['id' => false, 'primary_key' => 'item_id', 'comment' => '奖品信息配置表']);
        $table->addColumn('item_id', 'uuid', ['comment' => 'id', 'null' => false])
            ->addColumn('box_number', 'string', ['comment' => '盒子编号', 'null' => false])
            ->addColumn('lottery_id', 'uuid', ['comment' => '抽奖id', 'null' => false])
            ->addColumn('uid', 'string', ['comment' => '绑定用户uid', 'null' => true])
            ->addColumn('uname', 'string', ['comment' => '绑定用户uname', 'null' => true])
            ->addColumn('content', 'string', ['comment' => '奖品内容', 'null' => true])
            ->addColumn('status', 'integer', ['comment' => '当前状态', 'null' => true, 'default' => 0, 'limit' => MysqlAdapter::INT_TINY])
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
        $this->table('bili_lottery_item')->drop()->save();
    }
}

<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ModifyBiliLotteryAddItems extends AbstractMigration
{

    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bili_lottery');
        $table
            ->addColumn('row', 'integer', ['comment' => '每行展示个数', 'null' => false, 'default' => 6, 'limit' => MysqlAdapter::INT_MEDIUM, 'after' => 'type'])
            ->addColumn('prize_type', 'integer', ['comment' => '开奖类型', 'null' => false, 'default' => 0, 'limit' => MysqlAdapter::INT_TINY, 'after' => 'type'])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('bili_lottery');
        $table->removeColumn('prize_type')
            ->removeColumn('row')
            ->save();
    }
}

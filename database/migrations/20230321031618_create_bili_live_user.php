<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateBiliLiveUser extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bili_live_user', ['id' => false, 'primary_key' => 'user_id', 'comment' => '直播用户表']);
        $table->addColumn('user_id', 'uuid', ['comment' => '用户id', 'null' => false])
            ->addColumn('room_id', 'string', ['comment' => '房间号', 'null' => true])
            ->addColumn('target_id', 'string', ['comment' => '哔哩哔哩UID', 'null' => true])
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
        $this->table('bili_live_user')->drop()->save();
    }
}

<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateBiliLiveFiles extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bili_live_files', ['id' => false, 'primary_key' => 'files_id', 'comment' => '直播文件表']);
        $table->addColumn('files_id', 'uuid', ['comment' => '直播文件id', 'null' => false])
            ->addColumn('live_id', 'uuid', ['comment' => '直播记录id', 'null' => false])
            ->addColumn('room_id', 'string', ['comment' => '房间号', 'null' => true])
            ->addColumn('short_id', 'string', ['comment' => '短号，如果没有则为0', 'null' => true])
            ->addColumn('name', 'string', ['comment' => '主播名字', 'null' => true])
            ->addColumn('title', 'string', ['comment' => '当前直播间标题', 'null' => true])
            ->addColumn('area_name_parent', 'string', ['comment' => '直播间父分区', 'null' => true])
            ->addColumn('area_name_child', 'string', ['comment' => '直播间子分区', 'null' => true])
            ->addColumn('files_path', 'string', ['comment' => '文件路径', 'null' => true])
            ->addColumn('files_name', 'string', ['comment' => '文件名称', 'null' => true])
            ->addColumn('start_time', 'integer', ['comment' => '录制开始时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('down_time', 'integer', ['comment' => '录制结束时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('duration', 'integer', ['comment' => '录制时长', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
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
        $this->table('bili_live_files')->drop()->save();
    }
}

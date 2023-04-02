<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateBiliQuestionBox extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bili_question_box', ['id' => false, 'primary_key' => 'box_id', 'comment' => '投稿信息表']);
        $table->addColumn('box_id', 'uuid', ['comment' => '投稿id', 'null' => false])
            ->addColumn('ip', 'string', ['comment' => '投稿ip', 'null' => true])
            ->addColumn('type', 'integer', ['comment' => '投稿类型', 'null' => true, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('content', 'text', ['comment' => '投稿内容', 'null' => true])
            ->addColumn('read', 'integer', ['comment' => '是否已读', 'null' => false, 'default' => 0, 'limit' => MysqlAdapter::INT_TINY])
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
        $this->table('bili_question_box')->drop()->save();
    }
}

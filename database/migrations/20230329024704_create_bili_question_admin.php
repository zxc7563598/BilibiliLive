<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateBiliQuestionAdmin extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bili_question_admin', ['id' => false, 'primary_key' => 'admin_id', 'comment' => '投稿管理员表']);
        $table->addColumn('admin_id', 'uuid', ['comment' => '管理员id', 'null' => false])
            ->addColumn('account', 'string', ['comment' => '账号', 'null' => true])
            ->addColumn('password', 'string', ['comment' => '密码', 'null' => true])
            ->addColumn('salt', 'integer', ['comment' => '扰乱码', 'null' => true, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('token', 'string', ['comment' => '登陆凭证', 'null' => true])
            ->addColumn('last_login_at', 'integer', ['comment' => '最后登录时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('last_login_ip', 'string', ['comment' => '最后登录ip', 'null' => true])
            ->addColumn('login_times', 'integer', ['comment' => '登录次数', 'null' => false, 'default' => 0, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('status', 'integer', ['comment' => '状态', 'null' => false, 'limit' => MysqlAdapter::INT_TINY])
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
        $this->table('bili_question_admin')->drop()->save();
    }
}

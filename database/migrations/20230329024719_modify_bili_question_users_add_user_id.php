<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ModifyBiliQuestionUsersAddUserId extends AbstractMigration
{

    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bili_question_box');
        $table->addColumn('real_name', 'string', ['comment' => '名称', 'null' => true, 'after' => 'box_id'])
        ->addColumn('user_id', 'uuid', ['comment' => '用户id', 'null' => true, 'after' => 'box_id'])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('bili_question_box');
        $table->removeColumn('user_id')
            ->save();
    }
}

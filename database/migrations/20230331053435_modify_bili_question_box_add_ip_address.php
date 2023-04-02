<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ModifyBiliQuestionBoxAddIpAddress extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bili_question_box');
        $table->addColumn('ip_address', 'string', ['comment' => 'IP归属地', 'null' => true, 'after' => 'ip'])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('bili_question_box');
        $table->removeColumn('ip_address')
            ->save();
    }
}

<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ModifyLiveBlackAddKeyWords extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('live_black');
        $table->addColumn('key_words', 'string', ['comment' => '命中关键词', 'null' => true, 'after' => 'msg'])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('live_black');
        $table->removeColumn('key_words')
            ->save();
    }
}

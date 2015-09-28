<?php

namespace olessavluk\settings;

use yii\db\Migration;

class m150929_122401_settings extends Migration
{
    public $tableName = '{{%settings}}';

    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }
        $this->createTable($this->tableName, [
            'category' => $this->string()->notNull()->defaultValue('app'),
            'key' => $this->string()->notNull(),

            'value' => $this->text()->notNull(),
        ], $tableOptions);

        $this->addPrimaryKey('pk_settings', $this->tableName, ['category', 'key']);
    }

    public function down()
    {
        $this->dropTable($this->tableName);
    }
}

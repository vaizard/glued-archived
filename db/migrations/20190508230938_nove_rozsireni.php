<?php


use Phinx\Migration\AbstractMigration;

class NoveRozsireni extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $table = $this->table('t_users');
        $table->addColumn('profile_data', 'json', ['after' => 'stor_name', 'null' => true, 'default' => null])->update();
        
        $count = $this->execute("
        ALTER TABLE `t_accounting_received` ADD `stor_name` varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (json_unquote(json_extract(`c_data`,'$.data.inv_nr'))) VIRTUAL AFTER `c_data`;
        ");
        
    }
}

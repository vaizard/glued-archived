<?php


use Phinx\Migration\AbstractMigration;

class Cervenec2019 extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('t_accounting_received');
        $table->rename('t_accounting_costs');
    }
    
    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('t_accounting_costs');
        $table->rename('t_accounting_received');
    }
    
}

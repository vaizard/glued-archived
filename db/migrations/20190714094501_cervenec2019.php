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
        
        $table = $this->table('t_users');
        $table->addColumn('c_language ', 'char', ['limit' => 2, 'after' => 'c_screenname', 'default' => 'en'])->update();
        
    }
    
    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('t_accounting_costs');
        $table->rename('t_accounting_received');
        
        $table = $this->table('t_users');
        $table->removeColumn('c_language')->save();
        
    }
    
}

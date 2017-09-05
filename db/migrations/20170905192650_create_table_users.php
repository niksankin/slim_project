<?php


use Phinx\Migration\AbstractMigration;

class CreateTableUsers extends AbstractMigration
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
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $users = $this->table('users');

        $users->addColumn('login', 'string', array('limit'=>32, 'null'=>true, 'default'=>NULL))
            ->addColumn('password', 'string', array('limit'=>255, 'null'=>true, 'default'=>NULL))
            ->addColumn('ip', 'string', array('limit'=>15, 'null'=>true, 'default'=>NULL))
            ->addColumn('fullname', 'string', array('limit'=>255, 'null'=>true, 'default'=>NULL))
            ->addColumn('email', 'string', array('limit'=>255, 'null'=>true, 'default'=>NULL))
            ->addColumn('status', 'string', array('limit'=>7, 'null'=>true, 'default'=>NULL))
            ->addColumn('last_online', 'timestamp', array('null'=>true, 'default'=>NULL))
            ->addColumn('registered', 'timestamp', array('null'=>true, 'default'=>NULL))
            ->create();
    }
}

<?php


use Phinx\Migration\AbstractMigration;

class CreateTablePosts extends AbstractMigration
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
        $users = $this->table('posts');

        $users->addColumn('title', 'string', array('limit'=>32, 'null'=>true, 'default'=>NULL))
            ->addColumn('text', 'string', array('limit'=>4096, 'null'=>true, 'default'=>NULL))
            ->addColumn('user_id', 'int', array('limit'=>8, 'null'=>true, 'default'=>NULL))
            ->addColumn('created_at', 'timestamp', array('null'=>true, 'default'=>NULL))
            ->create();
    }
}

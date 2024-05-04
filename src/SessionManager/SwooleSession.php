<?php
namespace Xel\Async\SessionManager;

use Swoole\Table;

require __DIR__ . "/../../vendor/autoload.php";

class SwooleSession
{
    private Table $table;

    public function __init():void
    {
        $table = new Table(1024);
        $table->column('value', Table::TYPE_STRING, 192);
        $table->column('expired', Table::TYPE_INT, 16);
        $table->create();

        $this->table = $table;
    }

    public function add(string $key, string $value, int $expired):void
    {
        $this->table->set($key, [
            'value' => $value,
            'expired' => $expired
        ]);
    }

    public function get($id):mixed
    {
        return $this->table->get($id);
    }

    public function update(string $key, string $value, int $expired):void
    {
        $this->table->set($key, [
            'value' => $value,
            'expired' => $expired
        ]);
    }

    public function delete($id):void
    {
        $this->table->del($id);
    }

    public function isExist(string $key):bool
    {
        $bool = $this->table->exist($key);
        return $bool;
    }

    public function destroy():void
    {
        $this->table->destroy();
    }

    public function count():int{
        return $this->table->count();
    }

    public function currentSession():Table{
        return $this->table;
    }
}

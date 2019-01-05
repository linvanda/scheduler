<?php

namespace Scheduler\Infrastructure\Mysql;

/**
 * 一次性遍历集合对象，遍历完即销毁
 * Class FlashCollection
 * @package Scheduler\Infrastructure\Mysql
 */
class FlashCollection implements \Iterator
{
    private $statement;

    private $used = false;

    private $index = -1;

    private $current;

    public function __construct($statement)
    {
        $this->statement = $statement;
    }

    public function current()
    {
        return $this->current;
    }

    public function next()
    {
        if (!$this->used) {
            $result = $this->statement->fetch();

            if ($result === false) {
                // 取完了
                return $this->destroy();
            }

            $this->index++;
            $this->current = $result;
        }
    }

    public function key()
    {
        return $this->index;
    }

    public function valid()
    {
        return !$this->used;
    }

    public function rewind()
    {
        // Do Nothing
    }

    private function destroy()
    {
        $this->used = true;
        $this->index = -1;
        $this->current = null;
    }
}

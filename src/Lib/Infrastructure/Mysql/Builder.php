<?php

namespace Scheduler\Infrastructure\MySQL;

/**
 * 查询构造器
 * 目前只支持构造 select,update,insert,replace,delete
 * Class Builder
 * @package Scheduler\Infrastructure\MySQL
 */
class Builder
{
    private $type;
    private $fields;
    private $table;
    private $from;
    private $where;
    private $join;
    private $limit;
    private $orderBy;
    private $groupBy;
    private $having;
    private $values;
    private $forceIndex;
    private $params = [];

    public function select($fields = '*')
    {
        if ($this->type) {
            return $this;
        }

        $this->type = 'select';
        $this->fields = $this->fields($fields);

        return $this;
    }

    public function update(string $table)
    {
        if ($this->type) {
            return $this;
        }

        $this->type = 'update';
        $this->table = $table;

        return $this;
    }

    public function insert(string $table)
    {
        if ($this->type) {
            return $this;
        }

        $this->type = 'insert';
        $this->table = $table;

        return $this;
    }

    public function replace(string $table)
    {
        if ($this->type) {
            return $this;
        }

        $this->type = 'replace';
        $this->table = $table;

        return $this;
    }

    public function delete(string $table)
    {
        if ($this->type) {
            return $this;
        }

        $this->type = 'delete';
        $this->table = $table;

        return $this;
    }

    /**
     * @param string $table 如 'users'，'users as u'
     * @return Builder
     */
    public function from(string $table)
    {
        $this->from = $table;

        return $this;
    }

    public function forceIndex(string $index)
    {
        $this->forceIndex = "force index($index)";
    }

    /**
     * @param array $joinInfo ['table' => 'table_name', 'type' => 'inner', 'on' => condition]，或者是这种格式的二维数组
     * 其中：type 可能的值：inner、left、right，默认是 inner；condition 符合 where 格式
     */
    public function join(array $joinInfo)
    {
        if (!$joinInfo[0] && $joinInfo['table']) {
            $joinInfo = [$joinInfo];
        }

        $joinStr = '';
        foreach ($joinInfo as $join) {
            if (!is_array($join) || !$join['table'] || !$join['on']) {
                continue;
            }

            $join['type'] = $join['type'] && in_array(strtolower($join['type']), ['left', 'inner', 'right']) ?: 'inner';

            $joinStr .= "{$join['type']} join {$join['table']} on " . $this->condition($join['on']) . ' ';
        }

        $this->join = $joinStr;
    }

    public function where($conditions)
    {
        $this->where = $this->condition($conditions);

        return $this;
    }

    /**
     * @param int $limit 从 0 开始
     * @param int $offset
     * @return Builder
     */
    public function page(int $limit, int $offset)
    {
        $this->limit = "limit $limit,$offset";

        return $this;
    }

    /**
     * @param string $fields
     * @return Builder
     */
    public function groupBy(string $fields)
    {
        $this->groupBy = $fields;

        return $this;
    }

    public function having($conditions)
    {
        $this->having = $this->condition($conditions);

        return $this;
    }

    public function orderBy($orderInfo)
    {
        if (is_string($orderInfo)) {
            $this->orderBy = $orderInfo;
            return $this;
        }

        $order = 'order by ';
        foreach ($orderInfo as $field => $type) {
            $type = in_array(strtolower($type), ['asc', 'desc']) ? $type : 'desc';
            $order .= $field . ' ' . $type . ',';
        }

        $this->orderBy = rtrim($order, ',');

        return $this;
    }

    public function values(array $insertData)
    {
        if (is_string($insertData)) {
            $this->values = $insertData;

            return $this;
        }

        if (!$insertData[0]) {
            $insertData = [$insertData];
        }

        $values = '(' . implode(',', array_keys($insertData[0])) . ') values';
        $params = [];
        $i = 0;
        foreach ($insertData as $data) {
            $values .= '(';
            foreach ($data as $field => $value) {
                $flag = $field . '_' . $i;
                $values .= ":$flag,";
                $params[$flag] = $value;
                $i++;
            }
            $values = rtrim($values, ',') . '),';
        }

        $this->values = rtrim($values, ',');
        $this->params = $params;

        return $this;
    }

    /**
     * @param string|array $fields
     * @return string
     */
    private function fields($fields)
    {
        if (!$fields) {
            return '*';
        }

        if (is_string($fields)) {
            return $fields;
        }

        return implode(',', $fields);
    }

    private function condition($conditions)
    {

    }
}

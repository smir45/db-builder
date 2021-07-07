<?php

namespace Ryzen\DbBuilder;

use Closure;
use PDO;
use PDOException;

class DbBuilder implements DbInterface
{

    public $pdo = null;

    protected $select   = '*';
    protected $from     = null;
    protected $where    = null;
    protected $limit    = null;
    protected $offset   = null;
    protected $join     = null;
    protected $orderBy  = null;
    protected $groupBy  = null;
    protected $having   = null;
    protected $grouped  = false;
    protected $numRows  = 0;
    protected $insertId = null;
    protected $query    = null;
    protected $error    = null;
    protected $result   = [];
    protected $prefix   = null;

    protected $operators = ['=', '!=', '<', '>', '<=', '>=', '<>'];

    protected $queryCount = 0;
    protected $transactionCount = 0;
    protected $debug = true;
    protected $cache = null;
    protected $cacheDir = null;

    public function __construct(array $config){

        $config['mysql_database_config']['db_driver']       = $config['mysql_database_config']['db_driver'] ?? 'mysql';
        $config['mysql_database_config']['db_host']         = $config['mysql_database_config']['db_host'] ?? 'localhost';
        $config['mysql_database_config']['db_charset']      = $config['mysql_database_config']['db_charset'] ?? 'utf8mb4';
        $config['mysql_database_config']['db_collation']    = $config['mysql_database_config']['db_collation'] ?? 'utf8mb4_general_ci';
        $config['mysql_database_config']['db_port']         = $config['mysql_database_config']['db_port'] ?? (strstr($config['mysql_database_config']['db_host'], ':') ? explode(':', $config['mysql_database_config']['db_host'])[1] : '');
        $this->prefix           = $config['mysql_database_config']['db_prefix'] ?? '';
        $this->debug            = $config['debug'] ?? true;
        $this->cacheDir         = $config['cachedir'] ?? __DIR__ . '/cache/';

        $dsn = '';
        if (in_array($config['mysql_database_config']['db_driver'], ['', 'mysql', 'pgsql'])) {
            $dsn = $config['mysql_database_config']['db_driver'] . ':host=' . str_replace(':' . $config['mysql_database_config']['db_port'], '', $config['mysql_database_config']['db_host']) . ';'
                . ($config['mysql_database_config']['db_port'] !== '' ? 'port=' . $config['mysql_database_config']['db_port'] . ';' : '')
                . 'dbname=' . $config['mysql_database_config']['db_name'];
        } elseif ($config['mysql_database_config']['db_driver'] === 'sqlite') {
            $dsn = 'sqlite:' . $config['mysql_database_config']['db_name'];
        } elseif ($config['mysql_database_config']['driver'] === 'oracle') {
            $dsn = 'oci:dbname=' . $config['mysql_database_config']['db_host'] . '/' . $config['mysql_database_config']['db_name'];
        }

        try {
            $this->pdo = new PDO($dsn, $config['mysql_database_config']['db_user'], $config['mysql_database_config']['db_pass']);
            $this->pdo->exec("SET NAMES '" . $config['mysql_database_config']['db_charset'] . "' COLLATE '" . $config['mysql_database_config']['db_collation'] . "'");
            $this->pdo->exec("SET CHARACTER SET '" . $config['mysql_database_config']['db_charset'] . "'");
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            die('Speicified Database Cannot Be Connected With PDO. ' . $e->getMessage());
        }
        return $this->pdo;
    }

    public function table( $table ): DbBuilder
    {

        if(is_array( $table )){
            $from = '';
            foreach ($table as $key){
                $from .= $this->prefix.$key.', ';
            }
            $this->from = rtrim($from,', ');
        }else{
            if(strpos($table,', ')>0){
                $table = explode(',', $table);
                foreach ($table as $key => $value){
                    $value = $this->prefix.ltrim($value);
                }
                $this->from = implode(', ', $table);
            }else{
                $this->from = $this->prefix.$table;
            }
        }
        return $this;
    }

    public function select($fields): DbBuilder
    {
        $select = is_array($fields) ? implode(', ',$fields) : $fields;
        $this->optimizeSelect($select);
        return $this;
    }

    public function max($field, $name = null): DbBuilder
    {
        $column = 'MAX(' . $field . ')' . (!is_null($name) ? ' AS '. $name : '');
        $this->optimizeSelect($column);
        return $this;
    }

    public function min($field, $name = null): DbBuilder
    {
        $column = 'MIN(' . $field . ')' . (!is_null($name) ? ' AS ' . $name : '');
        $this->optimizeSelect($column);
        return $this;
    }

    public function sum($field, $name = null): DbBuilder
    {
        $column = 'SUM(' . $field . ')' . (!is_null($name) ? ' AS ' . $name : '');
        $this->optimizeSelect($column);
        return $this;
    }

    public function count($field, $name = null): DbBuilder
    {
        $column = 'COUNT(' . $field . ')' . (!is_null($name) ? ' AS ' . $name : '');
        $this->optimizeSelect($column);
        return $this;
    }

    public function avg($field, $name = null): DbBuilder
    {
        $column = 'AVG(' . $field . ')' . (!is_null($name) ? ' AS ' . $name : '');
        $this->optimizeSelect($column);
        return $this;
    }

    public function join($table, $field1 = null, $operator = null, $field2 = null, $type = ''): DbBuilder
    {
        $on = $field1;
        $table = $this->prefix . $table;

        if (!is_null($operator)) {
            $on = !in_array($operator, $this->operators)
                ? $field1 . ' = ' . $operator . (!is_null($field2) ? ' ' . $field2 : '')
                : $field1 . ' ' . $operator . ' ' . $field2;
        }

        $this->join = (is_null($this->join))
            ? ' ' . $type . 'JOIN' . ' ' . $table . ' ON ' . $on
            : $this->join . ' ' . $type . 'JOIN' . ' ' . $table . ' ON ' . $on;

        return $this;
    }

    public function innerJoin($table, $field1, $operator = '', $field2 = ''): DbBuilder
    {
        return $this->join($table, $field1, $operator, $field2, 'INNER ');
    }

    public function leftJoin($table, $field1, $operator = '', $field2 = ''): DbBuilder
    {
        return $this->join($table, $field1, $operator, $field2, 'LEFT ');
    }

    public function rightJoin($table, $field1, $operator = '', $field2 = ''): DbBuilder
    {
        return $this->join($table, $field1, $operator, $field2, 'RIGHT ');
    }

    public function fullOuterJoin($table, $field1, $operator = '', $field2 = ''): DbBuilder
    {
        return $this->join($table, $field1, $operator, $field2, 'FULL OUTER ');
    }

    public function leftOuterJoin($table, $field1, $operator = '', $field2 = ''): DbBuilder
    {
        return $this->join($table, $field1, $operator, $field2, 'LEFT OUTER ');
    }

    public function rightOuterJoin($table, $field1, $operator = '', $field2 = ''): DbBuilder
    {
        return $this->join($table, $field1, $operator, $field2, 'RIGHT OUTER ');
    }

    public function where($where, $operator = null, $val = null, $type = '', $andOr = 'AND'): DbBuilder
    {
        if (is_array($where) && !empty($where)) {
            $_where = [];
            foreach ($where as $column => $data) {
                $_where[] = $type . $column . '=' . $this->escape($data);
            }
            $where = implode(' ' . $andOr . ' ', $_where);
        } else {
            if (is_null($where) || empty($where)) {
                return $this;
            }

            if (is_array($operator)) {
                $params = explode('?', $where);
                $_where = '';
                foreach ($params as $key => $value) {
                    if (!empty($value)) {
                        $_where .= $type . $value . (isset($operator[$key]) ? $this->escape($operator[$key]) : '');
                    }
                }
                $where = $_where;
            } elseif (!in_array($operator, $this->operators) || $operator == false) {
                $where = $type . $where . ' = ' . $this->escape($operator);
            } else {
                $where = $type . $where . ' ' . $operator . ' ' . $this->escape($val);
            }
        }

        if ($this->grouped) {
            $where = '(' . $where;
            $this->grouped = false;
        }

        $this->where = is_null($this->where)
            ? $where
            : $this->where . ' ' . $andOr . ' ' . $where;

        return $this;
    }

    public function orWhere($where, $operator = null, $val = null): DbBuilder
    {
        return $this->where($where, $operator, $val, '', 'OR');
    }

    public function notWhere($where, $operator = null, $val = null): DbBuilder
    {
        return $this->where($where, $operator, $val, 'NOT ', 'AND');
    }

    public function orNotWhere($where, $operator = null, $val = null): DbBuilder
    {
        return $this->where($where, $operator, $val, 'NOT ', 'OR');
    }

    public function whereNull($where, $not = false): DbBuilder
    {
        $where = $where . ' IS ' . ($not ? 'NOT' : '') . ' NULL';
        $this->where = is_null($this->where) ? $where : $this->where . ' ' . 'AND ' . $where;

        return $this;
    }

    public function whereNotNull($where): DbBuilder
    {
        return $this->whereNull($where, true);
    }

    public function grouped(Closure $obj): DbBuilder
    {
        $this->grouped = true;
        call_user_func_array($obj, [$this]);
        $this->where .= ')';

        return $this;
    }

    public function in($field, array $keys, $type = '', $andOr = 'AND'): DbBuilder
    {
        if (is_array($keys)) {
            $_keys = [];
            foreach ($keys as $k => $v) {
                $_keys[] = is_numeric($v) ? $v : $this->escape($v);
            }
            $where = $field . ' ' . $type . 'IN (' . implode(', ', $_keys) . ')';

            if ($this->grouped) {
                $where = '(' . $where;
                $this->grouped = false;
            }

            $this->where = is_null($this->where)
                ? $where
                : $this->where . ' ' . $andOr . ' ' . $where;
        }

        return $this;
    }

    public function notIn($field, array $keys): DbBuilder
    {
        return $this->in($field, $keys, 'NOT ', 'AND');
    }

    public function orIn($field, array $keys): DbBuilder
    {
        return $this->in($field, $keys, '', 'OR');
    }

    public function orNotIn($field, array $keys): DbBuilder
    {
        return $this->in($field, $keys, 'NOT ', 'OR');
    }

    public function between($field, $value1, $value2, $type = '', $andOr = 'AND'): DbBuilder
    {
        $where = '(' . $field . ' ' . $type . 'BETWEEN ' . ($this->escape($value1) . ' AND ' . $this->escape($value2)) . ')';
        if ($this->grouped) {
            $where = '(' . $where;
            $this->grouped = false;
        }

        $this->where = is_null($this->where)
            ? $where
            : $this->where . ' ' . $andOr . ' ' . $where;

        return $this;
    }

    public function notBetween($field, $value1, $value2): DbBuilder
    {
        return $this->between($field, $value1, $value2, 'NOT ', 'AND');
    }

    public function orBetween($field, $value1, $value2): DbBuilder
    {
        return $this->between($field, $value1, $value2, '', 'OR');
    }

    public function orNotBetween($field, $value1, $value2): DbBuilder
    {
        return $this->between($field, $value1, $value2, 'NOT ', 'OR');
    }

    public function like($field, $data, $type = '', $andOr = 'AND'): DbBuilder
    {
        $like = $this->escape($data);
        $where = $field . ' ' . $type . 'LIKE ' . $like;

        if ($this->grouped) {
            $where = '(' . $where;
            $this->grouped = false;
        }

        $this->where = is_null($this->where)
            ? $where
            : $this->where . ' ' . $andOr . ' ' . $where;

        return $this;
    }

    public function orLike($field, $data): DbBuilder
    {
        return $this->like($field, $data, '', 'OR');
    }

    public function notLike($field, $data): DbBuilder
    {
        return $this->like($field, $data, 'NOT ', 'AND');
    }

    public function orNotLike($field, $data): DbBuilder
    {
        return $this->like($field, $data, 'NOT ', 'OR');
    }

    public function limit($limit, $limitEnd = null): DbBuilder
    {
        $this->limit = !is_null($limitEnd)
            ? $limit . ', ' . $limitEnd
            : $limit;

        return $this;
    }

    public function offset($offset): DbBuilder
    {
        $this->offset = $offset;

        return $this;
    }

    public function pagination($perPage, $page): DbBuilder
    {
        $this->limit = $perPage;
        $this->offset = (($page > 0 ? $page : 1) - 1) * $perPage;

        return $this;
    }

    public function orderBy($orderBy, $orderDir = null): DbBuilder
    {
        if (!is_null($orderDir)) {
            $this->orderBy = $orderBy . ' ' . strtoupper($orderDir);
        } else {
            $this->orderBy = stristr($orderBy, ' ') || strtolower($orderBy) === 'rand()'
                ? $orderBy
                : $orderBy . ' ASC';
        }

        return $this;
    }

    public function groupBy($groupBy): DbBuilder
    {
        $this->groupBy = is_array($groupBy) ? implode(', ', $groupBy) : $groupBy;

        return $this;
    }

    public function having($field, $operator = null, $val = null): DbBuilder
    {
        if (is_array($operator)) {
            $fields = explode('?', $field);
            $where = '';
            foreach ($fields as $key => $value) {
                if (!empty($value)) {
                    $where .= $value . (isset($operator[$key]) ? $this->escape($operator[$key]) : '');
                }
            }
            $this->having = $where;
        } elseif (!in_array($operator, $this->operators)) {
            $this->having = $field . ' > ' . $this->escape($operator);
        } else {
            $this->having = $field . ' ' . $operator . ' ' . $this->escape($val);
        }

        return $this;
    }

    public function numRows(): int
    {
        return $this->numRows;
    }

    public function insertId()
    {
        return $this->insertId;
    }

    public function error()
    {
        if ($this->debug === true) {
            if (php_sapi_name() === 'cli') {
                die("Query: " . $this->query . PHP_EOL . "Error: " . $this->error . PHP_EOL);
            }

            $msg = '<h1>Database Error</h1>';
            $msg .= '<h4>Query: <em style="font-weight:normal;">"' . $this->query . '"</em></h4>';
            $msg .= '<h4>Error: <em style="font-weight:normal;">' . $this->error . '</em></h4>';
            die($msg);
        }

        throw new PDOException($this->error . '. (' . $this->query . ')');
    }

    public function get($type = null, $argument = null)
    {
        $this->limit = 1;
        $query = $this->getAll(true);
        return $type === true ? $query : $this->query($query, false, $type, $argument);
    }

    public function getAll($type = null, $argument = null)
    {
        $query = 'SELECT ' . $this->select . ' FROM ' . $this->from;

        if (!is_null($this->join)) {
            $query .= $this->join;
        }

        if (!is_null($this->where)) {
            $query .= ' WHERE ' . $this->where;
        }

        if (!is_null($this->groupBy)) {
            $query .= ' GROUP BY ' . $this->groupBy;
        }

        if (!is_null($this->having)) {
            $query .= ' HAVING ' . $this->having;
        }

        if (!is_null($this->orderBy)) {
            $query .= ' ORDER BY ' . $this->orderBy;
        }

        if (!is_null($this->limit)) {
            $query .= ' LIMIT ' . $this->limit;
        }

        if (!is_null($this->offset)) {
            $query .= ' OFFSET ' . $this->offset;
        }

        return $type === true ? $query : $this->query($query, true, $type, $argument);
    }

    public function insert(array $data, $type = false)
    {
        $query = 'INSERT INTO ' . $this->from;

        $values = array_values($data);
        if (isset($values[0]) && is_array($values[0])) {
            $column = implode(', ', array_keys($values[0]));
            $query .= ' (' . $column . ') VALUES ';
            foreach ($values as $value) {
                $val = implode(', ', array_map([$this, 'escape'], $value));
                $query .= '(' . $val . '), ';
            }
            $query = trim($query, ', ');
        } else {
            $column = implode(', ', array_keys($data));
            $val = implode(', ', array_map([$this, 'escape'], $data));
            $query .= ' (' . $column . ') VALUES (' . $val . ')';
        }

        if ($type === true) {
            return $query;
        }

        if ($this->query($query, false)) {
            $this->insertId = $this->pdo->lastInsertId();
            return $this->insertId();
        }

        return false;
    }

    public function update(array $data, $type = false)
    {
        $query = 'UPDATE ' . $this->from . ' SET ';
        $values = [];

        foreach ($data as $column => $val) {
            $values[] = $column . '=' . $this->escape($val);
        }
        $query .= implode(',', $values);

        if (!is_null($this->where)) {
            $query .= ' WHERE ' . $this->where;
        }

        if (!is_null($this->orderBy)) {
            $query .= ' ORDER BY ' . $this->orderBy;
        }

        if (!is_null($this->limit)) {
            $query .= ' LIMIT ' . $this->limit;
        }

        return $type === true ? $query : $this->query($query, false);
    }

    public function delete($type = false)
    {
        $query = 'DELETE FROM ' . $this->from;

        if (!is_null($this->where)) {
            $query .= ' WHERE ' . $this->where;
        }

        if (!is_null($this->orderBy)) {
            $query .= ' ORDER BY ' . $this->orderBy;
        }

        if (!is_null($this->limit)) {
            $query .= ' LIMIT ' . $this->limit;
        }

        if ($query === 'DELETE FROM ' . $this->from) {
            $query = 'TRUNCATE TABLE ' . $this->from;
        }

        return $type === true ? $query : $this->query($query, false);
    }

    public function analyze()
    {
        return $this->query('ANALYZE TABLE ' . $this->from, false);
    }

    public function check()
    {
        return $this->query('CHECK TABLE ' . $this->from, false);
    }

    public function checksum()
    {
        return $this->query('CHECKSUM TABLE ' . $this->from, false);
    }

    public function optimize()
    {
        return $this->query('OPTIMIZE TABLE ' . $this->from, false);
    }

    public function repair()
    {
        return $this->query('REPAIR TABLE ' . $this->from, false);
    }

    public function transaction(): bool
    {
        if (!$this->transactionCount++) {
            return $this->pdo->beginTransaction();
        }

        $this->pdo->exec('SAVEPOINT trans' . $this->transactionCount);
        return $this->transactionCount >= 0;
    }

    public function commit(): bool
    {
        if (!--$this->transactionCount) {
            return $this->pdo->commit();
        }

        return $this->transactionCount >= 0;
    }

    public function rollBack(): bool
    {
        if (--$this->transactionCount) {
            $this->pdo->exec('ROLLBACK TO trans' . ($this->transactionCount + 1));
            return true;
        }

        return $this->pdo->rollBack();
    }

    public function exec()
    {
        if (is_null($this->query)) {
            return null;
        }

        $query = $this->pdo->exec($this->query);
        if ($query === false) {
            $this->error = $this->pdo->errorInfo()[2];
            $this->error();
        }

        return $query;
    }

    public function fetch($type = null, $argument = null, $all = false)
    {
        if (is_null($this->query)) {
            return null;
        }

        $query = $this->pdo->query($this->query);
        if (!$query) {
            $this->error = $this->pdo->errorInfo()[2];
            $this->error();
        }

        $type = $this->getFetchType($type);
        if ($type === PDO::FETCH_CLASS) {
            $query->setFetchMode($type, $argument);
        } else {
            $query->setFetchMode($type);
        }

        $result = $all ? $query->fetchAll() : $query->fetch();
        $this->numRows = is_array($result) ? count($result) : 1;
        return $result;
    }

    public function fetchAll($type = null, $argument = null)
    {
        return $this->fetch($type, $argument, true);
    }

    public function query($query, $all = true, $type = null, $argument = null)
    {
        $this->reset();

        if (is_array($all) || func_num_args() === 1) {
            $params = explode('?', $query);
            $newQuery = '';
            foreach ($params as $key => $value) {
                if (!empty($value)) {
                    $newQuery .= $value . (isset($all[$key]) ? $this->escape($all[$key]) : '');
                }
            }
            $this->query = $newQuery;
            return $this;
        }

        $this->query = preg_replace('/\s\s+|\t\t+/', ' ', trim($query));
        $str = false;
        foreach (['select', 'optimize', 'check', 'repair', 'checksum', 'analyze'] as $value) {
            if (stripos($this->query, $value) === 0) {
                $str = true;
                break;
            }
        }

        $type = $this->getFetchType($type);
        $cache = false;
        if (!is_null($this->cache) && $type !== PDO::FETCH_CLASS) {
            $cache = $this->cache->getCache($this->query, $type === PDO::FETCH_ASSOC);
        }

        if (!$cache && $str) {
            $sql = $this->pdo->query($this->query);
            if ($sql) {
                $this->numRows = $sql->rowCount();
                if ($this->numRows > 0) {
                    if ($type === PDO::FETCH_CLASS) {
                        $sql->setFetchMode($type, $argument);
                    } else {
                        $sql->setFetchMode($type);
                    }
                    $this->result = $all ? $sql->fetchAll() : $sql->fetch();
                }

                if (!is_null($this->cache) && $type !== PDO::FETCH_CLASS) {
                    $this->cache->setCache($this->query, $this->result);
                }
                $this->cache = null;
            } else {
                $this->cache = null;
                $this->error = $this->pdo->errorInfo()[2];
                $this->error();
            }
        } elseif ((!$cache && !$str) || ($cache && !$str)) {
            $this->cache = null;
            $this->result = $this->pdo->exec($this->query);

            if ($this->result === false) {
                $this->error = $this->pdo->errorInfo()[2];
                $this->error();
            }
        } else {
            $this->cache = null;
            $this->result = $cache;
            $this->numRows = is_array($this->result) ? count($this->result) : ($this->result === '' ? 0 : 1);
        }

        $this->queryCount++;
        return $this->result;
    }

    public function escape($data)
    {
        return $data === null ? 'NULL' : (
        is_int($data) || is_float($data) ? $data : $this->pdo->quote($data)
        );
    }

    public function cache($time): DbBuilder
    {
        $this->cache = new Cache($this->cacheDir, $time);

        return $this;
    }

    public function queryCount(): int
    {
        return $this->queryCount;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function __destruct()
    {
        $this->pdo = null;
    }

    protected function reset()
    {
        $this->select   = '*';
        $this->from     = null;
        $this->where    = null;
        $this->limit    = null;
        $this->offset   = null;
        $this->orderBy  = null;
        $this->groupBy  = null;
        $this->having   = null;
        $this->join     = null;
        $this->grouped  = false;
        $this->numRows  = 0;
        $this->insertId = null;
        $this->query    = null;
        $this->error    = null;
        $this->result   = [];
        $this->transactionCount = 0;
    }

    protected function getFetchType($type): int
    {
        return $type === 'class' ? PDO::FETCH_CLASS : ($type === 'array' ? PDO::FETCH_ASSOC : PDO::FETCH_OBJ);
    }

    private function optimizeSelect($fields)
    {
        $this->select = $this->select === '*' ? $fields : $this->select . ', ' . $fields;
    }
}
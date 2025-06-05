<?php

namespace App\Helpers;

use PDO;
use PDOException;

class Database
{
    private static $connection = null;
    private static $config = null;

    public static function init()
    {
        if (self::$connection === null) {
            // Charger la configuration depuis le fichier
            self::$config = require __DIR__ . '/../Config/database.php';
            
            try {
                $connectionConfig = self::$config['connections'][self::$config['default']];
                
                $dsn = sprintf(
                    "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                    $connectionConfig['host'],
                    $connectionConfig['port'],
                    $connectionConfig['database'],
                    $connectionConfig['charset']
                );

                self::$connection = new PDO(
                    $dsn,
                    $connectionConfig['username'],
                    $connectionConfig['password'],
                    $connectionConfig['options']
                );
                
                // Définir le collation
                self::$connection->exec("SET NAMES {$connectionConfig['charset']} COLLATE {$connectionConfig['collation']}");
                
            } catch (PDOException $e) {
                error_log("Erreur de connexion à la base de données : " . $e->getMessage());
                die("Erreur de connexion à la base de données");
            }
        }
    }

    public static function getConnection()
    {
        if (self::$connection === null) {
            self::init();
        }
        return self::$connection;
    }

    public static function getConfig($key = null)
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../Config/database.php';
        }
        
        if ($key === null) {
            return self::$config;
        }
        
        return self::$config[$key] ?? null;
    }

    public static function query($sql, $params = [])
    {
        try {
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erreur SQL : " . $e->getMessage() . " - Requête : " . $sql);
            throw $e;
        }
    }

    public static function fetch($sql, $params = [])
    {
        return self::query($sql, $params)->fetch();
    }

    public static function fetchAll($sql, $params = [])
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert($table, $data)
    {
        $keys = array_keys($data);
        $fields = implode(',', $keys);
        $placeholders = ':' . implode(',:', $keys);
        
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        self::query($sql, $data);
        
        return self::getConnection()->lastInsertId();
    }

    public static function update($table, $data, $where, $whereParams = [])
    {
        $fields = [];
        foreach (array_keys($data) as $key) {
            $fields[] = "{$key} = :{$key}";
        }
        $fields = implode(',', $fields);
        
        $sql = "UPDATE {$table} SET {$fields} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        return self::query($sql, $params)->rowCount();
    }

    public static function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return self::query($sql, $params)->rowCount();
    }

    public static function beginTransaction()
    {
        return self::getConnection()->beginTransaction();
    }

    public static function commit()
    {
        return self::getConnection()->commit();
    }

    public static function rollback()
    {
        return self::getConnection()->rollBack();
    }

    public static function inTransaction()
    {
        return self::getConnection()->inTransaction();
    }

    public static function table($table)
    {
        return new DatabaseQueryBuilder($table);
    }

    public static function raw($sql, $params = [])
    {
        return self::query($sql, $params);
    }

    public static function exists($table, $where, $params = [])
    {
        $sql = "SELECT 1 FROM {$table} WHERE {$where} LIMIT 1";
        $result = self::fetch($sql, $params);
        return !empty($result);
    }

    public static function count($table, $where = '1=1', $params = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$where}";
        $result = self::fetch($sql, $params);
        return $result['count'] ?? 0;
    }
}

// Classe pour construire des requêtes de manière fluide
class DatabaseQueryBuilder
{
    private $table;
    private $select = ['*'];
    private $where = [];
    private $joins = [];
    private $orderBy = [];
    private $groupBy = [];
    private $having = [];
    private $limit = null;
    private $offset = null;
    private $params = [];

    public function __construct($table)
    {
        $this->table = $table;
    }

    public function select($columns = ['*'])
    {
        $this->select = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function where($column, $operator = '=', $value = null)
    {
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        $placeholder = 'param_' . count($this->params);
        $this->where[] = "{$column} {$operator} :{$placeholder}";
        $this->params[$placeholder] = $value;

        return $this;
    }

    public function whereIn($column, $values)
    {
        if (empty($values)) {
            // Si le tableau est vide, ajouter une condition toujours fausse
            $this->where[] = "1 = 0";
            return $this;
        }

        $placeholders = [];
        foreach ($values as $i => $value) {
            $placeholder = 'param_' . count($this->params);
            $placeholders[] = ":{$placeholder}";
            $this->params[$placeholder] = $value;
        }

        $this->where[] = "{$column} IN (" . implode(',', $placeholders) . ")";
        return $this;
    }

    public function whereNotIn($column, $values)
    {
        if (empty($values)) {
            return $this;
        }

        $placeholders = [];
        foreach ($values as $i => $value) {
            $placeholder = 'param_' . count($this->params);
            $placeholders[] = ":{$placeholder}";
            $this->params[$placeholder] = $value;
        }

        $this->where[] = "{$column} NOT IN (" . implode(',', $placeholders) . ")";
        return $this;
    }

    public function whereNull($column)
    {
        $this->where[] = "{$column} IS NULL";
        return $this;
    }

    public function whereNotNull($column)
    {
        $this->where[] = "{$column} IS NOT NULL";
        return $this;
    }

    public function whereBetween($column, $min, $max)
    {
        $placeholder1 = 'param_' . count($this->params);
        $this->params[$placeholder1] = $min;
        
        $placeholder2 = 'param_' . count($this->params);
        $this->params[$placeholder2] = $max;

        $this->where[] = "{$column} BETWEEN :{$placeholder1} AND :{$placeholder2}";
        return $this;
    }

    public function whereLike($column, $value)
    {
        $placeholder = 'param_' . count($this->params);
        $this->where[] = "{$column} LIKE :{$placeholder}";
        $this->params[$placeholder] = $value;
        return $this;
    }

    public function orWhere($column, $operator = '=', $value = null)
    {
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        $placeholder = 'param_' . count($this->params);
        
        // Si c'est la première condition WHERE, utiliser WHERE sinon OR
        $connector = empty($this->where) ? '' : 'OR ';
        $this->where[] = $connector . "{$column} {$operator} :{$placeholder}";
        $this->params[$placeholder] = $value;

        return $this;
    }

    public function join($table, $first, $operator = '=', $second = null)
    {
        if (func_num_args() == 3) {
            $second = $operator;
            $operator = '=';
        }

        $this->joins[] = "INNER JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function leftJoin($table, $first, $operator = '=', $second = null)
    {
        if (func_num_args() == 3) {
            $second = $operator;
            $operator = '=';
        }

        $this->joins[] = "LEFT JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function rightJoin($table, $first, $operator = '=', $second = null)
    {
        if (func_num_args() == 3) {
            $second = $operator;
            $operator = '=';
        }

        $this->joins[] = "RIGHT JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function orderBy($column, $direction = 'ASC')
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }
        
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }

    public function orderByDesc($column)
    {
        return $this->orderBy($column, 'DESC');
    }

    public function groupBy($column)
    {
        $this->groupBy[] = $column;
        return $this;
    }

    public function having($column, $operator = '=', $value = null)
    {
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        $placeholder = 'param_' . count($this->params);
        $this->having[] = "{$column} {$operator} :{$placeholder}";
        $this->params[$placeholder] = $value;

        return $this;
    }

    public function limit($limit)
    {
        $this->limit = (int)$limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = (int)$offset;
        return $this;
    }

    public function take($limit)
    {
        return $this->limit($limit);
    }

    public function skip($offset)
    {
        return $this->offset($offset);
    }

    public function get()
    {
        $sql = $this->buildSelectQuery();
        return Database::fetchAll($sql, $this->params);
    }

    public function first()
    {
        $this->limit(1);
        $sql = $this->buildSelectQuery();
        return Database::fetch($sql, $this->params);
    }

    public function count($column = '*')
    {
        $originalSelect = $this->select;
        $this->select = ["COUNT({$column}) as count"];
        
        $sql = $this->buildSelectQuery();
        $result = Database::fetch($sql, $this->params);
        
        $this->select = $originalSelect;
        return $result['count'] ?? 0;
    }

    public function sum($column)
    {
        $originalSelect = $this->select;
        $this->select = ["SUM({$column}) as sum"];
        
        $sql = $this->buildSelectQuery();
        $result = Database::fetch($sql, $this->params);
        
        $this->select = $originalSelect;
        return $result['sum'] ?? 0;
    }

    public function avg($column)
    {
        $originalSelect = $this->select;
        $this->select = ["AVG({$column}) as avg"];
        
        $sql = $this->buildSelectQuery();
        $result = Database::fetch($sql, $this->params);
        
        $this->select = $originalSelect;
        return $result['avg'] ?? 0;
    }

    public function max($column)
    {
        $originalSelect = $this->select;
        $this->select = ["MAX({$column}) as max"];
        
        $sql = $this->buildSelectQuery();
        $result = Database::fetch($sql, $this->params);
        
        $this->select = $originalSelect;
        return $result['max'];
    }

    public function min($column)
    {
        $originalSelect = $this->select;
        $this->select = ["MIN({$column}) as min"];
        
        $sql = $this->buildSelectQuery();
        $result = Database::fetch($sql, $this->params);
        
        $this->select = $originalSelect;
        return $result['min'];
    }

    public function exists()
    {
        $this->select(['1']);
        $this->limit(1);
        
        $sql = $this->buildSelectQuery();
        $result = Database::fetch($sql, $this->params);
        
        return !empty($result);
    }

    public function doesntExist()
    {
        return !$this->exists();
    }

    public function paginate($page = 1, $perPage = 15)
    {
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        
        // Compter le total
        $total = $this->count();
        
        // Calculer l'offset
        $offset = ($page - 1) * $perPage;
        
        // Récupérer les données paginées
        $data = $this->offset($offset)->limit($perPage)->get();
        
        return [
            'data' => $data,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total),
            'has_more_pages' => $page < ceil($total / $perPage)
        ];
    }

    public function insert($data)
    {
        return Database::insert($this->table, $data);
    }

    public function update($data)
    {
        if (empty($this->where)) {
            throw new \Exception("Impossible de faire un UPDATE sans condition WHERE");
        }

        $fields = [];
        $updateParams = [];
        foreach ($data as $key => $value) {
            $placeholder = 'update_' . $key;
            $fields[] = "{$key} = :{$placeholder}";
            $updateParams[$placeholder] = $value;
        }
        $fields = implode(',', $fields);
        
        $sql = "UPDATE {$this->table} SET {$fields}";
        
        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }
        
        $allParams = array_merge($updateParams, $this->params);
        
        return Database::query($sql, $allParams)->rowCount();
    }

    public function delete()
    {
        if (empty($this->where)) {
            throw new \Exception("Impossible de faire un DELETE sans condition WHERE");
        }

        $sql = "DELETE FROM {$this->table}";
        
        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }
        
        return Database::query($sql, $this->params)->rowCount();
    }

    public function toSql()
    {
        return $this->buildSelectQuery();
    }

    public function getParams()
    {
        return $this->params;
    }

    private function buildSelectQuery()
    {
        $sql = "SELECT " . implode(', ', $this->select) . " FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= " " . implode(' ', $this->joins);
        }

        if (!empty($this->where)) {
            $whereClause = implode(' AND ', $this->where);
            // Nettoyer les conditions OR mal formées
            $whereClause = preg_replace('/^(AND |OR )/', '', $whereClause);
            $sql .= " WHERE " . $whereClause;
        }

        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }

        if (!empty($this->having)) {
            $sql .= " HAVING " . implode(' AND ', $this->having);
        }

        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }
}
?>
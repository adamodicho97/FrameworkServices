<?php

namespace FrameworkServices;

if (! function_exists('str_find')) {

	/**
	 * Searches for a string within another string based on a list and returns
	 * true if one of the items from the list was found.
	 *
	 * @param string $haystack
	 * @param array $needles
	 * @return boolean
	 */
	function str_find(string $haystack, array $needles): bool 
	{
		if (empty($needles)) {
			return false;
		}

		foreach ($needles as $needle) {
			if (strpos($haystack, $needle) === 0) {
				return true;
			} 
		}

		return false;
	}
}
class Db extends BaseServices
{
    /** @var MySQLi object */
    private $conn;

    /** @var resultset $rs */
    private $rs = null;

    /** @var int RO */
    const RO = 0;

    /** @var int CUD */
    const CUD = 1;

    /** @var array connectionRO */
    private $connectionRO = [];

    /** @var array connectionCUD */
    private $connectionCUD = [];

    /**
     * Initiates connection to the database and turns off automatic commits.
     *
     * @return void
     */
    public function __construct($connectionCUD = [], $connectionRO = [])
    {
        if (!empty($connectionCUD)) {
            $this->connectionCUD = $connectionCUD;
        }

        if (!empty($connectionRO)) {
            $this->connectionRO = $connectionRO;
        }

        try {
            if (is_null($this->conn)) {
                $this->connect();
            }
        } catch (\Exception $e) {
            printf($e->getMessage());
        }
    }

    /**
     * Stablishes connection to databases.
     * 
     * @param int $type
     * 
     * 
     * @return void
     */
    public function connect(int $type = self::RO)
    {
        if ($type == $this::RO) {
            $thisConnection = !empty($this->connectionRO)
            ? $this->connectionRO
            : $this->connectionCUD;

            $this->conn = new \mysqli(
                $thisConnection['DB_SERVER'],
                $thisConnection['DB_USERNAME'],
                $thisConnection['DB_PASSWORD'],
                $thisConnection['DB_DATABASE'],
                $thisConnection['DB_PORT']
            );
        }

        if ($type == $this::CUD) {
            $thisConnection = $this->connectionCUD;
            
            $this->conn = new \mysqli(
                $thisConnection['DB_SERVER'],
                $thisConnection['DB_USERNAME'],
                $thisConnection['DB_PASSWORD'],
                $thisConnection['DB_DATABASE'],
                $thisConnection['DB_PORT']
            );
        }

        if ($this->conn->connect_errno) {
            throw new \Exception(
                sprintf(
                    'ERROR[MySQL:%s] Failed to connect to MySQL: %s',
                    $this->conn->connect_errno,
                    $this->conn->connect_error
                )
            );

            die();
        }

        $this->conn->autocommit(false);
    }

    /**
     * Selects the DB name to work on.
     *
     * @param string $db_name
     * @return void
     */
    public function select_db(string $db_name)
    {
        $this->conn->select_db($db_name);

        return $this;
    }

    /**
     * Runs mul queeries on a single call.
     *
     * @param string $multi_query
     * @return void
     */
    public function multi_query(string $multi_query)
    {
        $this->conn->multi_query($multi_query);

        return $this;
    }

    /**
     * Runs a SQL query against the database.
     *
     * @param string $q
     * @return mixed
     */
    public function query(string $q)
    {
        $auth_action = false;

        if (str_find($q, ['INSERT INTO', 'UPDATE', 'DELETE FROM', 'CREATE'])) {
            $this->connect($this::CUD);
            $auth_action = true;
        }

        if (str_find($q, ['SELECT'])) {
            $auth_action = true;
        }

        if (!$auth_action) {
            printf("ERROR[SQL Format] The current query string is invalid.<br/>");
            return false;
        }

        if (!$rs = $this->conn->query($q)) {
            printf("ERROR[SQL] %s<br/>", $this->error());
            return false;
        }

        $this->rs = $rs;

        return $this;
    }

    /**
     * Fetches the next data row from a resultset.
     *
     * @param int $mode
     * @return array|null|false
     */
    public function fetch(int $mode = MYSQLI_ASSOC)
    {
        return $this->rs->fetch_array($mode);
    }

    /**
     * Returns the number of affected/found rows.
     *
     * @return void
     */
    public function num_rows()
    {
        return $this->rs->num_rows;
    }

    /**
     * Stores result from last query, if succed.
     *
     * @return void
     */
    public function store_result()
    {
        return $this->conn->store_result();
    }

    /**
     * Returns error from the las query.
     *
     * @return void
     */
    public function error()
    {
        return $this->conn->error;
    }

    /**
     * Moves the MySQLi pointer to the next result if many queries were executed.
     *
     * @return void
     */
    public function next_result()
    {
        $this->conn->next_result();
    }

    /**
     * Commits last query transaction: INSERT, UPDATE, DELETE actions.
     *
     * @return bool
     */
    public function commit(): bool
    {
        try {
            if (!$this->conn->commit()) {
                throw new \Exception(
                    sprintf('ERROR[Commit] Commit transaction failed.')
                );

                $this->conn->rollback();
            }

            // Last query execution
            if (!$this->rs) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            printf($e->getMessage());
        }
    }

    /**
     * Returns last autoincremented ID from INSERT or UPDATE clause.
     *
     * @return integer
     */
    public function get_last_insert_id(): int
    {
        return $this->conn->insert_id;
    }

    /**
     * Escapes special characters.
     *
     * @param string $string
     * @return string
     */
    public function escape(string $string)
    {
        return $this->conn->real_escape_string($string);
    }

    /**
     * Closes connection.
     *
     * @return void
     */
    public function close()
    {
        $this->conn->close();
    }

    /**
     * Fetches all rows from a resultset.
     *
     * @param int $mode
     * @return array|null|false
     */
    public function fetch_all(int $mode = \MYSQLI_ASSOC): array
    {
        return $this->rs->fetch_all($mode);
    }

    /**
     * Escapes all the special characters in an array
     *
     * @param array $data
     * @return array
     */
    public function escape_all(array $data): array
    {
        $escaped_data = [];
        foreach ($data as $key => $column) {
            if (is_array($column)) {
                $escaped_data[$key] = $this->escape_all($column);
                continue;
            }
            $escaped_data[$key] = $this->escape($column);
        }
        return $escaped_data;
    }

    /**
     * Returns Fetch All with Array Key
     * 
     * @param (string) $key
     * 
     * @return (array)
     */
    public function fetchAll_setKey(string $key, int $mode = \MYSQLI_ASSOC)
    {
        $fetchAll = $this->rs->fetch_all($mode);

        $array = [];
        foreach ($fetchAll as $arraykey => $value) {
            array_key_exists($key, $value) ? $array[$value[$key]] = $value : $array[] = $value;
        }

        return $array;
    }

    /**
     * Inserts Data into Table
     * 
     * @param (string) $table
     * @param (array) $data
     * 
     * @return (int)
     */
    public function insert(string $table, array $data): int
    {
        $columns = '';
        $values = '';
        $newId = 0;

        foreach ($data as $key => $value) {
            $columns .= "`{$this->escape($key)}`,";
            $values .= "'{$this->escape($value)}',";
        }
        $columns = rtrim($columns, ',');
        $values = rtrim($values, ',');

        $insert = $this->query("INSERT INTO $table
                        ($columns)
                        VALUES
                        ($values)");
        if ($insert) {
            $newId = $this->get_last_insert_id();
            $this->commit();
        }

        return $newId;
    }

    /**
     * Updates Data into Table
     * 
     * @param (string) $table
     * @param (array) $data
     * @param (array) $where
     * 
     * @return (bool)
     */
    public function update(string $table, array $data, array $where): bool
    {
        if (empty($where)) {
            return false;
        }

        $sql = '';
        $whereSql = '';

        $tableData = $this->query("SELECT COLUMN_NAME
                                        FROM INFORMATION_SCHEMA.COLUMNS
                                        WHERE TABLE_NAME = '{$this->escape($table)}'")
            ->fetchAll_setKey('COLUMN_NAME');

        foreach ($data as $key => $value) {
            if (array_key_exists($key, $tableData)) {
                $sql .= "`{$this->escape($key)}` = '{$this->escape($value)}',";
            }
        }
        $sql = rtrim($sql, ',');

        if (empty($sql)) {
            return false;
        }

        foreach ($where as $key => $value) {
            $whereSql .= "`{$this->escape($key)}` = '{$this->escape($value)}' AND ";
        }
        $whereSql = rtrim($whereSql, ' AND ');

        return $this->query("UPDATE $table SET $sql WHERE $whereSql")
            ->commit();
    }

    /**
     * Deletes Value From Table
     * 
     * @param (string) $table
     * @param (array) $where
     * 
     * @return (bool)
     */
    public function delete(string $table, array $where): bool
    {
        if (empty($where)) {
            return false;
        }

        $whereSql = '';
        foreach ($where as $key => $value) {
            $whereSql .= "`{$this->escape($key)}` = '{$this->escape($value)}' AND ";
        }
        $whereSql = rtrim($whereSql, ' AND ');

        return $this->query("DELETE FROM $table WHERE $whereSql")
            ->commit();
    }

    /**
     * Updates or Inserts Data
     * 
     * @param (string) $table
     * @param (array) $data
     * @param (array) $where
     * 
     * @return (bool|int)
     */
    public function updateOrInsert(string $table, array $data, array $where)
    {
        if (empty($where) || empty($data)) {
            return false;
        }

        $whereSql = '';
        foreach ($where as $key => $value) {
            $whereSql .= "`{$this->escape($key)}` = '{$this->escape($value)}' AND ";
        }
        $whereSql = rtrim($whereSql, ' AND ');

        $select = $this->query("SELECT * FROM {$this->escape($table)}
                                    WHERE $whereSql")
            ->fetch();

        if ($select) {
            return $this->update(
                $table,
                $data,
                $where
            );
        }

        return $this->insert(
            $table,
            $data + $where
        );
    }
}

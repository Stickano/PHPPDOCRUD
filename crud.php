<?php

class Crud
{
    private $conn;
    private $debug;

    public function __construct(Connection $conn)
    {
        $this->conn  = $conn;
    }

    /**
     * Call this method to return the sql and prepare values on a query.
     *
     * When activated, it will not run the query, but instead return the data
     * used, to make the query.
     *
     * @return array ['sql' => 'The SQL query string', 'prepare' => 'The prepared values']
     */
    public function debug()
    {
        $this->debug = true;
    }

    /**
     * READ from the database.
     *
     * This method will build a SQL string, and run the query.
     *
     * @param  array      $select "COLUMNS to select"           => "from TABLE"
     * @param  array|null $where  "COLUMNS to match"            => "VALUES to match"
     * @param  array|null $order  "COLUMN to order"             => "ASC|DESC"
     * @param  array|null $limit  "LIMIT value"                 => "OFFSET value"
     * @param  array|null $join   "LEFT|RIGHT|INNER|CROSS|FULL" => "JOIN COLUMN"
     * @return PDO->fetchAll()    The return from the database.
     */
    public function read(array $select,
                         array $where = null,
                         array $order = null,
                         array $limit = null,
                         array $join  = null)
    {

        # Make sure a table is defined
        if (empty($select))
        {
            throw new Exception("Empty SELECT array!");
        }

        $prepare = [];

        # Builde the SQL query string
        $select = 'SELECT '.key($select).' FROM '.$select[key($select)];
        $where  = $where ? self::where($where, $prepare) : null;
        $join   = $join  ? self::join($join, $prepare)   : null;
        $order  = $order ? self::order($order, $prepare) : null;
        $limit  = $limit ? self::limit($limit)           : null;

        # The QS should be build by now.
        # Run the Query.
        $sql = $select . $where . $join . $order . $limit;

        if ($this->debug)
        {
            return ['sql' => $sql, 'prepare' => $prepare];
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($prepare);

        return $stmt->fetchAll();
    }

    /**
     * Create a row in the database.
     *
     * This method will build the SQL string, and run the query.
     *
     * @param  string $table Which table to place the data
     * @param  array  $data  ['row' => 'data']
     * @return int           Rows affected (1=OK, 0=Something went wrong)
     */
    public function create (string $table, array $data)
    {
        if (empty($table))
        {
            throw new Exception('Empty TABLE value!');
        }

        $rows    = '';
        $values  = '';
        $prepare = [];

        $br = 0;
        foreach ($data as $key => $value)
        {
            $guid           = uniqid();
            $prepare[$guid] = $value;

            $rows          .= $key;
            $values        .= ":$guid";

            $br++;
            if (count($data) > $br)
            {
                $rows   .= ', ';
                $values .= ', ';
            }
        }

        $sql = "INSERT INTO $table ($rows) VALUES ($values)";

        if ($this->debug)
        {
            return ['sql' => $sql, 'prepare' => $prepare];
        }

        return $this->conn->prepare($sql)->execute($prepare);
    }

    /**
     * Update a row in the database.
     *
     * This method will build the SQL query string, and run it.
     *
     * @param  string      $table The table name to update in
     * @param  array       $data  ['column' => 'data']
     * @param  array|null  $where ['column' => 'value']
     * @return int         Rows affected (1=OK, 0=Error)
     */
    public function update(string $table, array $data, array $where = null)
    {
        if (empty($table))
        {
            throw new Exception('Empty TABLE value!');
        }

        if (empty($data))
        {
            throw new Exception('Empty DATA array!');
        }

        $prepare = [];

        $sql = "UPDATE $table SET";

        $br = 0;
        foreach ($data as $key => $value) {
            if ($br !== 0)
            {
                $sql .= ',';
            }

            $br++;
            $guid           = uniqid();
            $prepare[$guid] = $value;
            $sql           .= " $key = :$guid";
        }

        $where = $where ? self::where($where, $prepare) : null;
        $sql  .= $where;

        if ($this->debug)
        {
            return ['sql' => $sql, 'prepare' => $prepare];
        }

        return $this->conn->prepare($sql)->execute($prepare);
    }

    /**
     * Delete a record from the database.
     * @param  string     $table The table to delete from
     * @param  array|null $where ['column' => 'value']
     * @return int               Rows affected (1=OK, 0=Error)
     */
    public function delete(string $table, array $where = null)
    {
        $prepare = [];
        $where   = $where ? self::where($where, $prepare) : null;
        $sql     = "DELETE FROM $table" . $where;

        if ($this->debug)
        {
            return ['sql' => $sql, 'prepare' => $prepare];
        }

        return $this->conn->prepare($sql)->execute($prepare);
    }

    /**
     * If the CRUD methods in this class doesn't cover your query,
     * then you can use this method to run a loose query.
     *
     * Build the SQL like: "SELECT * FROM table WHERE id = ?"
     *
     * And to replace the '?' (prepared statement) pass along an array
     * with the values: [$value]
     *
     * Several prepared parameters are allowed. Just make sure, that the position
     * of the values in the prepare array, matches the position in the SQL string.
     *
     * @param  string     $sql     The SQL string to query
     * @param  array|null $prepare The values to prepare
     * @param  bool                If you're expecting a return, activate this param.
     * @return array|int           The return from the database | Rows affected (1=OK, 0=Error)
     */
    public function query (string $sql, array $prepare = null, bool $fetch = false)
    {
        if ($this->debug)
        {
            return ['sql' => $sql, 'prepare' => $prepare];
        }

        if ($prepare !== null)
        {
            $stmt = $this->conn->prepare($sql)->execute($prepare);
        }else{
            $stmt = $this->conn->query($sql);
        }

        if (!$fetch)
        {
            return $stmt;
        }

        return $stmt->fetchAll();
    }

    /**
     * Used to build the WHERE clause in a SQL query string.
     *
     * Used by the other CRUD methods. It will put together the
     * WHERE clause of an SQL string.
     *
     * @param  array  $where    ['column' => 'value']
     * @param  array  &$prepare The prepare array (reference)
     * @return string           The build SQL WHERE clause
     */
    private function where(array $where, array &$prepare)
    {
        if (empty($where))
        {
            return;
        }

        $placeholder = ' WHERE ';

        $br = 0;
        foreach ($where as $key => $value) {
            if ($br !== 0)
            {
                $placeholder .= ' AND ';
            }

            $br++;

            $guid           = uniqid();
            $placeholder   .= "$key = :$guid";
            $prepare[$guid] = $value;
        }

        return $placeholder;
    }

    /**
     * Used to build the JOIN clause for a SQL query string.
     *
     * This is used by the CRUD methods to build a JOIN clause, for the SQL query string.
     *
     * @param  array  $join     ['left|right etc' => 'table', ['ON table' => 'ON value']]
     * @param  array  &$prepare Prepare array (reference)
     * @return string           The SQL JOIN clause
     */
    private function join(array $join, array &$prepare)
    {
        if ($join === null)
        {
            return;
        }

        $joinClauses = ['CROSS', 'INNER', 'LEFT', 'RIGHT', 'FULL'];
        $placeholder = "";

        foreach ($join as $key => $value) {
            if (!is_array($value))
            {
                $key = strtoupper($key);
                if (!in_array($key, $joinClauses))
                {
                    throw new Exception('JOIN key can be one of the following values: cross, inner, left, right or full!');
                }

                $placeholder   .= ' ' . $key . ' JOIN ' . $value . ' ON ';
            }else
            {
                $br = 0;
                foreach ($value as $on => $onValue) {
                    if ($br !== 0)
                    {
                        $placeholder .= ' AND ';
                    }

                    # TODO: This doesn't seem to work.
                    # Am I not able to prepare the onValue?
                    # Might be an option for PDO. Need further work.
                    // $br++;
                    // $guid           = uniqid();
                    // $prepare[$guid] = $onValue;
                    // $placeholder   .= "$on = :$guid";

                    $br++;
                    $placeholder .= "$on = $onValue";
                }
            }
        }

        return $placeholder;
    }

    /**
     * Used to build the ORDER clause.
     *
     * Used by the CRUD methods to build the SQL ORDER clause.
     *
     * @param  array  $order    ['column' => 'ASC|DESC']
     * @param  array  &$prepare Prepare values (reference)
     * @return string           The ORDER SQL string
     */
    private function order(array $order, array &$prepare)
    {
        if ($order === null)
        {
            return;
        }

        $placeholder = ' ORDER BY ';

        $br = 0;
        foreach ($order as $key => $value) {
            $value = strtoupper($value);
            if ($value !== 'ASC' && $value !== 'DESC')
            {
                throw new Exception('ORDER value should be either ASC or DESC!');
            }

            if ($br !== 0)
            {
                $placeholder .= ', ';
            }

            $placeholder .= key($order) . ' ' . $value;
        }

        return $placeholder;
    }

    /**
     * Build the LIMIT and OFFSET clause for a SQL string.
     *
     * This is used by the CRUD methods to build the LIMIT & OFFSET SQL string.
     *
     * @param  array  $limit [LIMIT => OFFSET] (numeric)
     * @return string        The LIMIT/OFFSET SQL string
     */
    private function limit(array $limit)
    {
        if ($limit === null)
        {
            return;
        }

        if (array_keys($limit) !== range(0, count($limit) - 1)){
            throw new Exception('LIMIT array requires only numeric keys, with no values!');
        }

        if (min($limit) < 1)
        {
            throw new Exception('LIMIT values must be positive!');
        }

        $placeholder = "";

        if (count($limit) > 1)
        {
            $placeholder .= " OFFSET " . $limit[1];
        }

        $placeholder .= " LIMIT $limit[0]";

        return $placeholder;
    }
}

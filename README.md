# PDO CRUD
This model will handle your typical database communication using PDO.

The methods in this model typically takes an array of 'column' => 'value', making it possible to pass along several clauses to an query.

I.e. it is possible to define several WHERE, ORDER & JOIN clauses in the same array.


## Connection & Credentials
This model requires a _Connection_ model - It is basically just an initialized PDO model, with a working connection to the database.

The _Connection_ model takes a _Credentials_ model - Which is the one and only place, where the sensitive information for connecting to the database is stored (username, password, host, database).


## Prepared Statments
This model will prepare the values, before performing the query.

**BE AWARE THOUGH!**
Not everything passed to this model, will be escaped.

As a rule of thumb only the VALUES are being escaped, and **NOT** the COLUMN(s)!

In the following examples (Create, Read, Update & Delete) all the example-values marked with a start (*) will be escaped.

#### Column & Value Explained
$sql = "INSERT INTO (col1, col2) VALUES ('value1', 'value2')";

                       ^     ^              ^          ^
                     NOT ESCAPED              ESCAPED


## CREATE
To create a new record in the database.

**This method requires 2 parameters:** $table & $data

#### Typical SQL
```
$sql = "INSERT INTO table (column) VALUES ('value')";
```

#### CRUD
```
$table = 'table';
$data  = ['column' => 'value*'];

$crud->create($table, $data);
```

##### Insert into several columns
As with pretty much every other method in this class, you can pass several values to the $data array, and the method will build the query for you.

```
$table = 'table';
$data  = [
    'column1' => 'value1*',
    'column2' => 'value2*'
];

$crud->create($table, $data);
```


## READ
Read one, or more, record(s) from the database.

**This method requires 1 parameter:** $select
**And has 4 optional parameters:** $where, $order, $limit & $join

#### Typical SQL
```
$sql = "SELECT column FROM table WHERE column=value ORDER BY column DESC OFFSET 10, LIMIT 5";
```

#### CRUD
```
$select = ['column' => 'table'];
$where  = ['column' => 'value*'];
$order  = ['column' => 'ASC|DESC'];

$crud->read($select, $where, $order)
```

##### Limit & Offset
A 4th parameter can be passed - Limit & Offset.

The first value in the array is the LIMIT, the second value in the array is the OFFSET. Both values must be a positive numeric.

```
$limit = [5, 10];

$crud->read($select, $where, $order, $limit);
```

##### Joins
The 5th parameter is for building JOIN clauses.

**BE AWARE!** That the JOIN and ON clauses are currently not being escaped.

A typical JOIN query could look something like this:
```
$sql = "SELECT table.column, table2.column FROM table LEFT JOIN table2 ON table.column = table2.column";
```

With that in mind, the JOIN array is a little special. First, define the ON clause, then define which JOIN (LEFT, RIGHT etc) to which table.
```
$onClause = ['table.column' => 'table2.column'];
$join     = ['LEFT|RIGHT|INNER|CROSS|FULL' => 'table2', $onClause];

$crud->read($select, null, null, null, $join);
```

You are able to define several JOIN & ON clauses.


## UPDATE
Update a record in the database.

**This method requires 2 parameter:** $table & $data
**And has 1 optional parameters:** $where

#### Typical SQL
```
$sql = "UPDATE table SET column=value WHERE column=value";
```

#### CRUD
```
$table = 'table';
$data  = ['column' => 'value*'];
$where = ['column' => 'value*'];

$crud->update($table, $data, $where);
```

You are able to pass along several $data & $where clauses.


## DELETE
Delete a record from the database.

**This method requires 1 parameter:** $table
**And has 1 optional parameters:** $where

#### Typical SQL
```
$sql = "DELETE FROM table WHERE column=value";
```

#### CRUD
```
$table = 'table';
$where = ['column' => 'value*'];

$crud->delete($table, $where);
```

You are able to pass along several $where clauses.


## Query
If you have a query that doesn't quite fit into one of the above CRUD methods, a method for performing PDO queries is also available.

It will take a SQL string and execute it against the database. It is possible to use prepared statements with this method as well!

**This method requires 1 parameter:** $sql
**And has 2 optional parameters:** $prepare & $fetch

#### SQL Example
An example could i.e. be the use of the COUNT function (Though, this could be done with the READ method).
```
$sql = "SELECT COUNT(*) FROM table WHERE column=value";
```

#### CRUD
We will be using the $prepare parameter to escape out the WHERE clause.
The $prepare parameter is optional though, but a great responsibility follows when omitting it!
```
$sql     = "SELECT COUNT(*) FROM table WHERE column = ? AND column2 = ?";
$prepare = ['value*', 'value2*'];

$crud->query($sql, $prepare);
```

The order of the question-marks (?) in the $sql string will also be the order from the $prepare array, in which they will be replaced.

##### Fetch
If you're expecting a return from your query, a 3th parameter should be set to 'true'. It is not every query that will return a value, so it has to be actively set when needed.
```
$data = $crud->query($sql, $prepare, true);

foreach ($data as $key) {
    //
}
```



## Debug
This model also holds a 'debug' method. If you're in doubt how you SQL string is looking, call the method before making the query.

This will stop the model from making the query, and instead return an array with the SQL string and the prepared values.
```
$select = ['*' => 'users'];
$where  = ['uid' => 1];
$order  = ['fname' => 'desc'];
$limit  = [1, 5];

$crud->debug();
var_dump($crud->read($select, $where, $order, $limit));
```

If the `debug()` method is called before the query (`read()` i.e.), then the following will be output, instead of running the query:
```
array (size=2)
  'sql' => string 'SELECT * FROM users WHERE uid = :5ceb1abac27f4 ORDER BY fname DESC OFFSET 5 LIMIT 1' (length=83)
  'prepare' =>
    array (size=1)
      '5ceb1abac27f4' => int 1
```

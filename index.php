<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PDO CRUD PoC</title>
</head>
<body>
<?php
    require_once('credencials.php');
    require_once('connection.php');
    require_once('crud.php');

    $conn = new Connection(new Credencials);
    $crud = new Crud($conn);


    # General read example
    $select = ['*' => 'users'];
    $where  = ['uid' => 2];
    $order  = ['fname' => 'desc'];
    $limit  = [1, 5];
    #$crud->debug();
    var_dump($crud->read($select, $where, $order, $limit));



?>
</body>
</html>

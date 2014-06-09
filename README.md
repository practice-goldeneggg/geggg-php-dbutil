# Usage

```php
// instanciate
$pdo = new PDODbAccess();

// connect
$params = array('dbms' => 'mysql', 'host' => '127.0.0.1', 'port' => 13406, 'dbname' => 'test', 'user' => '[user]', 'password' => '[pass]');
try {
    $pdo->connect($params);
} catch(Exception $e) {
    trigger_error($e->getMessage());
}

// execute query
$sql = 'select * from test_table where create_date between :from and :to';
$binds = array('from' => '2013-05-08 00:00:00', 'to' => '2013-05-08 23:59:59');
$r = null;
try {
    // result is array
    $r = $pdo->select($sql, $binds);
} catch(Exception $e) {
    trigger_error($e->getMessage());
}

// execute query, get single record
$sql = 'select * from test_table where pk_column = :pk_column';
$binds = array('pk_column' => '[pk value]');
$r = null;
try {
    // result is assoc array or null
    $r = $pdo->select($sql, $binds, true);
} catch(Exception $e) {
    trigger_error($e->getMessage());
}

// execute update with transaction
$sql = 'insert into hoge (name, desc, ctime, mtime) values (:name, :desc, now(), now())';
$binds = array('name' => 'my name', 'desc' => 'hoge desc');
$r = null;
$id = null;
try {
    $pdo->beginTransaction();
    $r = $pdo->update($sql, $binds);
    $id = $pdo->getLastInsertId(); // for INSERT sql
    $pdo->commit();
} catch(Exception $e) {
    trigger_error($e->getMessage());
    $pdo->rollback();
}

// close
$pdo->close();
```

<?php

set_time_limit(0);

include_once('vendor/autoload.php');

$dbRead     = new MysqliDb(array(
    'host'      => '<DB_HOST>',
    'username'  => '<DB_USER>',
    'password'  => '<DB_PASS>',
    'db'        => '<DB_SCHEMA>',
    'charset'   => 'latin1'
));
$dbWrite    = new MysqliDb(array(
    'host'      => '<DB_HOST>',
    'username'  => '<DB_USER>',
    'password'  => '<DB_PASS>',
    'db'        => '<DB_SCHEMA>',
    'charset'   => 'utf8'
));

die("Replace values <FIELD_TO_TRANSLATE> and <TABLE_TO_TRANSLATE> and set db connection config before execute the script\n");

$dbRead->connect();
$dbWrite->connect();

$readSql = <<<SQL
SELECT
  id,
  <FIELD_TO_TRANSLATE>
FROM
  <TABLE_TO_TRANSLATE>
WHERE
  LENGTH(<FIELD_TO_TRANSLATE>) != CHAR_LENGTH(<FIELD_TO_TRANSLATE>)
LIMIT ?, ?
SQL;

$writeSql = <<<SQL
UPDATE
  <TABLE_TO_TRANSLATE>
SET
  <FIELD_TO_TRANSLATE> = UNHEX(?)
WHERE
  id = ?
SQL;

$i      = 0;
$window = 1000;

while(1) {
    $items = $dbRead->rawQuery($readSql, array($i, $i + $window));
    if (empty($items)) {
        break;
    }

    foreach ($items as $item) {
        $dbWrite->rawQuery($writeSql, array(
            bin2hex($item['<FIELD_TO_TRANSLATE>']),
            $city['id']
        ));
    }

    $i += $window;
};

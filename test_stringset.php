<?php

require 'vendor/autoload.php';

$nodes = ['localhost:9042'];

$database = new Cassandra\Database($nodes, 'test');
$database->connect();

$database->query("DROP TABLE IF EXISTS test_stringset");
$database->query("CREATE TABLE test_stringset (id int primary key, testset set<varchar>)");
$database->query("INSERT INTO test_stringset(id, testset) VALUES(1, {'aa','bb','cc'})");

$testset = $database->query('SELECT * FROM test_stringset WHERE "id" = :id', ['id' => 1]);

if(is_array($testset) && isset($testset[0])) {
  var_dump($testset[0]['testset']);
}

exit(0);
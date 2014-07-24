<?php

require 'vendor/autoload.php';

$nodes = ['localhost:9042'];

$database = new Cassandra\Database($nodes, 'test');
$database->connect();

$database->query("DROP TABLE IF EXISTS test_intset");
$database->query("CREATE TABLE test_intset (id int primary key, testset set<int>)");
$database->query("INSERT INTO test_intset(id, testset) VALUES(1, {10,20,30})");

$testset = $database->query('SELECT * FROM test_intset WHERE "id" = :id', ['id' => 1]);


if(is_array($testset) && isset($testset[0])) {
  var_dump($testset[0]['testset']);
}

exit(0);
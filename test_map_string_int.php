<?php

require 'vendor/autoload.php';

$nodes = ['localhost:9042'];

$database = new Cassandra\Database($nodes, 'test');
$database->connect();

$database->query("DROP TABLE IF EXISTS test_map_string_int");
$database->query("CREATE TABLE test_map_string_int (id int primary key, testmap map<varchar, int>)");
$database->query("INSERT INTO test_map_string_int(id, testmap) VALUES(1, {'aa':10,'bb':20, 'cc':30})");

$testset = $database->query('SELECT * FROM test_map_string_int WHERE "id" = :id', ['id' => 1]);

if(is_array($testset) && isset($testset[0])) {
  var_dump($testset[0]['testmap']);
}

exit(0);
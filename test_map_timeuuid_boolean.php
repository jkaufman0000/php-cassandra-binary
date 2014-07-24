<?php

require 'vendor/autoload.php';

$nodes = ['localhost:9042'];

$database = new Cassandra\Database($nodes, 'test');
$database->connect();

$database->query("DROP TABLE IF EXISTS test_map_timeuuid_boolean");
$database->query("CREATE TABLE test_map_timeuuid_boolean (id int primary key, testmap map<timeuuid, boolean>)");
$database->query("INSERT INTO test_map_timeuuid_boolean(id, testmap) VALUES(1, {7716ad80-1328-11e4-bba0-b2227cce2b54: true, 975f2c34-1328-11e4-bba0-b2227cce2b54: false})");

$testset = $database->query('SELECT * FROM test_map_timeuuid_boolean WHERE "id" = :id', ['id' => 1]);

if(is_array($testset) && isset($testset[0])) {
  var_dump($testset[0]['testmap']);
}

exit(0);
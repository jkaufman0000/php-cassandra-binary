<?php

require 'vendor/autoload.php';

$nodes = ['localhost:9042'];

$database = new Cassandra\Database($nodes, 'test');
$database->connect();

$database->query("DROP TABLE IF EXISTS test_map_uuid_string");
$database->query("CREATE TABLE test_map_uuid_string (id int primary key, testmap map<uuid, varchar>)");
$database->query("INSERT INTO test_map_uuid_string(id, testmap) VALUES(1, {aec21964-3513-46ab-9507-54da143299e3: 'aa', aafe13ac-6825-4b13-8c39-3ff8e05bca0c: 'bb'})");

$testset = $database->query('SELECT * FROM test_map_uuid_string WHERE "id" = :id', ['id' => 1]);

if(is_array($testset) && isset($testset[0])) {
  var_dump($testset[0]['testmap']);
}

exit(0);
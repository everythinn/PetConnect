<?php
$db = new PDO('mysql:host=127.0.0.1;port=3306;dbname=symfony_app', 'app', '!ChangeMe!');
$stmt = $db->query("SELECT id, owner_id, name FROM pet");
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($pets);

$stmt2 = $db->query("SELECT id, username FROM user");
$users = $stmt2->fetchAll(PDO::FETCH_ASSOC);
print_r($users);

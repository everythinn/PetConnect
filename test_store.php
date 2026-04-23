<?php
$db = new PDO('mysql:host=127.0.0.1;port=3306;dbname=symfony_app', 'app', '!ChangeMe!');
$stmt = $db->query("SELECT id, name, type, effect_value, description FROM item");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($items);

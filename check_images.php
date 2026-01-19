<?php
require 'config/database.php';
$stmt = $pdo->query("SELECT name, image FROM menu_items WHERE name LIKE '%Dimsum%'");
$items = $stmt->fetchAll();
foreach ($items as $item) {
    echo "Item: " . $item['name'] . " | Image: " . $item['image'] . "\n";
}

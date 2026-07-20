<?php
require 'config.php';

$sql = file_get_contents('database.sql');

try {
    $db->exec($sql);
    echo "Import Data Thanh Cong!\n";
} catch (Exception $e) {
    echo "Loi: " . $e->getMessage() . "\n";
}

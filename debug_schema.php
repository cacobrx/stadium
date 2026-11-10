<?php
require_once __DIR__ . '/config/database.php';
$mysqli = get_database_connection();
$res = $mysqli->query("DESCRIBE pisos_deportivos_tipos");
while($row = $res->fetch_assoc()) {
    print_r($row);
}

<?php
$servername = "188.12.186.162";
$username = "biasi";
$password = "biasi";
$dbname = "ristorante";

$conn = new MySQLi($servername, $username, $password, $dbname);


$dayOfWeek = ["Dom", "Lun", "Mar", "Mer", "Gio", "Ven", "Sab"];
$mountOfYear = ["Null", "Gen", "Feb", "Mar", "Apr", "Mag", "Giu", "Lug", "Ago", "Set", "Ott", "Nov", "Dic"];

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function fetchAssocStatement($stmt)
{
    if ($stmt->num_rows > 0) {
        $result = array();
        $md = $stmt->result_metadata();
        $params = array();
        while ($field = $md->fetch_field()) {
            $params[] = &$result[$field->name];
        }
        call_user_func_array(array($stmt, 'bind_result'), $params);
        if ($stmt->fetch()) return $result;
    }
    return null;
}


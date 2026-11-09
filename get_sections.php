<?php
header('Content-Type: application/json');
include_once(__DIR__ . '/includes/dbconnection.php');

if (!isset($_POST['class_name']) || empty($_POST['class_name'])) {
    echo json_encode([]);
    exit;
}

if (!isset($conn) || $conn->connect_error) {
    echo json_encode([]);
    exit;
}

$class_name = $_POST['class_name'];

$sql = "SELECT DISTINCT Section FROM tblclass WHERE ClassName = ? ORDER BY Section";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $class_name);
$stmt->execute();
$result = $stmt->get_result();

$sections = [];
while ($row = $result->fetch_assoc()) {
    $sections[] = $row;
}

if (empty($sections)) {
    $sections[] = ["Section" => "No Section Found"];
}

echo json_encode($sections);
$stmt->close();
?>
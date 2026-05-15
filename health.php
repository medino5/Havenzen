<?php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'service' => 'havenzen',
    'time' => gmdate('c'),
]);
?>

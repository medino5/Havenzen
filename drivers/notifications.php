<?php
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');

$driver_id = $_SESSION['user_id'] ?? 0;
if (!$driver_id) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// mark read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_read' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        if ($stmt) { $stmt->bind_param('ii', $id, $driver_id); $stmt->execute(); $stmt->close(); }
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'mark_all') {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        if ($stmt) { $stmt->bind_param('i', $driver_id); $stmt->execute(); $stmt->close(); }
        echo json_encode(['success' => true]);
        exit;
    }
    echo json_encode(['success' => false]);
    exit;
}

// GET: return latest notifications
$res = $conn->query("SELECT id, type, message, data, is_read, created_at FROM notifications WHERE user_id = $driver_id ORDER BY created_at DESC LIMIT 20");
$out = [];
while ($row = $res->fetch_assoc()) {
    $row['data'] = $row['data'] ? json_decode($row['data'], true) : null;
    $out[] = $row;
}
echo json_encode(['success' => true, 'notifications' => $out]);
exit;

?>

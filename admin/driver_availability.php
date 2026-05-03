<?php
require_once 'auth.php';
$page_title = "Driver Availability";
require_once 'header.php';

$driver_user_id = intval($_GET['driver_id'] ?? 0);

$days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

if ($driver_user_id <= 0) {
    echo '<p>Invalid driver ID.</p>';
    require_once 'footer.php';
    exit;
}

// Get basic driver info
$driver_stmt = $conn->prepare("SELECT u.user_id, u.username, d.full_name FROM users u JOIN drivers d ON d.user_id = u.user_id WHERE u.user_id = ?");
$driver_stmt->bind_param('i', $driver_user_id);
$driver_stmt->execute();
$driver_result = $driver_stmt->get_result();
$driver_info = $driver_result->fetch_assoc();
$driver_stmt->close();

if (!$driver_info) {
    echo '<p>Driver not found.</p>';
    require_once 'footer.php';
    exit;
}

// Load availability
$availability = [];
foreach ($days as $day) {
    $availability[$day] = [
        'available_from' => '',
        'available_to' => '',
        'is_active' => 0,
    ];
}

$avail_stmt = $conn->prepare("SELECT day_of_week, available_from, available_to, is_active FROM driver_availability WHERE driver_id = ?");
$avail_stmt->bind_param('i', $driver_user_id);
$avail_stmt->execute();
$avail_result = $avail_stmt->get_result();
while ($row = $avail_result->fetch_assoc()) {
    $day = $row['day_of_week'];
    if (isset($availability[$day])) {
        $availability[$day]['available_from'] = substr($row['available_from'], 0, 5);
        $availability[$day]['available_to'] = substr($row['available_to'], 0, 5);
        $availability[$day]['is_active'] = (int)$row['is_active'];
    }
}
$avail_stmt->close();
?>

<div class="section-header">
    <h2>Driver Availability: <?php echo htmlspecialchars($driver_info['full_name'] ?: $driver_info['username']); ?></h2>
    <div class="section-actions">
        <a href="drivers.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Drivers</a>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Day of Week</th>
                <th>Available From</th>
                <th>Available To</th>
                <th>Active</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($days as $day): 
                $data = $availability[$day];
            ?>
                <tr>
                    <td><?php echo ucfirst($day); ?></td>
                    <td><?php echo $data['available_from'] ? htmlspecialchars($data['available_from']) : '-'; ?></td>
                    <td><?php echo $data['available_to'] ? htmlspecialchars($data['available_to']) : '-'; ?></td>
                    <td><?php echo $data['is_active'] ? 'Yes' : 'No'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'footer.php'; ?>

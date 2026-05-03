<?php
require_once 'auth.php';
require_once 'header.php';

$driver_id = $_SESSION['user_id'] ?? 0;

$days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

// Initialize availability array
$availability = [];
foreach ($days as $day) {
    $availability[$day] = [
        'available_from' => '',
        'available_to' => '',
        'is_active' => 0,
    ];
}

// Load existing availability
if ($driver_id) {
    if ($stmt = $conn->prepare("SELECT day_of_week, available_from, available_to, is_active FROM driver_availability WHERE driver_id = ?")) {
        $stmt->bind_param('i', $driver_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $day = $row['day_of_week'];
            if (isset($availability[$day])) {
                $availability[$day]['available_from'] = substr($row['available_from'], 0, 5); // HH:MM
                $availability[$day]['available_to'] = substr($row['available_to'], 0, 5);
                $availability[$day]['is_active'] = (int)$row['is_active'];
            }
        }
        $stmt->close();
    }
}

$success_message = null;
$error_message = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_availability'])) {
    if ($driver_id) {
        // Clear existing records for this driver
        if ($del = $conn->prepare("DELETE FROM driver_availability WHERE driver_id = ?")) {
            $del->bind_param('i', $driver_id);
            $del->execute();
            $del->close();
        }

        $insert = $conn->prepare("INSERT INTO driver_availability (driver_id, available_from, available_to, day_of_week, is_active) VALUES (?, ?, ?, ?, ?)");
        if ($insert) {
            foreach ($days as $day) {
                $from_key = 'available_from_' . $day;
                $to_key = 'available_to_' . $day;
                $active_key = 'is_active_' . $day;

                $available_from = $_POST[$from_key] ?? '';
                $available_to = $_POST[$to_key] ?? '';
                $is_active = isset($_POST[$active_key]) ? 1 : 0;

                // Only insert if marked active and both times are provided
                if ($is_active && $available_from !== '' && $available_to !== '') {
                    $insert->bind_param('isssi', $driver_id, $available_from, $available_to, $day, $is_active);
                    $insert->execute();

                    // Update local array for display
                    $availability[$day]['available_from'] = $available_from;
                    $availability[$day]['available_to'] = $available_to;
                    $availability[$day]['is_active'] = 1;
                } else {
                    $availability[$day]['available_from'] = $available_from;
                    $availability[$day]['available_to'] = $available_to;
                    $availability[$day]['is_active'] = $is_active;
                }
            }
            $insert->close();
            $success_message = 'Availability updated successfully.';
            
            // Log availability update
            logCRUD($conn, $driver_id, 'UPDATE', 'driver_availability', $driver_id, 'Updated weekly availability schedule');
        } else {
            $error_message = 'Failed to save availability.';
        }
    } else {
        $error_message = 'Driver not found in session.';
    }
}
?>

<div class="dashboard-header">
    <h1>My Availability</h1>
    <p>Set the days and times when you are available to receive bookings.</p>
</div>

<?php if ($success_message): ?>
    <div class="notification success">
        <div class="notification-content">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
        <button class="notification-close" onclick="this.parentElement.style.display='none';">&times;</button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="notification error">
        <div class="notification-content">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
        <button class="notification-close" onclick="this.parentElement.style.display='none';">&times;</button>
    </div>
<?php endif; ?>

<div class="section-header">
    <h2>Weekly Schedule</h2>
    <div class="section-actions">
        <small>Times are in 24-hour format.</small>
    </div>
</div>

<form method="POST" class="availability-form">
    <input type="hidden" name="save_availability" value="1">
    <div class="availability-grid">
        <div class="availability-header-row">
            <div>Day</div>
            <div>From</div>
            <div>To</div>
            <div>Active</div>
        </div>
        <?php foreach ($days as $day): 
            $label = ucfirst($day);
            $data = $availability[$day];
        ?>
            <div class="availability-row">
                <div class="availability-day"><?php echo $label; ?></div>
                <div class="availability-field">
                    <input type="time" name="available_from_<?php echo $day; ?>" value="<?php echo htmlspecialchars($data['available_from']); ?>">
                </div>
                <div class="availability-field">
                    <input type="time" name="available_to_<?php echo $day; ?>" value="<?php echo htmlspecialchars($data['available_to']); ?>">
                </div>
                <div class="availability-field availability-toggle">
                    <label class="toggle-label">
                        <input type="checkbox" name="is_active_<?php echo $day; ?>" <?php echo $data['is_active'] ? 'checked' : ''; ?>>
                        <span>Available</span>
                    </label>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div style="margin-top: 1.5rem;">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Availability
        </button>
    </div>
</form>

<?php require_once 'footer.php'; ?>

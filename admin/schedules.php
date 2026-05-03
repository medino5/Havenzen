<?php
require_once 'auth.php';
require_once '../lib/trip_helpers.php';

$page_title = 'Trip Schedules';
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_date = date('Y-m-d', strtotime($selected_date));

function hz_schedule_days_from_post()
{
    return hz_encode_active_days($_POST['active_days'] ?? []);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_schedule') {
        $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
        $route_id = intval($_POST['route_id'] ?? 0);
        $return_route_id = $_POST['return_route_id'] === '' ? null : intval($_POST['return_route_id'] ?? 0);
        $departure_time = $_POST['departure_time'] ?? '';
        $layover_minutes = max(0, intval($_POST['layover_minutes'] ?? 0));
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $active_days = hz_schedule_days_from_post();

        $stmt = $conn->prepare("
            INSERT INTO vehicle_schedules (vehicle_id, route_id, return_route_id, departure_time, active_days, layover_minutes, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param('iiissii', $vehicle_id, $route_id, $return_route_id, $departure_time, $active_days, $layover_minutes, $is_active);
            if ($stmt->execute()) {
                $message = 'Schedule added successfully.';
                logCRUD($conn, $_SESSION['user_id'] ?? null, 'CREATE', 'vehicle_schedules', $conn->insert_id, 'Added recurring schedule');
            } else {
                $error = 'Error adding schedule: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = 'Error preparing schedule insert: ' . $conn->error;
        }
    }

    if ($action === 'update_schedule') {
        $schedule_id = intval($_POST['schedule_id'] ?? 0);
        $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
        $route_id = intval($_POST['route_id'] ?? 0);
        $return_route_id = $_POST['return_route_id'] === '' ? null : intval($_POST['return_route_id'] ?? 0);
        $departure_time = $_POST['departure_time'] ?? '';
        $layover_minutes = max(0, intval($_POST['layover_minutes'] ?? 0));
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $active_days = hz_schedule_days_from_post();

        $stmt = $conn->prepare("
            UPDATE vehicle_schedules
            SET vehicle_id = ?, route_id = ?, return_route_id = ?, departure_time = ?, active_days = ?, layover_minutes = ?, is_active = ?
            WHERE schedule_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param('iiissiii', $vehicle_id, $route_id, $return_route_id, $departure_time, $active_days, $layover_minutes, $is_active, $schedule_id);
            if ($stmt->execute()) {
                $message = 'Schedule updated successfully.';
                logCRUD($conn, $_SESSION['user_id'] ?? null, 'UPDATE', 'vehicle_schedules', $schedule_id, 'Updated recurring schedule');
            } else {
                $error = 'Error updating schedule: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = 'Error preparing schedule update: ' . $conn->error;
        }
    }

    if ($action === 'delete_schedule') {
        $schedule_id = intval($_POST['schedule_id'] ?? 0);
        if ($schedule_id > 0) {
            if ($conn->query("DELETE FROM vehicle_schedules WHERE schedule_id = {$schedule_id}")) {
                $message = 'Schedule deleted successfully.';
                logCRUD($conn, $_SESSION['user_id'] ?? null, 'DELETE', 'vehicle_schedules', $schedule_id, 'Deleted recurring schedule');
            } else {
                $error = 'Error deleting schedule: ' . $conn->error;
            }
        }
    }
}

hz_generate_trips_for_date($conn, $selected_date);
hz_expire_overdue_no_shows($conn);

$vehicles = [];
$vehicleResult = $conn->query("SELECT vehicle_id, vehicle_name, seat_capacity FROM vehicles ORDER BY vehicle_name ASC");
if ($vehicleResult) {
    while ($row = $vehicleResult->fetch_assoc()) {
        $vehicles[] = $row;
    }
}

$routes = [];
$routeResult = $conn->query("SELECT route_id, route_name, travel_minutes FROM routes ORDER BY route_name ASC");
if ($routeResult) {
    while ($row = $routeResult->fetch_assoc()) {
        $routes[] = $row;
    }
}

$schedules = [];
$scheduleResult = $conn->query("
    SELECT
        vs.*,
        v.vehicle_name,
        v.seat_capacity,
        r.route_name,
        rr.route_name AS return_route_name
    FROM vehicle_schedules vs
    JOIN vehicles v ON v.vehicle_id = vs.vehicle_id
    JOIN routes r ON r.route_id = vs.route_id
    LEFT JOIN routes rr ON rr.route_id = vs.return_route_id
    ORDER BY vs.departure_time ASC, v.vehicle_name ASC
");
if ($scheduleResult) {
    while ($row = $scheduleResult->fetch_assoc()) {
        $schedules[] = $row;
    }
}

$todayTrips = [];
$tripResult = $conn->query("
    SELECT
        vt.*,
        v.vehicle_name,
        r.route_name
    FROM vehicle_trips vt
    JOIN vehicles v ON v.vehicle_id = vt.vehicle_id
    JOIN routes r ON r.route_id = vt.route_id
    WHERE DATE(vt.scheduled_departure_at) = '{$selected_date}'
    ORDER BY vt.scheduled_departure_at ASC, vt.direction ASC
");
if ($tripResult) {
    while ($row = $tripResult->fetch_assoc()) {
        $row['metrics'] = hz_get_trip_metrics($conn, intval($row['trip_id']));
        $todayTrips[] = $row;
    }
}

$reservedSeats = 0;
$availableSeats = 0;
foreach ($todayTrips as $trip) {
    $reservedSeats += intval($trip['metrics']['reserved']);
    $availableSeats += intval($trip['metrics']['available']);
}

require_once 'header.php';
?>

<?php if (isset($message)): ?>
    <div class="notification success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="notification error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="section-header">
    <h2>Recurring Trip Schedules</h2>
    <div class="section-actions">
        <form method="GET" style="display:flex; gap:10px; align-items:center;">
            <label for="date"><strong>Trip Date</strong></label>
            <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">
            <button type="submit" class="btn btn-secondary">Load Trips</button>
        </form>
    </div>
</div>

<div class="dashboard-cards" style="margin-bottom: 20px;">
    <div class="card">
        <i class="fas fa-calendar-days card-icon" aria-hidden="true"></i>
        <h3>Schedules</h3>
        <div class="number"><?php echo count($schedules); ?></div>
    </div>
    <div class="card">
        <i class="fas fa-route card-icon" aria-hidden="true"></i>
        <h3>Trips On <?php echo date('M j', strtotime($selected_date)); ?></h3>
        <div class="number"><?php echo count($todayTrips); ?></div>
    </div>
    <div class="card">
        <i class="fas fa-ticket card-icon" aria-hidden="true"></i>
        <h3>Reserved Seats</h3>
        <div class="number"><?php echo $reservedSeats; ?></div>
    </div>
    <div class="card">
        <i class="fas fa-chair card-icon" aria-hidden="true"></i>
        <h3>Available Seats</h3>
        <div class="number"><?php echo $availableSeats; ?></div>
    </div>
</div>

<div class="form-container" style="max-width:none; margin-bottom:30px;">
    <h2 style="margin-bottom:20px;">Add Schedule</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_schedule">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:15px;">
            <div class="form-group">
                <label for="vehicle_id">Vehicle</label>
                <select name="vehicle_id" id="vehicle_id" required>
                    <option value="">Select vehicle</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?php echo intval($vehicle['vehicle_id']); ?>">
                            <?php echo htmlspecialchars($vehicle['vehicle_name'] . ' (' . intval($vehicle['seat_capacity']) . ' seats)'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="route_id">Outbound Route</label>
                <select name="route_id" id="route_id" required>
                    <option value="">Select route</option>
                    <?php foreach ($routes as $route): ?>
                        <option value="<?php echo intval($route['route_id']); ?>">
                            <?php echo htmlspecialchars($route['route_name'] . ' (' . intval($route['travel_minutes']) . ' mins)'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="return_route_id">Return Route</label>
                <select name="return_route_id" id="return_route_id">
                    <option value="">Optional</option>
                    <?php foreach ($routes as $route): ?>
                        <option value="<?php echo intval($route['route_id']); ?>">
                            <?php echo htmlspecialchars($route['route_name'] . ' (' . intval($route['travel_minutes']) . ' mins)'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="departure_time">Departure Time</label>
                <input type="time" name="departure_time" id="departure_time" required>
            </div>
            <div class="form-group">
                <label for="layover_minutes">Layover Minutes</label>
                <input type="number" name="layover_minutes" id="layover_minutes" min="0" value="0" required>
            </div>
            <div class="form-group">
                <label>Active Days</label>
                <div style="display:grid; grid-template-columns: repeat(2, minmax(100px, 1fr)); gap:8px;">
                    <?php foreach (['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day): ?>
                        <label style="display:flex; align-items:center; gap:6px;">
                            <input type="checkbox" name="active_days[]" value="<?php echo $day; ?>">
                            <span><?php echo ucfirst($day); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label style="display:flex; align-items:center; gap:8px; margin-top: 28px;">
                    <input type="checkbox" name="is_active" value="1" checked>
                    <span>Schedule Active</span>
                </label>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Schedule</button>
        </div>
    </form>
</div>

<div class="table-container" style="margin-bottom:30px;">
    <div class="table-header">
        <h2>Configured Schedules</h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>Vehicle</th>
                <th>Outbound</th>
                <th>Return</th>
                <th>Departure</th>
                <th>Active Days</th>
                <th>Layover</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$schedules): ?>
                <tr><td colspan="8">No schedules configured yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($schedules as $schedule): ?>
                <?php $activeDays = hz_decode_active_days($schedule['active_days']); ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($schedule['vehicle_name']); ?></strong><br>
                        <small><?php echo intval($schedule['seat_capacity']); ?> seats</small>
                    </td>
                    <td><?php echo htmlspecialchars($schedule['route_name']); ?></td>
                    <td><?php echo $schedule['return_route_name'] ? htmlspecialchars($schedule['return_route_name']) : '<em>None</em>'; ?></td>
                    <td><?php echo date('g:i A', strtotime($schedule['departure_time'])); ?></td>
                    <td><?php echo htmlspecialchars(implode(', ', array_map('ucfirst', $activeDays))); ?></td>
                    <td><?php echo intval($schedule['layover_minutes']); ?> mins</td>
                    <td>
                        <span class="status-badge <?php echo intval($schedule['is_active']) === 1 ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo intval($schedule['is_active']) === 1 ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td>
                        <details>
                            <summary class="btn btn-secondary" style="display:inline-flex;">Edit</summary>
                            <form method="POST" style="margin-top:10px; min-width:280px;">
                                <input type="hidden" name="action" value="update_schedule">
                                <input type="hidden" name="schedule_id" value="<?php echo intval($schedule['schedule_id']); ?>">
                                <div class="form-group">
                                    <label>Vehicle</label>
                                    <select name="vehicle_id" required>
                                        <?php foreach ($vehicles as $vehicle): ?>
                                            <option value="<?php echo intval($vehicle['vehicle_id']); ?>" <?php echo intval($vehicle['vehicle_id']) === intval($schedule['vehicle_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($vehicle['vehicle_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Outbound Route</label>
                                    <select name="route_id" required>
                                        <?php foreach ($routes as $route): ?>
                                            <option value="<?php echo intval($route['route_id']); ?>" <?php echo intval($route['route_id']) === intval($schedule['route_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($route['route_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Return Route</label>
                                    <select name="return_route_id">
                                        <option value="">Optional</option>
                                        <?php foreach ($routes as $route): ?>
                                            <option value="<?php echo intval($route['route_id']); ?>" <?php echo intval($route['route_id']) === intval($schedule['return_route_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($route['route_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Departure Time</label>
                                    <input type="time" name="departure_time" value="<?php echo htmlspecialchars($schedule['departure_time']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Layover Minutes</label>
                                    <input type="number" name="layover_minutes" min="0" value="<?php echo intval($schedule['layover_minutes']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Active Days</label>
                                    <div style="display:grid; grid-template-columns: repeat(2, minmax(100px, 1fr)); gap:8px;">
                                        <?php foreach (['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day): ?>
                                            <label style="display:flex; align-items:center; gap:6px;">
                                                <input type="checkbox" name="active_days[]" value="<?php echo $day; ?>" <?php echo in_array($day, $activeDays, true) ? 'checked' : ''; ?>>
                                                <span><?php echo ucfirst($day); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label style="display:flex; align-items:center; gap:8px;">
                                        <input type="checkbox" name="is_active" value="1" <?php echo intval($schedule['is_active']) === 1 ? 'checked' : ''; ?>>
                                        <span>Schedule Active</span>
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary">Update</button>
                            </form>
                            <form method="POST" style="margin-top:10px;" onsubmit="return confirm('Delete this schedule?');">
                                <input type="hidden" name="action" value="delete_schedule">
                                <input type="hidden" name="schedule_id" value="<?php echo intval($schedule['schedule_id']); ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </details>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="table-container">
    <div class="table-header">
        <h2>Generated Trips For <?php echo date('M j, Y', strtotime($selected_date)); ?></h2>
    </div>
    <table>
        <thead>
            <tr>
                <th>Departure</th>
                <th>Vehicle</th>
                <th>Route</th>
                <th>Direction</th>
                <th>Status</th>
                <th>Capacity</th>
                <th>Reserved</th>
                <th>Boarded</th>
                <th>Available</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$todayTrips): ?>
                <tr><td colspan="9">No trips generated for this date yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($todayTrips as $trip): ?>
                <tr>
                    <td><?php echo date('M j, g:i A', strtotime($trip['scheduled_departure_at'])); ?></td>
                    <td><?php echo htmlspecialchars($trip['vehicle_name']); ?></td>
                    <td><?php echo htmlspecialchars($trip['route_name']); ?></td>
                    <td><?php echo ucfirst($trip['direction']); ?></td>
                    <td><span class="status-badge status-<?php echo htmlspecialchars($trip['trip_status']); ?>"><?php echo ucwords(str_replace('_', ' ', $trip['trip_status'])); ?></span></td>
                    <td><?php echo intval($trip['metrics']['capacity']); ?></td>
                    <td><?php echo intval($trip['metrics']['reserved']); ?></td>
                    <td><?php echo intval($trip['metrics']['boarded']); ?></td>
                    <td><?php echo intval($trip['metrics']['available']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'footer.php'; ?>

<?php
require_once 'auth.php';
$page_title = "System Logs";
require_once 'header.php';
?>

<!-- System Logs -->
<div class="table-container">
    <div class="table-header">
        <h2>System Logs</h2>
        <div class="filter-section">
            <form method="GET" class="date-filter-form">
                <div class="filter-group">
                    <label for="start_date">From:</label>
                    <input type="date" id="start_date" name="start_date" 
                           value="<?php echo $_GET['start_date'] ?? ''; ?>">
                </div>
                <div class="filter-group">
                    <label for="end_date">To:</label>
                    <input type="date" id="end_date" name="end_date" 
                           value="<?php echo $_GET['end_date'] ?? ''; ?>">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <?php if(isset($_GET['start_date']) || isset($_GET['end_date'])): ?>
                    <a href="system-logs.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <table class="logs-table">
        <thead>
            <tr>
                <th>Log ID</th>
                <th>User</th>
                <th>Action</th>
                <th>Description</th>
                <th>IP Address</th>
                <th>Date & Time</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Pagination setup
            $records_per_page = 20;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $records_per_page;
            
            // Build the base query for counting total records
            $count_query = "SELECT COUNT(*) as total 
                           FROM system_logs sl 
                           LEFT JOIN users u ON sl.user_id = u.user_id 
                           WHERE 1=1";
            
            // Build the main query with date filters
            $query = "SELECT sl.*, u.username 
                     FROM system_logs sl 
                     LEFT JOIN users u ON sl.user_id = u.user_id 
                     WHERE 1=1";
            
            $params = [];
            $types = "";
            
            if (!empty($_GET['start_date'])) {
                $query .= " AND DATE(sl.created_at) >= ?";
                $count_query .= " AND DATE(sl.created_at) >= ?";
                $params[] = $_GET['start_date'];
                $types .= "s";
            }
            
            if (!empty($_GET['end_date'])) {
                $query .= " AND DATE(sl.created_at) <= ?";
                $count_query .= " AND DATE(sl.created_at) <= ?";
                $params[] = $_GET['end_date'];
                $types .= "s";
            }
            
            // Get total count for pagination
            $count_stmt = $conn->prepare($count_query);
            if (!empty($params)) {
                $count_stmt->bind_param($types, ...$params);
            }
            $count_stmt->execute();
            $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
            $total_pages = ceil($total_records / $records_per_page);
            
            // Add pagination to main query
            $query .= " ORDER BY sl.created_at DESC LIMIT ? OFFSET ?";
            $params[] = $records_per_page;
            $params[] = $offset;
            $types .= "ii";
            
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $logs = $stmt->get_result();
            
            while ($log = $logs->fetch_assoc()):
            ?>
            <tr>
                <td><?php echo $log['log_id']; ?></td>
                <td><?php echo $log['username'] ? htmlspecialchars($log['username']) : 'System'; ?></td>
                <td><?php echo htmlspecialchars($log['action']); ?></td>
                <td><?php echo htmlspecialchars($log['description']); ?></td>
                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                <td><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination-container">
        <div class="pagination-info">
            Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> entries
        </div>
        <div class="pagination">
            <?php
            // Build query string for pagination links
            $query_params = $_GET;
            unset($query_params['page']);
            $query_string = !empty($query_params) ? '&' . http_build_query($query_params) : '';
            
            // Previous button
            if ($page > 1): ?>
                <a href="?page=<?php echo ($page - 1) . $query_string; ?>" class="pagination-btn">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif;
            
            // Page numbers
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1): ?>
                <a href="?page=1<?php echo $query_string; ?>" class="pagination-btn">1</a>
                <?php if ($start_page > 2): ?>
                    <span class="pagination-dots">...</span>
                <?php endif;
            endif;
            
            for ($i = $start_page; $i <= $end_page; $i++): ?>
                <a href="?page=<?php echo $i . $query_string; ?>" 
                   class="pagination-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor;
            
            if ($end_page < $total_pages): 
                if ($end_page < $total_pages - 1): ?>
                    <span class="pagination-dots">...</span>
                <?php endif; ?>
                <a href="?page=<?php echo $total_pages . $query_string; ?>" class="pagination-btn"><?php echo $total_pages; ?></a>
            <?php endif;
            
            // Next button
            if ($page < $total_pages): ?>
                <a href="?page=<?php echo ($page + 1) . $query_string; ?>" class="pagination-btn">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
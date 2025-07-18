<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all maintenance requests for user's assets
$stmt = $pdo->prepare("
    SELECT mr.*, a.asset_name, a.asset_type, a.location
    FROM maintenance_requests mr
    JOIN assets a ON mr.asset_id = a.asset_id
    WHERE mr.user_id = ? OR a.assigned_to = ?
    ORDER BY 
        CASE 
            WHEN mr.status = 'in_progress' THEN 1
            WHEN mr.status = 'approved' THEN 2
            WHEN mr.status = 'pending' THEN 3
            WHEN mr.status = 'completed' THEN 4
            ELSE 5
        END,
        CASE
            WHEN mr.priority = 'critical' THEN 1
            WHEN mr.priority = 'high' THEN 2
            WHEN mr.priority = 'medium' THEN 3
            WHEN mr.priority = 'low' THEN 4
        END
");
$stmt->execute([$user_id, $user_id]);
$maintenance_requests = $stmt->fetchAll();

// Try to get scheduled maintenance if the table exists
try {
    // Check if the scheduled_maintenance table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'scheduled_maintenance'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        $stmt = $pdo->prepare("
            SELECT sm.*, a.asset_name, a.asset_type, a.location
            FROM scheduled_maintenance sm
            JOIN assets a ON sm.asset_id = a.asset_id
            WHERE a.assigned_to = ?
            ORDER BY sm.next_maintenance_date
        ");
        $stmt->execute([$user_id]);
        $scheduled_maintenance = $stmt->fetchAll();
    } else {
        // Use assets with calculated due dates instead
        throw new Exception('Table does not exist');
    }
} catch (Exception $e) {
    // Fallback: Generate scheduled maintenance from asset purchase dates
    $stmt = $pdo->prepare("
        SELECT 
            a.asset_id, 
            a.asset_name, 
            a.asset_type, 
            a.location, 
            'routine' as maintenance_type,
            DATE_ADD(a.purchase_date, INTERVAL 90 DAY) as next_maintenance_date
        FROM assets a
        WHERE a.assigned_to = ?
        ORDER BY a.purchase_date
    ");
    $stmt->execute([$user_id]);
    $scheduled_maintenance = $stmt->fetchAll();
}

// Get current month and year
$current_month = date('m');
$current_year = date('Y');
$selected_month = isset($_GET['month']) ? $_GET['month'] : $current_month;
$selected_year = isset($_GET['year']) ? $_GET['year'] : $current_year;

// Get the first day of the month
$first_day = mktime(0, 0, 0, $selected_month, 1, $selected_year);
// Get the number of days in the month
$num_days = date('t', $first_day);
// Get the first day of the week (0 = Sunday, 1 = Monday, etc.)
$first_day_of_week = date('w', $first_day);

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="mb-0"><i class="fas fa-calendar-alt me-2 text-primary"></i> Maintenance Schedule</h1>
            <p class="text-muted">View upcoming and past maintenance activities for your assets</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="maintenance-request.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i> New Maintenance Request
            </a>
            <a href="requests.php" class="btn btn-outline-primary ms-2">
                <i class="fas fa-list-alt me-2"></i> View Requests
            </a>
        </div>
    </div>

    <!-- Monthly Calendar -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0"><i class="fas fa-calendar me-2"></i> Maintenance Calendar</h5>
            <div class="btn-group">
                <?php
                $prev_month = $selected_month - 1;
                $prev_year = $selected_year;
                if ($prev_month < 1) {
                    $prev_month = 12;
                    $prev_year--;
                }
                
                $next_month = $selected_month + 1;
                $next_year = $selected_year;
                if ($next_month > 12) {
                    $next_month = 1;
                    $next_year++;
                }
                ?>
                <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <span class="btn btn-outline-primary">
                    <?php echo date('F Y', mktime(0, 0, 0, $selected_month, 1, $selected_year)); ?>
                </span>
                <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr class="text-center">
                            <th>Sunday</th>
                            <th>Monday</th>
                            <th>Tuesday</th>
                            <th>Wednesday</th>
                            <th>Thursday</th>
                            <th>Friday</th>
                            <th>Saturday</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Create an array of maintenance dates
                        $maintenance_dates = [];
                        foreach ($scheduled_maintenance as $item) {
                            $date = date('Y-m-d', strtotime($item['next_maintenance_date']));
                            if (!isset($maintenance_dates[$date])) {
                                $maintenance_dates[$date] = [];
                            }
                            $maintenance_dates[$date][] = $item;
                        }
                        
                        // Create an array of maintenance request dates
                        $request_dates = [];
                        foreach ($maintenance_requests as $request) {
                            if ($request['status'] == 'approved' || $request['status'] == 'in_progress') {
                                $date = date('Y-m-d', strtotime($request['approved_date'] ?? $request['request_date']));
                                if (!isset($request_dates[$date])) {
                                    $request_dates[$date] = [];
                                }
                                $request_dates[$date][] = $request;
                            }
                        }
                        
                        // Generate the calendar
                        $day_count = 1;
                        $calendar_rows = ceil(($num_days + $first_day_of_week) / 7);
                        
                        for ($i = 0; $i < $calendar_rows; $i++) {
                            echo '<tr class="calendar-row">';
                            
                            for ($j = 0; $j < 7; $j++) {
                                if (($i == 0 && $j < $first_day_of_week) || ($day_count > $num_days)) {
                                    // Empty cells
                                    echo '<td class="calendar-day empty"></td>';
                                } else {
                                    // Format the date
                                    $date = sprintf('%04d-%02d-%02d', $selected_year, $selected_month, $day_count);
                                    $today_class = ($date == date('Y-m-d')) ? 'today bg-light' : '';
                                    
                                    echo '<td class="calendar-day ' . $today_class . '">';
                                    echo '<div class="date-header d-flex justify-content-between">';
                                    echo '<span class="day-number">' . $day_count . '</span>';
                                    
                                    // Check if it's today
                                    if ($date == date('Y-m-d')) {
                                        echo '<span class="badge bg-primary rounded-pill">Today</span>';
                                    }
                                    
                                    echo '</div>';
                                    
                                    // Display scheduled maintenance for this day
                                    if (isset($maintenance_dates[$date])) {
                                        foreach ($maintenance_dates[$date] as $item) {
                                            echo '<div class="calendar-event maintenance-event p-1 mb-1 rounded">';
                                            echo '<small><i class="fas fa-tools me-1"></i> ' . htmlspecialchars($item['asset_name']) . '</small>';
                                            echo '</div>';
                                        }
                                    }
                                    
                                    // Display maintenance requests for this day
                                    if (isset($request_dates[$date])) {
                                        foreach ($request_dates[$date] as $request) {
                                            $status_class = '';
                                            switch ($request['status']) {
                                                case 'approved':
                                                    $status_class = 'bg-info';
                                                    break;
                                                case 'in_progress':
                                                    $status_class = 'bg-warning';
                                                    break;
                                            }
                                            
                                            echo '<div class="calendar-event request-event p-1 mb-1 rounded ' . $status_class . '">';
                                            echo '<small><i class="fas fa-wrench me-1"></i> ' . htmlspecialchars($request['asset_name']) . '</small>';
                                            echo '</div>';
                                        }
                                    }
                                    
                                    echo '</td>';
                                    $day_count++;
                                }
                            }
                            
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Upcoming Maintenance -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i> Upcoming Maintenance</h5>
                    <span class="badge bg-primary rounded-pill"><?php echo count($scheduled_maintenance); ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (count($scheduled_maintenance) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($scheduled_maintenance as $index => $item): ?>
                                <?php if ($index < 5): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($item['asset_name']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars(ucfirst($item['maintenance_type'] ?? 'Routine')); ?> Maintenance
                                                    <?php if (isset($item['location']) && !empty($item['location'])): ?>
                                                        • <?php echo htmlspecialchars($item['location']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <div>
                                                <?php
                                                $maintenance_date = strtotime($item['next_maintenance_date']);
                                                $days_until = ceil(($maintenance_date - time()) / (60 * 60 * 24));
                                                
                                                if ($days_until < 0) {
                                                    echo '<span class="badge bg-danger">Overdue</span>';
                                                } elseif ($days_until == 0) {
                                                    echo '<span class="badge bg-warning">Today</span>';
                                                } elseif ($days_until <= 7) {
                                                    echo '<span class="badge bg-warning">' . $days_until . ' days</span>';
                                                } else {
                                                    echo '<span class="badge bg-info">' . $days_until . ' days</span>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <p class="mb-1 mt-2">
                                            <small>
                                                <strong>Scheduled Date:</strong> 
                                                <?php echo date('F j, Y', $maintenance_date); ?>
                                            </small>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($scheduled_maintenance) > 5): ?>
                            <div class="card-footer bg-transparent text-center py-2">
                                <a href="#" class="btn btn-sm btn-link">View All Scheduled Maintenance</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <img src="https://img.icons8.com/color/96/000000/maintenance.png" alt="No Maintenance" class="mb-3" style="opacity: 0.5;">
                            <p class="text-muted">No upcoming maintenance scheduled.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0"><i class="fas fa-tools me-2"></i> Active Maintenance Requests</h5>
                    <span class="badge bg-primary rounded-pill">
                        <?php 
                        $active_count = 0;
                        foreach ($maintenance_requests as $request) {
                            if ($request['status'] != 'completed' && $request['status'] != 'rejected') {
                                $active_count++;
                            }
                        }
                        echo $active_count;
                        ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php 
                    $active_requests = array_filter($maintenance_requests, function($request) {
                        return $request['status'] != 'completed' && $request['status'] != 'rejected';
                    });
                    ?>
                    
                    <?php if (count($active_requests) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($active_requests as $index => $request): ?>
                                <?php if ($index < 5): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <div class="asset-icon rounded text-center p-2 bg-light" style="width: 40px; height: 40px;">
                                                        <i class="fas fa-laptop text-primary"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($request['asset_name']); ?></h6>
                                                    <small class="text-muted">
                                                        <?php echo ucfirst($request['request_type']); ?> • 
                                                        <?php echo ucfirst($request['priority']); ?> Priority
                                                    </small>
                                                </div>
                                            </div>
                                            <div>
                                                <?php 
                                                $status_badges = [
                                                    'pending' => '<span class="badge bg-warning">Pending</span>',
                                                    'approved' => '<span class="badge bg-info">Approved</span>',
                                                    'in_progress' => '<span class="badge bg-primary">In Progress</span>'
                                                ];
                                                echo $status_badges[$request['status']] ?? $request['status'];
                                                ?>
                                            </div>
                                        </div>
                                        <p class="small text-muted mt-2 mb-0">
                                            <?php echo htmlspecialchars(substr($request['description'], 0, 100)) . (strlen($request['description']) > 100 ? '...' : ''); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($active_requests) > 5): ?>
                            <div class="card-footer bg-transparent text-center py-2">
                                <a href="requests.php" class="btn btn-sm btn-link">View All Requests</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <img src="https://img.icons8.com/color/96/000000/check-all.png" alt="No Active Requests" class="mb-3" style="opacity: 0.5;">
                            <p class="text-muted">No active maintenance requests.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-day {
    height: 120px;
    vertical-align: top;
    padding: 5px;
    position: relative;
}

.calendar-day.empty {
    background-color: #f8f9fa;
}

.calendar-day .date-header {
    margin-bottom: 5px;
}

.calendar-day .day-number {
    font-weight: bold;
}

.calendar-day.today .day-number {
    color: #0d6efd;
}

.calendar-event {
    font-size: 0.75rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.maintenance-event {
    background-color: rgba(13, 110, 253, 0.1);
    border-left: 3px solid #0d6efd;
}

.request-event {
    color: white;
}
</style>

<?php require_once '../includes/footer.php'; ?>

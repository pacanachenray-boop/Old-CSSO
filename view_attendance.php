<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['usertype'], ['Secretary', 'Treasurer', 'Auditor', 'Social Manager', 'Senator', 'Governor', 'Vice Governor'])) {
    header("Location: ../login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "csso");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get event parameters from URL
$event_name = $_GET['event'] ?? '';
$event_date = $_GET['date'] ?? '';

// Get event details
$event = null;
$show_am = true;
$show_pm = true;

if (!empty($event_name) && !empty($event_date)) {
    $event_sql = "SELECT * FROM event WHERE event_Name = ? AND event_Date = ?";
    $stmt = $conn->prepare($event_sql);
    $stmt->bind_param("ss", $event_name, $event_date);
    $stmt->execute();
    $event_result = $stmt->get_result();
    $event = $event_result->fetch_assoc();
    
    if ($event) {
        // Determine what columns to show based on Time_Session
        $show_am = in_array($event['Time_Session'], ['AM Session', 'Full Day']);
        $show_pm = in_array($event['Time_Session'], ['PM Session', 'Full Day']);
    }
}

// ✅ AJAX HANDLER: Update Excuse Letter
if (isset($_POST['update_excuse'])) {
    header('Content-Type: application/json');
    
    $attendance_id = intval($_POST['attendance_id']);
    $excuse_letter = $_POST['excuse_letter'];
    
    if ($excuse_letter === 'Yes') {
        $update_sql = "UPDATE attendance SET ExcuseLetter = 'Yes', TotalPenalty = 0 WHERE attendance_id = ?";
    } else {
        $update_sql = "UPDATE attendance SET 
            ExcuseLetter = 'No',
            TotalPenalty = (
                (CASE WHEN amLogin IS NULL OR amLogin = '' THEN 50 ELSE 0 END) +
                (CASE WHEN amLogout IS NULL OR amLogout = '' THEN 50 ELSE 0 END) +
                (CASE WHEN pmLogin IS NULL OR pmLogin = '' THEN 50 ELSE 0 END) +
                (CASE WHEN pmLogout IS NULL OR pmLogout = '' THEN 50 ELSE 0 END)
            )
            WHERE attendance_id = ?";
    }
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $attendance_id);
    
    if ($stmt->execute()) {
        $get_penalty = $conn->query("SELECT TotalPenalty, ExcuseLetter FROM attendance WHERE attendance_id = $attendance_id");
        $penalty_row = $get_penalty->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'new_penalty' => $penalty_row['TotalPenalty'],
            'excuse_letter' => $penalty_row['ExcuseLetter']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    
    exit();
}

// ✅ NEW: AJAX HANDLER for Export to Fines
if (isset($_POST['export_to_fines'])) {
    header('Content-Type: application/json');
    
    $event_name_export = $_POST['event_name'] ?? '';
    $event_date_export = $_POST['event_date'] ?? '';
    
    try {
        // Get all attendance records (including 0 penalty)
        $attendance_query = "SELECT 
            a.attendance_id,
            a.UserID,
            a.students_id,
            a.registration_no,
            a.event_name,
            a.event_date,
            a.location,
            a.TotalPenalty
        FROM attendance a
        WHERE 1=1";
        
        // Filter by event if provided (follow same filter as view)
        if (!empty($event_name_export) && !empty($event_date_export)) {
            $attendance_query .= " AND a.event_name = ? AND a.event_date = ?";
            $stmt = $conn->prepare($attendance_query);
            $stmt->bind_param("ss", $event_name_export, $event_date_export);
        } else {
            $stmt = $conn->prepare($attendance_query);
        }
        
        $stmt->execute();
        $attendance_records = $stmt->get_result();
        
        $inserted_count = 0;
        $skipped_count = 0;
        $updated_count = 0;
        
        while ($record = $attendance_records->fetch_assoc()) {
            // Check if this attendance_id already exists in fines table
            $check_sql = "SELECT fines_id FROM fines WHERE attendance_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $record['attendance_id']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Already exists - UPDATE the record instead
                $update_sql = "UPDATE fines SET 
                    user_id = ?,
                    students_id = ?,
                    registration_no = ?,
                    event_name = ?,
                    event_date = ?,
                    location = ?,
                    PenaltyAmount = ?
                    WHERE attendance_id = ?";
                
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param(
                    "iissssdi",
                    $record['UserID'],
                    $record['students_id'],
                    $record['registration_no'],
                    $record['event_name'],
                    $record['event_date'],
                    $record['location'],
                    $record['TotalPenalty'],
                    $record['attendance_id']
                );
                
                if ($update_stmt->execute()) {
                    $updated_count++;
                }
                continue;
            }
            
            // Insert into fines table
            $insert_sql = "INSERT INTO fines 
                (user_id, students_id, registration_no, attendance_id, event_name, event_date, location, PenaltyAmount) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $insert_stmt = $conn->prepare($insert_sql);
            
            // Make sure all values are properly set
            $user_id = $record['UserID'];
            $students_id = $record['students_id'];
            $registration_no = $record['registration_no'];
            $attendance_id = $record['attendance_id'];
            $event_name = $record['event_name'];
            $event_date = $record['event_date'];
            $location = $record['location'];
            $penalty = $record['TotalPenalty'];
            
            $insert_stmt->bind_param(
                "iisssssd",
                $user_id,
                $students_id,
                $registration_no,
                $attendance_id,
                $event_name,
                $event_date,
                $location,
                $penalty
            );
            
            if ($insert_stmt->execute()) {
                $inserted_count++;
            }
        }
        
        $total_processed = $inserted_count + $updated_count;
        $message = [];
        
        if ($inserted_count > 0) {
            $message[] = "$inserted_count new records added";
        }
        if ($updated_count > 0) {
            $message[] = "$updated_count records updated";
        }
        
        echo json_encode([
            'success' => true,
            'inserted' => $inserted_count,
            'updated' => $updated_count,
            'total' => $total_processed,
            'message' => "Successfully exported! " . implode(", ", $message)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    
    exit();
}

// ✅ NEW: Check if records already exported
if (isset($_POST['check_export_status'])) {
    header('Content-Type: application/json');
    
    $event_name_check = $_POST['event_name'] ?? '';
    $event_date_check = $_POST['event_date'] ?? '';
    
    try {
        // Check if any attendance records for this event exist in fines table
        $check_query = "SELECT COUNT(*) as exported_count 
                       FROM fines f
                       INNER JOIN attendance a ON f.attendance_id = a.attendance_id
                       WHERE 1=1";
        
        if (!empty($event_name_check) && !empty($event_date_check)) {
            $check_query .= " AND a.event_name = ? AND a.event_date = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("ss", $event_name_check, $event_date_check);
        } else {
            $stmt = $conn->prepare($check_query);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'is_exported' => $row['exported_count'] > 0,
            'count' => $row['exported_count']
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    
    exit();
}

// Get filter parameters
$search = $_GET['search'] ?? '';

// Build SQL query
$sql = "SELECT 
    a.attendance_id,
    a.students_id,
    CONCAT(sp.LastName, ', ', sp.FirstName) as FullName,
    sp.Course,
    sp.YearLevel,
    a.event_name,
    a.event_date,
    a.amLogin,
    a.amLogout,
    a.pmLogin,
    a.pmLogout,
    a.ExcuseLetter,
    a.TotalPenalty
FROM attendance a
LEFT JOIN student_profile sp ON a.students_id = sp.students_id
WHERE 1=1";

// Filter by specific event if provided
if (!empty($event_name) && !empty($event_date)) {
    $sql .= " AND a.event_name = '" . $conn->real_escape_string($event_name) . "'";
    $sql .= " AND a.event_date = '" . $conn->real_escape_string($event_date) . "'";
}

if (!empty($search)) {
    $search_term = $conn->real_escape_string($search);
    $sql .= " AND (a.students_id LIKE '%$search_term%' 
              OR sp.LastName LIKE '%$search_term%' 
              OR sp.FirstName LIKE '%$search_term%'
              OR sp.YearLevel LIKE '%$search_term%'
              OR a.event_name LIKE '%$search_term%')";
}

$sql .= " ORDER BY sp.LastName ASC, sp.FirstName ASC";
$result = $conn->query($sql);

// Calculate statistics for this event
$stats_sql = "SELECT 
    COUNT(*) as total_records,
    COUNT(DISTINCT a.students_id) as unique_students,
    SUM(a.TotalPenalty) as total_penalties
FROM attendance a";

if (!empty($event_name) && !empty($event_date)) {
    $stats_sql .= " WHERE a.event_name = '" . $conn->real_escape_string($event_name) . "'";
    $stats_sql .= " AND a.event_date = '" . $conn->real_escape_string($event_date) . "'";
}

$stats = $conn->query($stats_sql)->fetch_assoc();

function formatYearLevel($yearLevel) {
    $mapping = [
        '1stYearLevel' => '1st Year',
        '2ndYearLevel' => '2nd Year',
        '3rdYearLevel' => '3rd Year',
        '4thYearLevel' => '4th Year',
        'AllLevels' => 'All Levels'
    ];
    return $mapping[$yearLevel] ?? $yearLevel;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $event ? htmlspecialchars($event_name) . ' - ' : '' ?>Attendance Records | CSSO</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: #f0f4f8;
  color: #1e3a5f;
  padding: 0;
}

.container {
  padding: 24px;
  max-width: 1600px;
  margin: 0 auto;
}

/* Back Button */
.back-btn {
  background: #64748b;
  color: white;
  padding: 8px 16px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
  margin-bottom: 20px;
}

.back-btn:hover {
  background: #475569;
  transform: translateY(-2px);
}

/* Event Header */
.event-header {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
  margin-bottom: 20px;
  border-left: 4px solid #0ea5e9;
}

.event-title {
  font-size: 24px;
  font-weight: 600;
  color: #1e3a5f;
  margin-bottom: 12px;
}

.event-details {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
  color: #64748b;
  font-size: 14px;
}

.event-detail {
  display: flex;
  align-items: center;
  gap: 6px;
}

.event-detail .badge {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  margin-left: 4px;
}

.badge-time {
  background: #fef3c7;
  color: #92400e;
}

.badge-year-level {
  background: #e0e7ff;
  color: #3730a3;
}

/* Header */
.page-header {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 3px solid #e0f2fe;
}

.page-header i {
  background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
  color: white;
  padding: 14px;
  border-radius: 12px;
  font-size: 22px;
  box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
}

.page-header h2 {
  font-size: 26px;
  font-weight: 600;
  color: #1e3a5f;
  letter-spacing: -0.3px;
}

/* Stats Cards */
.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border-left: 4px solid #0ea5e9;
}

.stat-card h4 {
  font-size: 13px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.stat-card p {
  font-size: 24px;
  font-weight: 700;
  color: #0ea5e9;
}

/* Controls Section */
.controls {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  margin-bottom: 20px;
}

.filters {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.filters select,
.filters input[type="text"] {
  padding: 10px 14px;
  border-radius: 8px;
  border: 2px solid #e2e8f0;
  font-size: 14px;
  outline: none;
  transition: all 0.3s ease;
  background: white;
  color: #334155;
  font-weight: 500;
}

.filters select:focus,
.filters input[type="text"]:focus {
  border-color: #0ea5e9;
  box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
}

.filters input[type="text"] {
  min-width: 250px;
}

/* Buttons */
.btn {
  padding: 10px 18px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.search-btn {
  background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(14, 165, 233, 0.3);
}

.search-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
}

.clear-btn {
  background: #64748b;
  color: white;
}

.clear-btn:hover {
  background: #475569;
  transform: translateY(-2px);
}

.export-btn {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.export-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.export-btn.already-exported {
  background: linear-gradient(135deg, #64748b 0%, #475569 100%);
  box-shadow: 0 2px 8px rgba(100, 116, 139, 0.3);
  cursor: default;
}

.export-btn.already-exported:hover {
  transform: none;
  box-shadow: 0 2px 8px rgba(100, 116, 139, 0.3);
}

/* Table Container */
.table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.table-scroll {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1000px;
}

thead {
  background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
  position: sticky;
  top: 0;
  z-index: 10;
}

th {
  padding: 16px 14px;
  text-align: left;
  color: white;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  white-space: nowrap;
}

td {
  padding: 14px;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
  font-size: 14px;
}

tbody tr {
  transition: all 0.2s ease;
}

tbody tr:hover {
  background: #f0f9ff;
}

tbody tr:last-child td {
  border-bottom: none;
}

/* Badges */
.badge {
  padding: 4px 10px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 12px;
  display: inline-block;
  white-space: nowrap;
}

.badge-course {
  background: #dbeafe;
  color: #1e40af;
}

.badge-year {
  background: #e0e7ff;
  color: #3730a3;
}

.time-badge {
  background: #fef3c7;
  color: #92400e;
  font-family: 'Courier New', monospace;
}

.penalty-badge {
  background: #fee2e2;
  color: #991b1b;
  font-weight: 700;
}

/* Excuse Dropdown Styling */
.excuse-dropdown {
  padding: 6px 10px;
  border-radius: 6px;
  border: 2px solid #e2e8f0;
  font-weight: 500;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.3s ease;
  outline: none;
  background: white;
  color: #334155;
}

.excuse-dropdown:focus {
  border-color: #0ea5e9;
  box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.1);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #64748b;
  font-size: 15px;
}

.empty-state i {
  font-size: 64px;
  color: #cbd5e1;
  margin-bottom: 16px;
}

/* Responsive */
@media (max-width: 768px) {
  .controls {
    padding: 16px;
  }
  
  .filters {
    flex-direction: column;
    width: 100%;
  }
  
  .filters select,
  .filters input[type="text"],
  .btn {
    width: 100%;
  }
  
  .stats-container {
    grid-template-columns: 1fr;
  }

  .event-details {
    flex-direction: column;
    gap: 12px;
  }
}
</style>
</head>
<body>
<div class="container">
    <?php if ($event): ?>
        <button onclick="window.location.href='attendance.php'" class="back-btn">
            <i class="fa fa-arrow-left"></i> Back
        </button>

        <!-- Event Header -->
        <div class="event-header">
            <h1 class="event-title"><?= htmlspecialchars($event_name) ?></h1>
            <div class="event-details">
                <div class="event-detail">
                    <i class="fa fa-calendar"></i>
                    <?= date('F j, Y', strtotime($event_date)) ?>
                </div>
                <div class="event-detail">
                    <i class="fa fa-map-marker-alt"></i>
                    <?= htmlspecialchars($event['location']) ?>
                </div>
                <div class="event-detail">
                    <i class="fa fa-clock"></i>
                    <span class="badge badge-time"><?= htmlspecialchars($event['Time_Session']) ?></span>
                </div>
                <div class="event-detail">
                    <i class="fa fa-users"></i>
                    <span class="badge badge-year-level"><?= formatYearLevel($event['YearLevel']) ?></span>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Header for general attendance view -->
        <div class="page-header">
            <i class="fa-solid fa-clipboard-list"></i>
            <h2>Attendance Records</h2>
        </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <h4>Total Records</h4>
            <p><?= number_format($stats['total_records']) ?></p>
        </div>
        <div class="stat-card">
            <h4>Unique Students</h4>
            <p><?= number_format($stats['unique_students']) ?></p>
        </div>
        <div class="stat-card">
            <h4>Total Penalties</h4>
            <p>₱<?= number_format($stats['total_penalties'], 2) ?></p>
        </div>
    </div>

    <!-- Controls -->
    <div class="controls">
        <form method="get" class="filters">
            <?php if (!empty($event_name) && !empty($event_date)): ?>
                <input type="hidden" name="event" value="<?= htmlspecialchars($event_name) ?>">
                <input type="hidden" name="date" value="<?= htmlspecialchars($event_date) ?>">
            <?php endif; ?>
            
            <input type="text" 
                   name="search" 
                   placeholder="Search by Student ID, Name, or Year Level..." 
                   value="<?= htmlspecialchars($search) ?>">
            
            <button type="submit" class="btn search-btn">
                <i class="fa fa-search"></i> Search
            </button>
            
            <button type="button" class="btn clear-btn" onclick="clearFilters()">
                <i class="fa fa-rotate"></i> Clear
            </button>

            <button type="button" class="btn export-btn" id="exportBtn" onclick="exportToFines()">
                <i class="fa fa-file-invoice"></i> <span id="exportBtnText">Export to Fines</span>
            </button>
        </form>
    </div>

    <!-- Table -->
    <div class="table-container">
        <div class="table-scroll">
            <table id="attendanceTable">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Course</th>
                        <th>Year</th>
                        <?php if ($show_am): ?>
                            <th>AM Login</th>
                            <th>AM Logout</th>
                        <?php endif; ?>
                        <?php if ($show_pm): ?>
                            <th>PM Login</th>
                            <th>PM Logout</th>
                        <?php endif; ?>
                        <th>Excuse Letter</th>
                        <th>Total Penalty</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr id="row-<?= $row['attendance_id'] ?>">
                        <td><strong><?= htmlspecialchars($row['students_id']) ?></strong></td>
                        <td><?= htmlspecialchars($row['FullName'] ?? 'N/A') ?></td>
                        <td>
                            <?php if ($row['Course']): ?>
                                <span class="badge badge-course"><?= htmlspecialchars($row['Course']) ?></span>
                            <?php else: ?>
                                <span style="color: #94a3b8;">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['YearLevel']): ?>
                                <span class="badge badge-year"><?= htmlspecialchars($row['YearLevel']) ?></span>
                            <?php else: ?>
                                <span style="color: #94a3b8;">N/A</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($show_am): ?>
                            <td>
                                <?php if ($row['amLogin']): ?>
                                    <span class="time-badge"><?= htmlspecialchars($row['amLogin']) ?></span>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['amLogout']): ?>
                                    <span class="time-badge"><?= htmlspecialchars($row['amLogout']) ?></span>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <?php if ($show_pm): ?>
                            <td>
                                <?php if ($row['pmLogin']): ?>
                                    <span class="time-badge"><?= htmlspecialchars($row['pmLogin']) ?></span>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['pmLogout']): ?>
                                    <span class="time-badge"><?= htmlspecialchars($row['pmLogout']) ?></span>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <td>
                            <select class="excuse-dropdown" 
                                    data-id="<?= $row['attendance_id'] ?>"
                                    onchange="updateExcuse(<?= $row['attendance_id'] ?>, this.value)">
                                <option value="Yes" <?= $row['ExcuseLetter'] === 'Yes' ? 'selected' : '' ?>>Yes</option>
                                <option value="No" <?= $row['ExcuseLetter'] === 'No' ? 'selected' : '' ?>>No</option>
                            </select>
                        </td>
                        <td>
                            <span class="penalty-badge" id="penalty-<?= $row['attendance_id'] ?>">
                                ₱<?= number_format($row['TotalPenalty'], 2) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= 4 + ($show_am ? 2 : 0) + ($show_pm ? 2 : 0) + 2 ?>">
                            <div class="empty-state">
                                <i class="fa fa-clipboard"></i>
                                <h3>No Attendance Records Found</h3>
                                <p>No records match your search criteria.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Check export status on page load
window.addEventListener('DOMContentLoaded', function() {
    checkExportStatus();
});

function checkExportStatus() {
    const formData = new URLSearchParams();
    formData.append('check_export_status', '1');
    
    <?php if (!empty($event_name) && !empty($event_date)): ?>
        formData.append('event_name', '<?= addslashes($event_name) ?>');
        formData.append('event_date', '<?= addslashes($event_date) ?>');
    <?php endif; ?>
    
    fetch('view_attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.is_exported) {
            updateButtonToExported();
        }
    })
    .catch(error => {
        console.error('Error checking export status:', error);
    });
}

function updateButtonToExported() {
    const exportBtn = document.getElementById('exportBtn');
    const exportBtnText = document.getElementById('exportBtnText');
    
    exportBtn.classList.add('already-exported');
    exportBtnText.textContent = 'Already Exported';
    exportBtn.onclick = showAlreadyExportedMessage;
}

function showAlreadyExportedMessage() {
    Swal.fire({
        icon: 'info',
        title: 'Already Exported',
        html: `
            <div style="text-align: left; padding: 10px;">
                <p>These attendance records have already been exported to the Fines table.</p>
                <br>
                <p>Would you like to view the exported records or re-export to update any changes?</p>
            </div>
        `,
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonColor: '#0ea5e9',
        denyButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa fa-eye"></i> View Fines Records',
        denyButtonText: '<i class="fa fa-refresh"></i> Re-Export',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // View Fines Records
            window.location.href = 'fines_record.php<?= !empty($event_name) && !empty($event_date) ? "?event=" . urlencode($event_name) . "&date=" . urlencode($event_date) : "" ?>';
        } else if (result.isDenied) {
            // Re-export
            performExport();
        }
    });
}

function clearFilters() {
    <?php if (!empty($event_name) && !empty($event_date)): ?>
        window.location.href = 'view_attendance.php?event=<?= urlencode($event_name) ?>&date=<?= urlencode($event_date) ?>';
    <?php else: ?>
        window.location.href = 'view_attendance.php';
    <?php endif; ?>
}

// Auto-update Excuse Letter & Penalty
function updateExcuse(attendanceId, excuseLetter) {
    const dropdown = document.querySelector(`select[data-id="${attendanceId}"]`);
    const penaltyDisplay = document.getElementById(`penalty-${attendanceId}`);
    
    dropdown.disabled = true;
    
    fetch('view_attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `update_excuse=1&attendance_id=${attendanceId}&excuse_letter=${excuseLetter}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            penaltyDisplay.textContent = '₱' + parseFloat(data.new_penalty).toFixed(2);
            Swal.fire({
                icon: 'success',
                title: 'Updated!',
                text: 'Excuse letter status updated successfully',
                timer: 1500,
                showConfirmButton: false
            });
        } else {
            Swal.fire('Error', 'Failed to update excuse letter status', 'error');
        }
        dropdown.disabled = false;
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        Swal.fire('Error', 'An error occurred while updating', 'error');
        dropdown.disabled = false;
    });
}

// ✅ NEW: Export to Fines Function
function exportToFines() {
    const exportBtn = document.getElementById('exportBtn');
    
    // Check if already exported
    if (exportBtn.classList.contains('already-exported')) {
        showAlreadyExportedMessage();
        return;
    }
    
    Swal.fire({
        title: 'Export to Fines',
        html: `
            <div style="text-align: left; padding: 10px;">
                <p>📋 This will export <strong>ALL attendance records</strong> to the Fines table.</p>
                <br>
                <p>✅ Includes records with <strong>₱0 penalty</strong></p>
                <p>✅ Event details will be saved (name, date, location)</p>
                <p>✅ Existing records will be <strong>UPDATED</strong></p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa fa-check"></i> Yes, Export Now',
        cancelButtonText: 'Cancel',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            return performExport();
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            const data = result.value;
            
            let detailsHTML = '<div style="text-align: left; padding: 10px;">';
            
            if (data.inserted > 0) {
                detailsHTML += `<p><strong>✅ ${data.inserted}</strong> new records added to Fines</p>`;
            }
            if (data.updated > 0) {
                detailsHTML += `<p><strong>🔄 ${data.updated}</strong> existing records updated</p>`;
            }
            if (data.total === 0) {
                detailsHTML += `<p><strong>ℹ️</strong> No records to export</p>`;
            }
            
            detailsHTML += '</div>';
            
            // Update button to "Already Exported"
            updateButtonToExported();
            
            Swal.fire({
                icon: 'success',
                title: 'Export Successful!',
                html: detailsHTML,
                showCancelButton: true,
                confirmButtonColor: '#0ea5e9',
                cancelButtonColor: '#64748b',
                confirmButtonText: '<i class="fa fa-eye"></i> View Fines Records',
                cancelButtonText: '<i class="fa fa-clock"></i> Maybe Later'
            }).then((result) => {
                if (result.isConfirmed) {
                    // User clicked "View Fines Records"
                    window.location.href = 'fines_record.php<?= !empty($event_name) && !empty($event_date) ? "?event=" . urlencode($event_name) . "&date=" . urlencode($event_date) : "" ?>';
                }
                // If user clicks "Maybe Later" or closes the dialog, nothing happens (stays on current page)
            });
        }
    });
}

function performExport() {
    const formData = new URLSearchParams();
    formData.append('export_to_fines', '1');
    
    <?php if (!empty($event_name) && !empty($event_date)): ?>
        formData.append('event_name', '<?= addslashes($event_name) ?>');
        formData.append('event_date', '<?= addslashes($event_date) ?>');
    <?php endif; ?>
    
    return fetch('view_attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || 'Export failed');
        }
        return data;
    })
    .catch(error => {
        Swal.showValidationMessage(`Export failed: ${error.message}`);
        return null;
    });
}
</script>
</body>
</html>
<?php $conn->close(); ?>
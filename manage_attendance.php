<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['usertype'], ['Secretary', 'Treasurer', 'Auditor', 'Governor', 'Vice Governor'])) {
    header("Location: ../login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "csso");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get event parameters
$event_name = $_GET['event'] ?? '';
$event_date = $_GET['date'] ?? '';

if (empty($event_name) || empty($event_date)) {
    header("Location: attendance.php");
    exit();
}

// Get event details
$event_sql = "SELECT * FROM event WHERE event_Name = ? AND event_Date = ?";
$stmt = $conn->prepare($event_sql);
$stmt->bind_param("ss", $event_name, $event_date);
$stmt->execute();
$event_result = $stmt->get_result();
$event = $event_result->fetch_assoc();

if (!$event) {
    header("Location: attendance.php");
    exit();
}

// Determine what columns to show based on Time_Session
$show_am = in_array($event['Time_Session'], ['AM Session', 'Full Day']);
$show_pm = in_array($event['Time_Session'], ['PM Session', 'Full Day']);

$message = '';
$message_type = '';

// ✅ FUNCTION: Calculate Total Penalty (HIDDEN from UI, but saves to DB)
function calculatePenalty($amLogin, $amLogout, $pmLogin, $pmLogout, $timeSession) {
    $penalty = 0;
    
    if ($timeSession === 'AM Session') {
        if (empty($amLogin)) $penalty += 50;
        if (empty($amLogout)) $penalty += 50;
    } elseif ($timeSession === 'PM Session') {
        if (empty($pmLogin)) $penalty += 50;
        if (empty($pmLogout)) $penalty += 50;
    } else {
        if (empty($amLogin)) $penalty += 50;
        if (empty($amLogout)) $penalty += 50;
        if (empty($pmLogin)) $penalty += 50;
        if (empty($pmLogout)) $penalty += 50;
    }
    
    return $penalty;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['attendance_data'])) {
        $success_count = 0;
        $error_count = 0;
        $total_students = count($_POST['attendance_data']);
        $absent_students = 0;
        
        foreach ($_POST['attendance_data'] as $student_id => $data) {
            $students_id = $conn->real_escape_string($student_id);
            
            // Count absent students (no time recorded)
            $hasTime = false;
            foreach ($data as $timeValue) {
                if (!empty($timeValue)) {
                    $hasTime = true;
                    break;
                }
            }
            if (!$hasTime) $absent_students++;
            
            // Auto-clear data based on Time_Session
            if ($event['Time_Session'] === 'AM Session') {
                $amLogin = !empty($data['amLogin']) ? $conn->real_escape_string($data['amLogin']) : NULL;
                $amLogout = !empty($data['amLogout']) ? $conn->real_escape_string($data['amLogout']) : NULL;
                $pmLogin = NULL;
                $pmLogout = NULL;
            } elseif ($event['Time_Session'] === 'PM Session') {
                $amLogin = NULL;
                $amLogout = NULL;
                $pmLogin = !empty($data['pmLogin']) ? $conn->real_escape_string($data['pmLogin']) : NULL;
                $pmLogout = !empty($data['pmLogout']) ? $conn->real_escape_string($data['pmLogout']) : NULL;
            } else {
                $amLogin = !empty($data['amLogin']) ? $conn->real_escape_string($data['amLogin']) : NULL;
                $amLogout = !empty($data['amLogout']) ? $conn->real_escape_string($data['amLogout']) : NULL;
                $pmLogin = !empty($data['pmLogin']) ? $conn->real_escape_string($data['pmLogin']) : NULL;
                $pmLogout = !empty($data['pmLogout']) ? $conn->real_escape_string($data['pmLogout']) : NULL;
            }
            
            // ✅ CALCULATE PENALTY (silently in background)
            $totalPenalty = calculatePenalty($amLogin, $amLogout, $pmLogin, $pmLogout, $event['Time_Session']);
            
            // Check if record exists
            $check_sql = "SELECT attendance_id FROM attendance WHERE students_id = ? AND event_name = ? AND event_date = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("iss", $students_id, $event_name, $event_date);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // UPDATE with calculated penalty
                $update_sql = "UPDATE attendance SET amLogin = ?, amLogout = ?, pmLogin = ?, pmLogout = ?, TotalPenalty = ? WHERE students_id = ? AND event_name = ? AND event_date = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssssdiss", $amLogin, $amLogout, $pmLogin, $pmLogout, $totalPenalty, $students_id, $event_name, $event_date);
                
                if ($update_stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            } else {
                // Insert new record (even if all NULL - for absent students)
                $user_id = $_SESSION['user_id'] ?? 1;
                
                $reg_sql = "SELECT registration_no FROM registration WHERE students_id = ? LIMIT 1";
                $reg_stmt = $conn->prepare($reg_sql);
                $reg_stmt->bind_param("s", $students_id);
                $reg_stmt->execute();
                $reg_result = $reg_stmt->get_result();
                $reg_no = '';
                if ($reg_row = $reg_result->fetch_assoc()) {
                    $reg_no = $reg_row['registration_no'];
                }
                
                // INSERT with calculated penalty (even for absent students)
                $insert_sql = "INSERT INTO attendance (UserID, students_id, registration_no, event_name, event_date, location, amLogin, amLogout, pmLogin, pmLogout, TotalPenalty) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iissssssssd", $user_id, $students_id, $reg_no, $event_name, $event_date, $event['location'], $amLogin, $amLogout, $pmLogin, $pmLogout, $totalPenalty);
                
                if ($insert_stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        if ($error_count === 0) {
            $message = "Attendance updated successfully for $success_count students!";
            if ($absent_students > 0) {
                $message .= " ($absent_students students marked as absent)";
            }
            $message_type = 'success';
        } else {
            $message = "Updated $success_count records, but $error_count records failed to update.";
            $message_type = 'error';
        }
    }
}

// ✅ CLEANUP & RECALCULATE (silent background process)
if ($event['Time_Session'] === 'AM Session') {
    $cleanup_sql = "UPDATE attendance 
                    SET pmLogin = NULL, 
                        pmLogout = NULL,
                        TotalPenalty = (
                            (CASE WHEN amLogin IS NULL THEN 50 ELSE 0 END) +
                            (CASE WHEN amLogout IS NULL THEN 50 ELSE 0 END)
                        )
                    WHERE event_name = ? AND event_date = ?";
    $cleanup_stmt = $conn->prepare($cleanup_sql);
    $cleanup_stmt->bind_param("ss", $event_name, $event_date);
    $cleanup_stmt->execute();
} elseif ($event['Time_Session'] === 'PM Session') {
    $cleanup_sql = "UPDATE attendance 
                    SET amLogin = NULL, 
                        amLogout = NULL,
                        TotalPenalty = (
                            (CASE WHEN pmLogin IS NULL THEN 50 ELSE 0 END) +
                            (CASE WHEN pmLogout IS NULL THEN 50 ELSE 0 END)
                        )
                    WHERE event_name = ? AND event_date = ?";
    $cleanup_stmt = $conn->prepare($cleanup_sql);
    $cleanup_stmt->bind_param("ss", $event_name, $event_date);
    $cleanup_stmt->execute();
} else {
    $cleanup_sql = "UPDATE attendance 
                    SET TotalPenalty = (
                        (CASE WHEN amLogin IS NULL THEN 50 ELSE 0 END) +
                        (CASE WHEN amLogout IS NULL THEN 50 ELSE 0 END) +
                        (CASE WHEN pmLogin IS NULL THEN 50 ELSE 0 END) +
                        (CASE WHEN pmLogout IS NULL THEN 50 ELSE 0 END)
                    )
                    WHERE event_name = ? AND event_date = ?";
    $cleanup_stmt = $conn->prepare($cleanup_sql);
    $cleanup_stmt->bind_param("ss", $event_name, $event_date);
    $cleanup_stmt->execute();
}

// ✅ Get registered students - FILTERED BY SEMESTER & SCHOOL YEAR
$students_sql = "SELECT DISTINCT sp.students_id, sp.FirstName, sp.LastName, sp.Course, sp.YearLevel, sp.Section 
                 FROM student_profile sp
                 INNER JOIN registration r ON sp.students_id = r.students_id
                 WHERE r.semester = ? AND r.school_year = ?";

// Filter by YearLevel if not AllLevels
if ($event['YearLevel'] !== 'AllLevels') {
    $year_mapping = [
        '1stYearLevel' => '1stYear',
        '2ndYearLevel' => '2ndYear',
        '3rdYearLevel' => '3rdYear',
        '4thYearLevel' => '4thYear'
    ];
    
    $target_year = $year_mapping[$event['YearLevel']] ?? '';
    if ($target_year) {
        $students_sql .= " AND sp.YearLevel = ?";
    }
}

$students_sql .= " ORDER BY sp.LastName, sp.FirstName";

$stmt = $conn->prepare($students_sql);

// Bind parameters based on YearLevel filter
if ($event['YearLevel'] !== 'AllLevels' && !empty($target_year)) {
    $stmt->bind_param("sss", $event['Semester'], $event['school_year'], $target_year);
} else {
    $stmt->bind_param("ss", $event['Semester'], $event['school_year']);
}

$stmt->execute();
$students_result = $stmt->get_result();

// Get existing attendance records
$attendance_sql = "SELECT * FROM attendance WHERE event_name = ? AND event_date = ?";
$stmt = $conn->prepare($attendance_sql);
$stmt->bind_param("ss", $event_name, $event_date);
$stmt->execute();
$attendance_result = $stmt->get_result();

$existing_attendance = [];
while ($row = $attendance_result->fetch_assoc()) {
    $existing_attendance[$row['students_id']] = $row;
}

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
<title>Manage Attendance - <?= htmlspecialchars($event_name) ?> | CSSO</title>
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
  min-height: 100vh;
  position: relative;
  padding-bottom: 100px;
}

.container {
  padding: 24px;
  max-width: 1200px;
  margin: 0 auto;
}

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

.event-header {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
  margin-bottom: 20px;
  border-left: 4px solid #f59e0b;
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

.badge-year {
  background: #e0e7ff;
  color: #3730a3;
}

.badge-semester {
  background: #ddd6fe;
  color: #5b21b6;
}

.badge-sy {
  background: #d1fae5;
  color: #065f46;
}

.message {
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-weight: 500;
}

.message.success {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #a7f3d0;
}

.message.error {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.attendance-form {
  background: white;
  border-radius: 12px;
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  margin-bottom: 100px;
}

.form-header {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
  padding: 20px;
}

.form-header h3 {
  font-size: 18px;
  font-weight: 600;
}

.form-header p {
  font-size: 13px;
  opacity: 0.9;
  margin-top: 4px;
}

.form-actions {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1000;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.save-btn {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.save-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

.table-container {
  max-height: 600px;
  overflow-y: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead {
  background: #f8fafc;
  position: sticky;
  top: 0;
  z-index: 10;
}

th {
  padding: 16px 14px;
  text-align: left;
  color: #374151;
  font-weight: 600;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #e2e8f0;
  background: #f8fafc;
}

td {
  padding: 12px 14px;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
  font-size: 14px;
}

tbody tr:hover {
  background: #f0f9ff;
}

.time-input {
  width: 100%;
  padding: 8px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 13px;
  text-align: center;
  cursor: pointer;
  background: white;
  transition: all 0.3s ease;
}

.time-input:focus {
  outline: none;
  border-color: #0ea5e9;
  box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.1);
}

.time-input.verified {
  border-color: #10b981;
  background: #f0fdf4;
  cursor: not-allowed;
}

.time-input.has-record {
  border-color: #10b981;
  background: #f0fdf4;
  cursor: not-allowed;
}

.badge-course {
  background: #dbeafe;
  color: #1e40af;
  padding: 4px 10px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 12px;
  display: inline-block;
}

.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #64748b;
}

.empty-state i {
  font-size: 48px;
  color: #cbd5e1;
  margin-bottom: 12px;
}

/* Custom SweetAlert Styles */
.swal-input-large {
  font-size: 16px !important;
  padding: 12px !important;
  text-align: center !important;
  font-weight: 600 !important;
  letter-spacing: 1px !important;
}

.swal-input-extra-large {
  font-size: 200px !important;
  padding: 30px 20px !important;
  text-align: center !important;
  font-weight: 700 !important;
  letter-spacing: 10px !important;
  width: 100% !important;
  max-width: 900px !important;
  margin: 30px auto !important;
  border: 3px solid #10b981 !important;
  border-radius: 12px !important;
  height: 250px !important;
  line-height: 1 !important;
}

.swal-input-extra-large:focus {
  border-color: #059669 !important;
  box-shadow: 0 0 0 5px rgba(16, 185, 129, 0.2) !important;
  outline: none !important;
}

.swal-input-extra-large::placeholder {
  font-size: 40px !important;
  color: #cbd5e1 !important;
  letter-spacing: 2px !important;
}

@media (max-width: 768px) {
  .table-container {
    overflow-x: auto;
  }
  
  table {
    min-width: 800px;
  }
  
  .form-actions {
    position: fixed;
    bottom: 15px;
    right: 15px;
    left: 15px;
  }
  
  .btn {
    width: 100%;
    justify-content: center;
  }
  
  body {
    padding-bottom: 80px;
  }
}
</style>
</head>
<body>
<div class="container">
    <a href="view_attendance.php?event=<?= urlencode($event_name) ?>&date=<?= urlencode($event_date) ?>" class="back-btn">
        <i class="fa fa-arrow-left"></i> Back to View Attendance
    </a>

    <!-- Event Header -->
    <div class="event-header">
        <h1 class="event-title">Manage Attendance - <?= htmlspecialchars($event_name) ?></h1>
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
                <span class="badge badge-year"><?= formatYearLevel($event['YearLevel']) ?></span>
            </div>
            <div class="event-detail">
                <i class="fa fa-book"></i>
                <span class="badge badge-semester"><?= htmlspecialchars($event['Semester']) ?></span>
            </div>
            <div class="event-detail">
                <i class="fa fa-calendar-alt"></i>
                <span class="badge badge-sy"><?= htmlspecialchars($event['school_year']) ?></span>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message <?= $message_type ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" id="attendanceForm">
        <div class="attendance-form">
            <div class="form-header">
                <h3><i class="fa fa-edit"></i> Student Attendance Records</h3>
                <p>Found <?= $students_result->num_rows ?> registered students (<?= htmlspecialchars($event['Semester']) ?> - <?= htmlspecialchars($event['school_year']) ?>)</p>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Course/Year</th>
                            <?php if ($show_am): ?>
                                <th>AM Login</th>
                                <th>AM Logout</th>
                            <?php endif; ?>
                            <?php if ($show_pm): ?>
                                <th>PM Login</th>
                                <th>PM Logout</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($students_result->num_rows > 0): ?>
                        <?php while($student = $students_result->fetch_assoc()): 
                            $attendance = $existing_attendance[$student['students_id']] ?? [];
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($student['students_id']) ?></strong></td>
                            <td><?= htmlspecialchars($student['LastName'] . ', ' . $student['FirstName']) ?></td>
                            <td>
                                <span class="badge badge-course"><?= htmlspecialchars($student['Course']) ?></span>
                                <span class="badge badge-course"><?= htmlspecialchars($student['YearLevel']) ?></span>
                            </td>
                            <?php if ($show_am): ?>
                                <td>
                                    <input type="time" 
                                           name="attendance_data[<?= $student['students_id'] ?>][amLogin]" 
                                           value="<?= htmlspecialchars($attendance['amLogin'] ?? '') ?>" 
                                           class="time-input <?= !empty($attendance['amLogin']) ? 'has-record' : '' ?>"
                                           data-student-id="<?= $student['students_id'] ?>"
                                           data-student-name="<?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) ?>"
                                           <?= !empty($attendance['amLogin']) ? 'readonly' : 'readonly' ?>>
                                </td>
                                <td>
                                    <input type="time" 
                                           name="attendance_data[<?= $student['students_id'] ?>][amLogout]" 
                                           value="<?= htmlspecialchars($attendance['amLogout'] ?? '') ?>" 
                                           class="time-input <?= !empty($attendance['amLogout']) ? 'has-record' : '' ?>"
                                           data-student-id="<?= $student['students_id'] ?>"
                                           data-student-name="<?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) ?>"
                                           <?= !empty($attendance['amLogout']) ? 'readonly' : 'readonly' ?>>
                                </td>
                            <?php endif; ?>
                            <?php if ($show_pm): ?>
                                <td>
                                    <input type="time" 
                                           name="attendance_data[<?= $student['students_id'] ?>][pmLogin]" 
                                           value="<?= htmlspecialchars($attendance['pmLogin'] ?? '') ?>" 
                                           class="time-input <?= !empty($attendance['pmLogin']) ? 'has-record' : '' ?>"
                                           data-student-id="<?= $student['students_id'] ?>"
                                           data-student-name="<?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) ?>"
                                           <?= !empty($attendance['pmLogin']) ? 'readonly' : 'readonly' ?>>
                                </td>
                                <td>
                                    <input type="time" 
                                           name="attendance_data[<?= $student['students_id'] ?>][pmLogout]" 
                                           value="<?= htmlspecialchars($attendance['pmLogout'] ?? '') ?>" 
                                           class="time-input <?= !empty($attendance['pmLogout']) ? 'has-record' : '' ?>"
                                           data-student-id="<?= $student['students_id'] ?>"
                                           data-student-name="<?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']) ?>"
                                           <?= !empty($attendance['pmLogout']) ? 'readonly' : 'readonly' ?>>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= ($show_am && $show_pm) ? 7 : 5 ?>">
                                <div class="empty-state">
                                    <i class="fa fa-user-slash"></i>
                                    <p><strong>No registered students found</strong></p>
                                    <p style="font-size: 13px; margin-top: 8px;">
                                        No students registered for <?= htmlspecialchars($event['Semester']) ?> - <?= htmlspecialchars($event['school_year']) ?>
                                        <?php if ($event['YearLevel'] !== 'AllLevels'): ?>
                                            (<?= formatYearLevel($event['YearLevel']) ?>)
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SAVE BUTTON - FIXED AT BOTTOM RIGHT -->
        <div class="form-actions">
            <button type="submit" class="btn save-btn" id="saveButton">
                <i class="fa fa-save"></i> Save All Changes
            </button>
        </div>
    </form>
</div>

<script>
// 🎯 GET CURRENT TIME in HH:MM format
function getCurrentTime() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
}

// 🎯 INITIALIZE: Add click listeners only to empty time inputs
document.addEventListener('DOMContentLoaded', function() {
    const timeInputs = document.querySelectorAll('.time-input');
    
    timeInputs.forEach(input => {
        // If input already has a value, make it completely unclickable
        if (input.value) {
            input.classList.add('has-record');
            input.style.cursor = 'not-allowed';
            input.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            });
        } else {
            // Add click event listener only to empty inputs
            input.addEventListener('click', async function(e) {
                e.preventDefault();
                await verifyAndRecordTime(this);
            });
        }
        
        // Prevent manual typing for all inputs
        input.addEventListener('keydown', function(e) {
            e.preventDefault();
        });
    });
});

// 🎯 CHECK IF TIME IS VALID FOR SESSION
function isValidTimeForSession(inputName, currentHour) {
    // AM sessions: 5:00 AM - 11:59 AM (hours 5-11)
    // PM sessions: 12:00 PM - 11:59 PM (hours 12-23)
    
    if (inputName.includes('amLogin') || inputName.includes('amLogout')) {
        // For AM inputs, current time must be AM (5-11)
        return currentHour >= 5 && currentHour < 12;
    } else if (inputName.includes('pmLogin') || inputName.includes('pmLogout')) {
        // For PM inputs, current time must be PM (12-23)
        return currentHour >= 12;
    }
    return true;
}

// 🎯 MAIN FUNCTION: Verify Student ID and Record Current Time
async function verifyAndRecordTime(input) {
    // DOUBLE CHECK: If input already has value, DO NOTHING
    if (input.value) {
        return;
    }
    
    const correctStudentID = input.getAttribute('data-student-id');
    const studentName = input.getAttribute('data-student-name');
    const currentTime = getCurrentTime();
    const now = new Date();
    const currentHour = now.getHours();
    
    // Check if current time is valid for this session
    if (!isValidTimeForSession(input.name, currentHour)) {
        const sessionType = input.name.includes('am') ? 'AM' : 'PM';
        const expectedTime = sessionType === 'AM' ? '' : 'afternoon/evening (12:00 PM onwards)';
        
        await Swal.fire({
            icon: 'error',
            title: 'Time Cut-Off Reached',
            html: `
                <p style="color: #64748b; margin-bottom: 10px;">
                    You can no longer record <strong style="color: #ef4444;">${sessionType} attendance</strong>.
                </p>
                <p style="background: #fee2e2; padding: 12px; border-radius: 8px; color: #991b1b;">
                    <i class="fa fa-clock"></i>
                    Current time: <strong>${currentTime}</strong>
                </p>
                <p style="margin-top: 15px; color: #64748b;">
                    ${sessionType} attendance has already closed, sorry ${expectedTime}.
                </p>
            `,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Understood'
        });
        return;
    }
    
    // Show verification dialog
    const { value: enteredID } = await Swal.fire({
        title: 'Please enter your Student ID',
        input: 'text',
        inputPlaceholder: 'Enter Student ID',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-check"></i> OK',
        cancelButtonText: '<i class="fa fa-times"></i> Cancel',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        allowOutsideClick: false,
        width: '1000px',
        padding: '40px',
        customClass: {
            input: 'swal-input-extra-large'
        },
        inputValidator: (value) => {
            if (!value) {
                return 'Student ID is required!';
            }
        }
    });
    
    // Handle verification result
    if (enteredID) {
        if (enteredID === correctStudentID) {
            // ✅ CORRECT ID - Record current time automatically
            input.value = currentTime;
            input.classList.add('verified', 'has-record');
            input.style.cursor = 'not-allowed';
            
            // Remove click event listener to prevent future clicks
            input.replaceWith(input.cloneNode(true));
            
            await Swal.fire({
                icon: 'success',
                title: 'Recorded Successfully!',
                html: `
                    <p>Student ID <strong>${correctStudentID}</strong> verified!</p>
                    <p style="margin-top: 10px; background: #d1fae5; padding: 10px; border-radius: 8px;">
                        <i class="fa fa-clock" style="color: #065f46;"></i>
                        Time recorded: <strong style="color: #065f46;">${currentTime}</strong>
                    </p>
                `,
                timer: 2000,
                showConfirmButton: false,
                timerProgressBar: true
            });
            
        } else {
            // ❌ WRONG ID - Show error
            await Swal.fire({
                icon: 'error',
                title: 'Verification Failed',
                html: `
                    <p>The Student ID you entered does not match!</p>
                    <p style="margin-top: 10px; color: #64748b;">
                        <strong>Entered:</strong> ${enteredID}<br>
                        <strong>Expected:</strong> ${correctStudentID}
                    </p>
                    <p style="margin-top: 15px; color: #ef4444; font-weight: 600;">
                        Please try again with the correct Student ID.
                    </p>
                `,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Try Again'
            });
            
            input.value = '';
            input.blur();
        }
    } else {
        // User cancelled
        input.blur();
    }
}

// 🎯 FORM SUBMISSION: Check if all students are absent and show confirmation
document.getElementById('attendanceForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const timeInputs = document.querySelectorAll('.time-input');
    let hasTimeRecords = false;
    
    // Check if any time input has value
    timeInputs.forEach(input => {
        if (input.value) {
            hasTimeRecords = true;
        }
    });
    
    // If NO time records (all students absent), show confirmation
    if (!hasTimeRecords) {
        const result = await Swal.fire({
            icon: 'warning',
            title: 'No Attendance Records',
            html: `
                <p style="color: #64748b; margin-bottom: 15px;">
                    You haven't recorded any attendance times for this event.
                </p>
                <p style="background: #fef3c7; padding: 12px; border-radius: 8px; color: #92400e;">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>All students will be marked as ABSENT with corresponding penalties.</strong>
                </p>
                <p style="margin-top: 15px; color: #64748b;">
                    Are you sure you want to continue?
                </p>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="fa fa-check"></i> Yes, Save Anyway',
            cancelButtonText: '<i class="fa fa-times"></i> Cancel',
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#64748b',
            reverseButtons: true
        });
        
        if (result.isConfirmed) {
            // User confirmed - submit the form
            this.submit();
        }
    } else {
        // Has time records - submit normally
        this.submit();
    }
});

// 🎯 SUCCESS/ERROR MESSAGES from PHP
<?php if ($message_type === 'success'): ?>
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '<?= addslashes($message) ?>',
        timer: 3000,
        showConfirmButton: false,
        timerProgressBar: true
    }).then(() => {
        window.location.href = 'view_attendance.php?event=<?= urlencode($event_name) ?>&date=<?= urlencode($event_date) ?>';
    });
<?php elseif ($message_type === 'error'): ?>
    Swal.fire({
        icon: 'error',
        title: 'Warning!',
        text: '<?= addslashes($message) ?>',
        confirmButtonColor: '#ef4444'
    });
<?php endif; ?>
</script>
</body>
</html>
<?php $conn->close(); ?>
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

// Get student ID from URL
$student_id = $_GET['student_id'] ?? '';

if (empty($student_id)) {
    header("Location: fines.php");
    exit();
}

// Get student information
$student_sql = "SELECT students_id, FirstName, LastName, MI, Suffix, Course, YearLevel, Section 
                FROM student_profile 
                WHERE students_id = ?";
$stmt = $conn->prepare($student_sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

if (!$student) {
    header("Location: fines.php");
    exit();
}

// Build full name
$fullname = trim($student['LastName'] . ', ' . $student['FirstName'] . ' ' . ($student['MI'] ? $student['MI'] . '.' : '') . ' ' . $student['Suffix']);

// Get all fines records for this student
$fines_sql = "SELECT fines_id, event_name, event_date, location, PenaltyAmount 
              FROM fines 
              WHERE students_id = ? 
              ORDER BY event_date DESC";
$stmt = $conn->prepare($fines_sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$fines_result = $stmt->get_result();

// Calculate total penalty
$total_penalty = 0;
$fines_data = [];
while ($row = $fines_result->fetch_assoc()) {
    $fines_data[] = $row;
    $total_penalty += $row['PenaltyAmount'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Fines Record | CSSO</title>
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
  max-width: 1400px;
  margin: 0 auto;
}

/* Back Button */
.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: #64748b;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  margin-bottom: 20px;
  transition: all 0.3s ease;
}

.back-btn:hover {
  background: #475569;
  transform: translateY(-2px);
}

/* Student Info Header */
.student-header {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  margin-bottom: 20px;
  border-left: 4px solid #0ea5e9;
}

.student-header h2 {
  font-size: 24px;
  color: #1e3a5f;
  margin-bottom: 8px;
}

.student-info {
  display: flex;
  gap: 24px;
  flex-wrap: wrap;
  margin-top: 12px;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.info-item i {
  color: #0ea5e9;
  font-size: 16px;
}

.info-item span {
  font-size: 14px;
  color: #64748b;
}

.info-item strong {
  color: #1e3a5f;
  font-weight: 600;
}

/* Stats Card */
.stats-card {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  margin-bottom: 20px;
  border-left: 4px solid #ef4444;
  max-width: 300px;
}

.stats-card h4 {
  font-size: 13px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.stats-card p {
  font-size: 28px;
  font-weight: 700;
  color: #ef4444;
}

/* Table Container */
.table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead {
  background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
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

/* Event Badge */
.event-badge {
  background: #dbeafe;
  color: #1e40af;
  padding: 4px 10px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 12px;
  display: inline-block;
}

/* Penalty Amount */
.penalty-amount {
  font-weight: 700;
  font-size: 15px;
  color: #ef4444;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #64748b;
}

.empty-state i {
  font-size: 64px;
  color: #cbd5e1;
  margin-bottom: 16px;
}

.empty-state h3 {
  font-size: 18px;
  color: #475569;
  margin-bottom: 8px;
}

.empty-state p {
  font-size: 14px;
  color: #94a3b8;
}

/* Responsive */
@media (max-width: 768px) {
  .student-info {
    flex-direction: column;
    gap: 12px;
  }
  
  .table-container {
    overflow-x: auto;
  }
  
  table {
    min-width: 700px;
  }
}
</style>
</head>
<body>
<div class="container">
    <!-- Back Button -->
    <button class="back-btn" onclick="window.location.href='fines.php'">
        <i class="fa fa-arrow-left"></i> Back
    </button>

    <!-- Student Header -->
    <div class="student-header">
        <h2><?= htmlspecialchars($fullname) ?></h2>
        <div class="student-info">
            <div class="info-item">
                <i class="fa fa-id-card"></i>
                <span>Student ID: <strong><?= htmlspecialchars($student['students_id']) ?></strong></span>
            </div>
            <div class="info-item">
                <i class="fa fa-graduation-cap"></i>
                <span>Course: <strong><?= htmlspecialchars($student['Course']) ?></strong></span>
            </div>
            <div class="info-item">
                <i class="fa fa-layer-group"></i>
                <span>Year Level: <strong><?= htmlspecialchars($student['YearLevel']) ?></strong></span>
            </div>
            <?php if (!empty($student['Section'])): ?>
            <div class="info-item">
                <i class="fa fa-users"></i>
                <span>Section: <strong><?= htmlspecialchars($student['Section']) ?></strong></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Total Penalty Stats -->
    <div class="stats-card">
        <h4>Total Penalties</h4>
        <p>₱<?= number_format($total_penalty, 2) ?></p>
    </div>

    <!-- Fines Records Table -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Event Date</th>
                    <th>Location</th>
                    <th>Penalty Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($fines_data) > 0): ?>
                    <?php foreach ($fines_data as $fine): ?>
                    <tr>
                        <td>
                            <span class="event-badge">
                                <?= htmlspecialchars($fine['event_name'] ?? 'N/A') ?>
                            </span>
                        </td>
                        <td><?= $fine['event_date'] ? date('M d, Y', strtotime($fine['event_date'])) : 'N/A' ?></td>
                        <td><?= htmlspecialchars($fine['location'] ?? 'N/A') ?></td>
                        <td>
                            <span class="penalty-amount">₱<?= number_format($fine['PenaltyAmount'], 2) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <i class="fa fa-file-invoice"></i>
                                <h3>No Fines Records</h3>
                                <p>This student has no fines on record.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
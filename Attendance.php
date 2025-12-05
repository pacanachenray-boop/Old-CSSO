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

// ✅ HANDLE ADD EVENT FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_event'])) {
    $event_name = trim($_POST['event_name']);
    $event_date = $_POST['event_date'];
    $location = trim($_POST['location']);
    $time_session = $_POST['time_session'];
    $year_level = $_POST['year_level'];
    $semester = $_POST['semester'];
    $school_year = $_POST['school_year'];
    
    // Insert into database
    $insert_sql = "INSERT INTO event (event_Name, event_Date, location, Time_Session, YearLevel, Semester, school_year) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("sssssss", $event_name, $event_date, $location, $time_session, $year_level, $semester, $school_year);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Event added successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to add event. Please try again.";
    }
    
    header("Location: attendance.php");
    exit();
}

// Helper function
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Get events with attendance statistics
$sql = "SELECT 
    e.event_Name,
    e.event_Date,
    e.location,
    e.Time_Session,
    e.YearLevel,
    e.Semester,
    e.school_year,
    COUNT(a.attendance_id) as total_attendance,
    COUNT(DISTINCT a.students_id) as unique_students
FROM event e
LEFT JOIN attendance a ON e.event_Name = a.event_name AND e.event_Date = a.event_date
GROUP BY e.event_Name, e.event_Date, e.location, e.Time_Session, e.YearLevel, e.Semester, e.school_year
ORDER BY e.event_Date DESC";

$result = $conn->query($sql);

// Get unique semesters for filters
$semestersQuery = "SELECT DISTINCT Semester FROM event WHERE Semester IS NOT NULL AND Semester != '' ORDER BY Semester";
$semesters = $conn->query($semestersQuery);

// Year Level Display Mapping
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
<title>Events & Attendance | CSSO</title>
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

.filters-row {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  align-items: center;
  margin-bottom: 16px;
}

/* Search Bar */
.search-container {
  position: relative;
  flex: 1;
  min-width: 250px;
}

.search-input {
  width: 100%;
  padding: 10px 40px 10px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.3s ease;
  background: #f8fafc;
}

.search-input:focus {
  outline: none;
  border-color: #0ea5e9;
  background: white;
  box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
}

.search-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
  pointer-events: none;
}

/* Filter Dropdowns */
.filter-group {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.filter-select {
  padding: 10px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  background: #f8fafc;
  color: #1e3a5f;
  cursor: pointer;
  transition: all 0.3s ease;
  min-width: 180px;
  font-weight: 500;
}

.filter-select:focus {
  outline: none;
  border-color: #0ea5e9;
  background: white;
  box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
}

.filter-select:hover {
  border-color: #0ea5e9;
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
  white-space: nowrap;
}

.add-event-btn {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
}

.add-event-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
}

.button-row {
  display: flex;
  justify-content: flex-end;
}

/* Modal Styles */
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  animation: fadeIn 0.3s ease;
}

.modal.show {
  display: flex;
  align-items: center;
  justify-content: center;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  animation: slideUp 0.3s ease;
  max-height: 90vh;
  overflow-y: auto;
}

@keyframes slideUp {
  from {
    transform: translateY(50px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-header {
  background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
  color: white;
  padding: 20px 24px;
  border-radius: 12px 12px 0 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  font-size: 20px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 10px;
}

.modal-close {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  font-size: 24px;
  width: 36px;
  height: 36px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-close:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg);
}

.modal-body {
  padding: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #334155;
  font-size: 14px;
}

.form-group label .required {
  color: #ef4444;
  margin-left: 2px;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 10px 14px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  transition: all 0.3s ease;
  font-family: inherit;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #0ea5e9;
  box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
}

.modal-footer {
  padding: 16px 24px;
  border-top: 2px solid #f1f5f9;
  display: flex;
  gap: 12px;
  justify-content: flex-end;
}

.btn-cancel {
  background: #64748b;
  color: white;
}

.btn-cancel:hover {
  background: #475569;
}

.btn-submit {
  background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(14, 165, 233, 0.3);
}

.btn-submit:hover {
  box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
}

/* Events Grid */
.events-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.event-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  transition: all 0.3s ease;
  cursor: pointer;
  border: 2px solid transparent;
}

.event-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
  border-color: #0ea5e9;
}

.event-card.hidden {
  display: none;
}

.event-header {
  background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
  color: white;
  padding: 20px;
  position: relative;
}

.event-header h3 {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 8px;
  line-height: 1.3;
}

.event-date {
  font-size: 14px;
  opacity: 0.9;
  display: flex;
  align-items: center;
  gap: 6px;
}

.event-body {
  padding: 20px;
}

.event-info {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 16px;
}

.event-detail-row {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  color: #64748b;
  font-size: 13px;
  text-align: center;
  padding: 10px;
  background: #f8fafc;
  border-radius: 8px;
}

.event-detail-row i {
  font-size: 18px;
}

.event-detail-row i.fa-map-marker-alt {
  color: #ef4444;
}

.event-detail-row i.fa-clock {
  color: #f59e0b;
}

.event-detail-row i.fa-users {
  color: #8b5cf6;
}

.event-detail-row i.fa-calendar-alt {
  color: #10b981;
}

.event-detail-row i.fa-book {
  color: #0ea5e9;
}

.event-detail-row span {
  font-weight: 500;
  color: #475569;
  word-break: break-word;
}

.event-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 16px;
}

.stat {
  text-align: center;
  padding: 12px;
  background: #f8fafc;
  border-radius: 8px;
}

.stat-number {
  font-size: 20px;
  font-weight: 700;
  color: #0ea5e9;
  display: block;
}

.stat-label {
  font-size: 12px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.event-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  flex: 1;
  padding: 10px 16px;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.view-attendance {
  background: #dbeafe;
  color: #1e40af;
}

.view-attendance:hover {
  background: #bfdbfe;
  transform: translateY(-1px);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #64748b;
  font-size: 15px;
  grid-column: 1 / -1;
}

.empty-state i {
  font-size: 64px;
  color: #cbd5e1;
  margin-bottom: 16px;
}

.empty-state h3 {
  font-size: 18px;
  margin-bottom: 8px;
  color: #475569;
}

/* No Results State */
.no-results {
  text-align: center;
  padding: 60px 20px;
  color: #64748b;
  font-size: 15px;
  grid-column: 1 / -1;
  display: none;
}

.no-results i {
  font-size: 64px;
  color: #cbd5e1;
  margin-bottom: 16px;
}

.no-results h3 {
  font-size: 18px;
  margin-bottom: 8px;
  color: #475569;
}

/* Responsive */
@media (max-width: 768px) {
  .events-grid {
    grid-template-columns: 1fr;
  }
  
  .filters-row {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-container {
    min-width: 100%;
  }
  
  .filter-group {
    width: 100%;
  }
  
  .filter-select {
    width: 100%;
  }
  
  .button-row {
    width: 100%;
  }
  
  .add-event-btn {
    width: 100%;
  }
  
  .event-stats {
    grid-template-columns: 1fr;
  }
  
  .event-info {
    grid-template-columns: 1fr;
  }
  
  .event-actions {
    flex-direction: column;
  }
  
  .modal-content {
    width: 95%;
    margin: 20px;
  }
  
  .modal-footer {
    flex-direction: column;
  }
  
  .modal-footer .btn {
    width: 100%;
  }
}
</style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="page-header">
        <i class="fa-solid fa-calendar-check"></i>
        <h2>Events & Attendance</h2>
    </div>

    <!-- Stats Cards -->
    <div class="stats-container">
        <div class="stat-card">
            <h4>Total Events</h4>
            <p id="totalEvents"><?= $result ? $result->num_rows : 0 ?></p>
        </div>
        <div class="stat-card">
            <h4>Total Attendance Records</h4>
            <p>
                <?php 
                $total_records = $conn->query("SELECT COUNT(*) as total FROM attendance")->fetch_assoc()['total'];
                echo $total_records;
                ?>
            </p>
        </div>
        <div class="stat-card">
            <h4>Today's Date</h4>
            <p style="font-size: 16px;"><?= date('M d, Y') ?></p>
        </div>
    </div>

    <!-- Controls -->
    <div class="controls">
        <div class="filters-row">
            <div class="search-container">
                <input type="text" 
                       class="search-input" 
                       id="searchInput" 
                       placeholder="Search events by name, location, or year level..."
                       onkeyup="filterEvents()">
                <i class="fa fa-search search-icon"></i>
            </div>
            <div class="filter-group">
                <select class="filter-select" id="semesterFilter" onchange="saveSemesterFilter()">
                    <option value="all">All Semesters</option>
                    <?php while($sem = $semesters->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($sem['Semester']) ?>">
                            <?= htmlspecialchars($sem['Semester']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <select class="filter-select" id="schoolYearFilter" onchange="saveSchoolYearFilter()">
                    <option value="all">All School Years</option>
                    <?php 
                    // Generate school years from 2024-2025 to 2033-2034
                    for($year = 2024; $year <= 2033; $year++) {
                        $schoolYear = $year . '-' . ($year + 1);
                        echo '<option value="' . $schoolYear . '">' . $schoolYear . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="button-row">
            <button class="btn add-event-btn" onclick="openAddEventModal()">
                <i class="fa fa-plus"></i> Add New Event
            </button>
        </div>
    </div>

    <!-- Events Grid -->
    <div class="events-grid" id="eventsGrid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($event = $result->fetch_assoc()): ?>
                <div class="event-card" 
                     data-event-name="<?= htmlspecialchars(strtolower($event['event_Name'])) ?>"
                     data-location="<?= htmlspecialchars(strtolower($event['location'] ?: '')) ?>"
                     data-year-level="<?= htmlspecialchars(strtolower(formatYearLevel($event['YearLevel']))) ?>"
                     data-semester="<?= htmlspecialchars($event['Semester'] ?: '') ?>"
                     data-school-year="<?= htmlspecialchars($event['school_year'] ?: '') ?>"
                     onclick="viewEventAttendance('<?= htmlspecialchars($event['event_Name']) ?>', '<?= $event['event_Date'] ?>')">
                    <div class="event-header">
                        <h3><?= htmlspecialchars($event['event_Name']) ?></h3>
                        <div class="event-date">
                            <i class="fa fa-calendar"></i>
                            <?= date('M d, Y', strtotime($event['event_Date'])) ?>
                        </div>
                    </div>
                    <div class="event-body">
                        <div class="event-info">
                            <div class="event-detail-row">
                                <i class="fa fa-map-marker-alt"></i>
                                <span><?= htmlspecialchars($event['location'] ?: 'No location') ?></span>
                            </div>
                            <div class="event-detail-row">
                                <i class="fa fa-clock"></i>
                                <span><?= htmlspecialchars($event['Time_Session']) ?></span>
                            </div>
                            <div class="event-detail-row">
                                <i class="fa fa-users"></i>
                                <span><?= formatYearLevel($event['YearLevel']) ?></span>
                            </div>
                            <div class="event-detail-row">
                                <i class="fa fa-book"></i>
                                <span><?= htmlspecialchars($event['Semester']) ?></span>
                            </div>
                            <div class="event-detail-row">
                                <i class="fa fa-calendar-alt"></i>
                                <span><?= htmlspecialchars($event['school_year']) ?></span>
                            </div>
                        </div>
                        
                        <div class="event-stats">
                            <div class="stat">
                                <span class="stat-number"><?= $event['total_attendance'] ?></span>
                                <span class="stat-label">Records</span>
                            </div>
                            <div class="stat">
                                <span class="stat-number"><?= $event['unique_students'] ?></span>
                                <span class="stat-label">Students</span>
                            </div>
                        </div>
                        
                        <div class="event-actions">
                            <button class="action-btn view-attendance" onclick="event.stopPropagation(); viewEventAttendance('<?= htmlspecialchars($event['event_Name']) ?>', '<?= $event['event_Date'] ?>')">
                                <i class="fa fa-eye"></i> View Attendance
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            <div class="no-results" id="noResults">
                <i class="fa fa-search"></i>
                <h3>No Events Found</h3>
                <p>Try adjusting your search or filter options.</p>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa fa-calendar-times"></i>
                <h3>No Events Found</h3>
                <p>Get started by creating your first event.</p>
                <button class="btn add-event-btn" style="margin-top: 16px;" onclick="openAddEventModal()">
                    <i class="fa fa-plus"></i> Create First Event
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Event Modal -->
<div id="addEventModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa fa-calendar-plus"></i> Add New Event</h3>
            <button class="modal-close" onclick="closeAddEventModal()">&times;</button>
        </div>
        <form method="POST" action="attendance.php" onsubmit="return validateForm()">
            <div class="modal-body">
                <div class="form-group">
                    <label for="event_name">Event Name <span class="required">*</span></label>
                    <input type="text" id="event_name" name="event_name" required placeholder="Enter event name">
                </div>
                
                <div class="form-group">
                    <label for="event_date">Event Date <span class="required">*</span></label>
                    <input type="date" id="event_date" name="event_date" required>
                </div>
                
                <div class="form-group">
                    <label for="location">Location <span class="required">*</span></label>
                    <input type="text" id="location" name="location" required placeholder="Enter event location">
                </div>
                
                <div class="form-group">
                    <label for="time_session">Time Session <span class="required">*</span></label>
                    <select id="time_session" name="time_session" required>
                        <option value="">Select Time Session</option>
                        <option value="AM Session">AM Session</option>
                        <option value="PM Session">PM Session</option>
                        <option value="Full Day">Full Day</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="year_level">Year Level <span class="required">*</span></label>
                    <select id="year_level" name="year_level" required>
                        <option value="">Select Year Level</option>
                        <option value="1stYearLevel">1st Year</option>
                        <option value="2ndYearLevel">2nd Year</option>
                        <option value="3rdYearLevel">3rd Year</option>
                        <option value="4thYearLevel">4th Year</option>
                        <option value="AllLevels">All Levels</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="semester">Semester <span class="required">*</span></label>
                    <select id="semester" name="semester" required>
                        <option value="">Select Semester</option>
                        <option value="First Semester">First Semester</option>
                        <option value="Second Semester">Second Semester</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="school_year">School Year <span class="required">*</span></label>
                    <select id="school_year" name="school_year" required>
                        <option value="">Select School Year</option>
                        <?php 
                        for($year = 2024; $year <= 2033; $year++) {
                            $schoolYear = $year . '-' . ($year + 1);
                            echo '<option value="' . $schoolYear . '">' . $schoolYear . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" onclick="closeAddEventModal()">Cancel</button>
                <button type="submit" name="add_event" class="btn btn-submit">
                    <i class="fa fa-save"></i> Add Event
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const totalEventsEl = document.getElementById('totalEvents');
const originalTotal = totalEventsEl ? parseInt(totalEventsEl.textContent) : 0;

// Load saved filters when page loads
window.addEventListener('DOMContentLoaded', function() {
    loadSavedFilters();
});

// Show success/error messages
<?php if (isset($_SESSION['success_message'])): ?>
Swal.fire({
    icon: 'success',
    title: 'Success!',
    text: '<?= $_SESSION['success_message'] ?>',
    timer: 2000,
    showConfirmButton: false
});
<?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
Swal.fire({
    icon: 'error',
    title: 'Error!',
    text: '<?= $_SESSION['error_message'] ?>',
    confirmButtonColor: '#0ea5e9'
});
<?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

function loadSavedFilters() {
    // Get saved semester filter
    const savedSemester = localStorage.getItem('semesterFilter');
    if (savedSemester) {
        document.getElementById('semesterFilter').value = savedSemester;
    }
    
    // Get saved school year filter
    const savedSchoolYear = localStorage.getItem('schoolYearFilter');
    if (savedSchoolYear) {
        document.getElementById('schoolYearFilter').value = savedSchoolYear;
    }
    
    // Apply filters
    filterEvents();
}

function saveSemesterFilter() {
    const semesterValue = document.getElementById('semesterFilter').value;
    localStorage.setItem('semesterFilter', semesterValue);
    filterEvents();
}

function saveSchoolYearFilter() {
    const schoolYearValue = document.getElementById('schoolYearFilter').value;
    localStorage.setItem('schoolYearFilter', schoolYearValue);
    filterEvents();
}

function filterEvents() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
    const semesterFilter = document.getElementById('semesterFilter').value;
    const schoolYearFilter = document.getElementById('schoolYearFilter').value;
    const eventCards = document.querySelectorAll('.event-card');
    const noResults = document.getElementById('noResults');
    let visibleCount = 0;
    
    eventCards.forEach(card => {
        const eventName = card.getAttribute('data-event-name') || '';
        const location = card.getAttribute('data-location') || '';
        const yearLevel = card.getAttribute('data-year-level') || '';
        const semester = card.getAttribute('data-semester') || '';
        const schoolYear = card.getAttribute('data-school-year') || '';
        
        // Check search term
        const matchesSearch = searchTerm === '' || 
                             eventName.includes(searchTerm) || 
                             location.includes(searchTerm) || 
                             yearLevel.includes(searchTerm);
        
        // Check semester filter
        const matchesSemester = semesterFilter === 'all' || semester === semesterFilter;
        
        // Check school year filter
        const matchesSchoolYear = schoolYearFilter === 'all' || schoolYear === schoolYearFilter;
        
        // Show card only if ALL filters match
        if (matchesSearch && matchesSemester && matchesSchoolYear) {
            card.classList.remove('hidden');
            visibleCount++;
        } else {
            card.classList.add('hidden');
        }
    });
    
    // Update total events counter
    if (totalEventsEl) {
        totalEventsEl.textContent = visibleCount;
    }
    
    // Show/hide no results message
    if (noResults) {
        if (visibleCount === 0) {
            noResults.style.display = 'block';
        } else {
            noResults.style.display = 'none';
        }
    }
}

function openAddEventModal() {
    document.getElementById('addEventModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeAddEventModal() {
    document.getElementById('addEventModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('addEventModal');
    if (event.target === modal) {
        closeAddEventModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAddEventModal();
    }
});

function validateForm() {
    const eventName = document.getElementById('event_name').value.trim();
    const eventDate = document.getElementById('event_date').value;
    const location = document.getElementById('location').value.trim();
    const timeSession = document.getElementById('time_session').value;
    const yearLevel = document.getElementById('year_level').value;
    const semester = document.getElementById('semester').value;
    const schoolYear = document.getElementById('school_year').value;
    
    if (!eventName || !eventDate || !location || !timeSession || !yearLevel || !semester || !schoolYear) {
        Swal.fire({
            icon: 'warning',
            title: 'Incomplete Form',
            text: 'Please fill in all required fields',
            confirmButtonColor: '#0ea5e9'
        });
        return false;
    }
    
    return true;
}

function viewEventAttendance(eventName, eventDate) {
    window.location.href = `view_attendance.php?event=${encodeURIComponent(eventName)}&date=${encodeURIComponent(eventDate)}`;
}
</script>
</body>
</html>
<?php $conn->close(); ?>
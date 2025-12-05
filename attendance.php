<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['usertype'], ['Social Manager', 'Senator',])) {
    header("Location: ../login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "csso");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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
    COUNT(a.attendance_id) as total_attendance,
    COUNT(DISTINCT a.students_id) as unique_students
FROM event e
LEFT JOIN attendance a ON e.event_Name = a.event_name AND e.event_Date = a.event_date
GROUP BY e.event_Name, e.event_Date, e.location, e.Time_Session, e.YearLevel
ORDER BY e.event_Date DESC";

$result = $conn->query($sql);

// Year Level Display Mapping
function formatYearLevel($yearLevel) {
    $mapping = [
        '1stYear' => '1st Year',
        '2ndYear' => '2nd Year',
        '3rdYear' => '3rd Year',
        '4thYear' => '4th Year',
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

/* Controls Section - Search Only */
.controls {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  margin-bottom: 20px;
}

/* Search Bar */
.search-container {
  position: relative;
  max-width: 100%;
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
  padding: 8px 12px;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

.view-attendance {
  background: #dbeafe;
  color: #1e40af;
}

.view-attendance:hover {
  background: #c7d2fe;
}

.manage-attendance {
  background: #fef3c7;
  color: #92400e;
}

.manage-attendance:hover {
  background: #fde68a;
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
  
  .event-stats {
    grid-template-columns: 1fr;
  }
  
  .event-info {
    grid-template-columns: 1fr;
  }
  
  .event-actions {
    flex-direction: column;
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

    <!-- Controls - Search Only -->
    <div class="controls">
        <div class="search-container">
            <input type="text" 
                   class="search-input" 
                   id="searchInput" 
                   placeholder="Search events by name, location, or year level..."
                   onkeyup="filterEvents()">
            <i class="fa fa-search search-icon"></i>
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
                                <span><?= htmlspecialchars($event['location'] ?: 'No location specified') ?></span>
                            </div>
                            <div class="event-detail-row">
                                <i class="fa fa-clock"></i>
                                <span><?= htmlspecialchars($event['Time_Session']) ?></span>
                            </div>
                            <div class="event-detail-row">
                                <i class="fa fa-users"></i>
                                <span><?= formatYearLevel($event['YearLevel']) ?></span>
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
                                <i class="fa fa-eye"></i> View
                            </button>
                            <button class="action-btn manage-attendance" onclick="event.stopPropagation(); manageEventAttendance('<?= htmlspecialchars($event['event_Name']) ?>', '<?= $event['event_Date'] ?>')">
                                <i class="fa fa-edit"></i> Manage
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            <div class="no-results" id="noResults">
                <i class="fa fa-search"></i>
                <h3>No Events Found</h3>
                <p>Try adjusting your search terms.</p>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa fa-calendar-times"></i>
                <h3>No Events Found</h3>
                <p>There are currently no events available.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
const totalEventsEl = document.getElementById('totalEvents');
const originalTotal = totalEventsEl ? parseInt(totalEventsEl.textContent) : 0;

function filterEvents() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
    const eventCards = document.querySelectorAll('.event-card');
    const noResults = document.getElementById('noResults');
    let visibleCount = 0;
    
    eventCards.forEach(card => {
        const eventName = card.getAttribute('data-event-name') || '';
        const location = card.getAttribute('data-location') || '';
        const yearLevel = card.getAttribute('data-year-level') || '';
        
        const matches = eventName.includes(searchTerm) || 
                       location.includes(searchTerm) || 
                       yearLevel.includes(searchTerm);
        
        if (matches) {
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
        if (visibleCount === 0 && searchTerm !== '') {
            noResults.style.display = 'block';
        } else {
            noResults.style.display = 'none';
        }
    }
}

function viewEventAttendance(eventName, eventDate) {
    const url = `view_attendance.php?event=${encodeURIComponent(eventName)}&date=${encodeURIComponent(eventDate)}`;
    if (window.top !== window.self) {
        window.top.location.href = url;
    } else {
        window.location.href = url;
    }
}

function manageEventAttendance(eventName, eventDate) {
    const url = `manage_attendance.php?event=${encodeURIComponent(eventName)}&date=${encodeURIComponent(eventDate)}`;
    if (window.top !== window.self) {
        window.top.location.href = url;
    } else {
        window.location.href = url;
    }
}
</script>
</body>
</html>
<?php $conn->close(); ?>
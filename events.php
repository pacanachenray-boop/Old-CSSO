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

// Helper function
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

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

// ===== SEARCH =====
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM event";
if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $sql .= " WHERE event_Name LIKE '%$search_safe%' OR location LIKE '%$search_safe%'";
}
$sql .= " ORDER BY event_Date DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Events | CSSO</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

/* Controls Section */
.controls {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  margin-bottom: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.filters {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

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
  min-width: 220px;
}

.filters input[type="text"]:focus {
  border-color: #0ea5e9;
  box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
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
  transform: scale(1.01);
}

tbody tr:last-child td {
  border-bottom: none;
}

.badge {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  display: inline-block;
}

.badge-time {
  background: #fef3c7;
  color: #92400e;
}

.badge-year {
  background: #e0e7ff;
  color: #3730a3;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 40px 20px;
  color: #64748b;
  font-size: 15px;
}

.empty-state i {
  font-size: 48px;
  color: #cbd5e1;
  margin-bottom: 12px;
}

/* Responsive */
@media (max-width: 768px) {
  .controls {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filters {
    flex-direction: column;
    width: 100%;
  }
  
  .filters input[type="text"] {
    width: 100%;
  }
  
  .table-container {
    overflow-x: auto;
  }
  
  table {
    min-width: 800px;
  }
}
</style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="page-header">
        <i class="fa-solid fa-calendar-alt"></i>
        <h2>Events Management</h2>
    </div>

    <!-- Controls - Search Only -->
    <div class="controls">
        <form method="get" class="filters">
            <input type="text" name="search" placeholder="Search event..." value="<?= htmlspecialchars($search) ?>">
            
            <button type="submit" class="btn search-btn">
                <i class="fa fa-search"></i> Search
            </button>
            
            <button type="button" class="btn clear-btn" onclick="window.location='events.php'">
                <i class="fa fa-rotate"></i> Clear
            </button>
        </form>
    </div>

    <!-- Table - No Actions Column -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Event Date</th>
                    <th>Location</th>
                    <th>Time Session</th>
                    <th>Year Level</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['event_Name']) ?></strong></td>
                    <td><?= date('M d, Y', strtotime($row['event_Date'])) ?></td>
                    <td><?= htmlspecialchars($row['location']) ?></td>
                    <td><span class="badge badge-time"><?= htmlspecialchars($row['Time_Session']) ?></span></td>
                    <td><span class="badge badge-year"><?= formatYearLevel($row['YearLevel']) ?></span></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="fa fa-calendar-times"></i>
                            <p>No events found.</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
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

$search = $_GET['search'] ?? '';
$course_filter = $_GET['course'] ?? 'BSIT';
$year_filter = $_GET['year'] ?? '';
$semester_filter = $_GET['semester'] ?? '';
$school_year_filter = $_GET['school_year'] ?? '';

$service_where = "";

if (!empty($school_year_filter) && !empty($semester_filter)) {
    $service_where = " AND r.school_year = '$school_year_filter' AND r.semester = '$semester_filter'";
} elseif (!empty($school_year_filter)) {
    $service_where = " AND r.school_year = '$school_year_filter'";
} elseif (!empty($semester_filter)) {
    $service_where = " AND r.semester = '$semester_filter'";
}

$sql = "SELECT 
    cs.service_id,
    sp.students_id,
    CONCAT(sp.LastName, ', ', sp.FirstName) as FullName,
    sp.Course,
    sp.Section,
    sp.YearLevel,
    cs.penalty_amount,
    cs.total_hours,
    cs.service_date,
    cs.status,
    cs.discount
FROM student_profile sp
INNER JOIN comservice cs ON sp.students_id = cs.students_id
INNER JOIN fines f ON cs.fines_id = f.fines_id
LEFT JOIN registration r ON f.registration_no = r.registration_no
WHERE 1=1 $service_where";

if (!empty($search)) {
    $search_term = $conn->real_escape_string($search);
    $sql .= " AND (sp.students_id LIKE '%$search_term%' 
              OR sp.LastName LIKE '%$search_term%' 
              OR sp.FirstName LIKE '%$search_term%')";
}

if (!empty($course_filter)) {
    $course_term = $conn->real_escape_string($course_filter);
    $sql .= " AND sp.Course = '$course_term'";
}

if (!empty($year_filter)) {
    $year_term = $conn->real_escape_string($year_filter);
    $sql .= " AND sp.YearLevel = '$year_term'";
}

$sql .= " ORDER BY cs.service_date DESC, sp.LastName ASC, sp.FirstName ASC";

$result = $conn->query($sql);

$courses_sql = "SELECT DISTINCT Course FROM student_profile WHERE Course IS NOT NULL ORDER BY Course";
$courses_result = $conn->query($courses_sql);

$stats_sql = "SELECT 
    COUNT(DISTINCT cs.students_id) as total_students,
    SUM(cs.discount) as total_discounts,
    COUNT(cs.service_id) as total_records
FROM comservice cs
INNER JOIN student_profile sp ON cs.students_id = sp.students_id
INNER JOIN fines f ON cs.fines_id = f.fines_id
LEFT JOIN registration r ON f.registration_no = r.registration_no
WHERE 1=1 $service_where";

if (!empty($search)) {
    $search_term = $conn->real_escape_string($search);
    $stats_sql .= " AND (sp.students_id LIKE '%$search_term%' 
                   OR sp.LastName LIKE '%$search_term%' 
                   OR sp.FirstName LIKE '%$search_term%')";
}

if (!empty($course_filter)) {
    $course_term = $conn->real_escape_string($course_filter);
    $stats_sql .= " AND sp.Course = '$course_term'";
}

if (!empty($year_filter)) {
    $year_term = $conn->real_escape_string($year_filter);
    $stats_sql .= " AND sp.YearLevel = '$year_term'";
}

$stats = $conn->query($stats_sql)->fetch_assoc();

function getCourseName($course) {
    $courseNames = [
        'BSIT' => 'Bachelor of Science in Information Technology',
        'BSCS' => 'Bachelor of Science in Computer Science',
        'BSA' => 'Bachelor of Science in Accountancy',
        'BSBA' => 'Bachelor of Science in Business Administration'
    ];
    return $courseNames[$course] ?? $course;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Service Records | CSSO</title>
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
.page-header {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 3px solid #ede9fe;
}
.page-header i {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
  color: white;
  padding: 14px;
  border-radius: 12px;
  font-size: 22px;
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
}
.page-header h2 {
  font-size: 26px;
  font-weight: 600;
  color: #1e3a5f;
  letter-spacing: -0.3px;
}
.stat-card {
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  border-left: 4px solid #8b5cf6;
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
  color: #8b5cf6;
}
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
  border-color: #8b5cf6;
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}
.filters input[type="text"] {
  min-width: 250px;
}
.filters select {
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23334155' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  padding-right: 36px;
}
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
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
}
.search-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
}
.clear-btn {
  background: #64748b;
  color: white;
}
.clear-btn:hover {
  background: #475569;
  transform: translateY(-2px);
}
.course-badge {
  background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
  padding: 12px 20px;
  border-radius: 10px;
  text-align: center;
  margin-bottom: 16px;
  border-left: 4px solid #0ea5e9;
}
.course-badge h3 {
  font-size: 17px;
  color: #0c4a6e;
  font-weight: 600;
  margin: 0;
}
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
  min-width: 1200px;
}
thead {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
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
  background: #faf5ff;
}
tbody tr:last-child td {
  border-bottom: none;
}
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
.penalty-badge {
  background: #fee2e2;
  color: #991b1b;
  font-weight: 700;
  font-size: 14px;
}
.hours-badge {
  background: #ede9fe;
  color: #6b21a8;
  font-weight: 700;
  font-size: 14px;
}
.discount-badge {
  background: #fef3c7;
  color: #92400e;
  font-weight: 700;
  font-size: 14px;
}
.status-badge {
  padding: 5px 10px;
  border-radius: 6px;
  font-weight: 600;
  font-size: 12px;
  display: inline-block;
}
.status-ongoing {
  background: #fef3c7;
  color: #92400e;
}
.status-completed {
  background: #d1fae5;
  color: #065f46;
}
.action-btn {
  padding: 6px 12px;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.btn-pay {
  background: #8b5cf6;
  color: white;
}
.btn-pay:hover {
  background: #7c3aed;
  transform: translateY(-1px);
}
.btn-pay:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
  transform: none;
}
.action-btn {
  padding: 6px 12px;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.btn-pay {
  background: #8b5cf6;
  color: white;
}
.btn-pay:hover {
  background: #7c3aed;
  transform: translateY(-1px);
}
.btn-pay:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
  transform: none;
}
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
</style>
</head>
<body>
<div class="container">
    <div class="page-header">
        <i class="fa-solid fa-hand-holding-heart"></i>
        <h2>Service Records</h2>
    </div>
    <div style="margin-bottom: 20px;">
        <div class="stat-card" style="max-width: 300px;">
            <h4>Total Discounts</h4>
            <p>₱<?= number_format($stats['total_discounts'] ?? 0, 2) ?></p>
        </div>
    </div>
    <div class="controls">
        <form method="get" class="filters" id="filterForm">
            <select name="course" onchange="this.form.submit()">
                <?php 
                $courses_result->data_seek(0);
                while($course = $courses_result->fetch_assoc()): 
                ?>
                    <option value="<?= htmlspecialchars($course['Course']) ?>" 
                            <?= $course_filter == $course['Course'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($course['Course']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <select name="year" onchange="this.form.submit()">
                <option value="">All Year Levels</option>
                <option value="1stYear" <?= $year_filter == '1stYear' ? 'selected' : '' ?>>1st Year</option>
                <option value="2ndYear" <?= $year_filter == '2ndYear' ? 'selected' : '' ?>>2nd Year</option>
                <option value="3rdYear" <?= $year_filter == '3rdYear' ? 'selected' : '' ?>>3rd Year</option>
                <option value="4thYear" <?= $year_filter == '4thYear' ? 'selected' : '' ?>>4th Year</option>
            </select>
            <select name="semester" id="semesterFilter" onchange="saveSemesterFilter(); this.form.submit()">
                <option value="">All Semesters</option>
                <option value="First Semester" <?= $semester_filter === 'First Semester' ? 'selected' : '' ?>>First Semester</option>
                <option value="Second Semester" <?= $semester_filter === 'Second Semester' ? 'selected' : '' ?>>Second Semester</option>
            </select>
            <select name="school_year" id="schoolYearFilter" onchange="saveSchoolYearFilter(); this.form.submit()">
                <option value="">All School Years</option>
                <?php 
                for($year = 2023; $year <= 2033; $year++) {
                    $schoolYear = $year . '-' . ($year + 1);
                    $selected = ($school_year_filter === $schoolYear) ? 'selected' : '';
                    echo '<option value="' . $schoolYear . '" ' . $selected . '>' . $schoolYear . '</option>';
                }
                ?>
            </select>
            <input type="text" name="search" placeholder="Search by Student ID or Name..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn search-btn">
                <i class="fa fa-search"></i> Search
            </button>
            <button type="button" class="btn clear-btn" onclick="clearFilters()">
                <i class="fa fa-rotate"></i> Clear
            </button>
        </form>
    </div>
    <div class="course-badge">
        <h3><?= htmlspecialchars(getCourseName($course_filter)) ?></h3>
    </div>
    <div class="table-container">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Course</th>
                        <th>Section</th>
                        <th>Year Level</th>
                        <th>Penalty Amount</th>
                        <th>Total Hours</th>
                        <th>Service Date</th>
                        <th>Status</th>
                        <th>Discount</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['students_id']) ?></strong></td>
                        <td><?= htmlspecialchars($row['FullName']) ?></td>
                        <td><span class="badge badge-course"><?= htmlspecialchars($row['Course']) ?></span></td>
                        <td><?= htmlspecialchars($row['Section'] ?? 'N/A') ?></td>
                        <td><span class="badge badge-year"><?= htmlspecialchars($row['YearLevel']) ?></span></td>
                        <td><span class="penalty-badge">₱<?= number_format($row['penalty_amount'], 2) ?></span></td>
                        <td><span class="hours-badge"><?= htmlspecialchars($row['total_hours']) ?> hrs</span></td>
                        <td><?= date('M d, Y', strtotime($row['service_date'])) ?></td>
                        <td>
                            <?php 
                            $statusClass = $row['status'] === 'Completed' ? 'status-completed' : 'status-ongoing';
                            ?>
                            <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($row['status']) ?></span>
                        </td>
                        <td><span class="discount-badge">₱<?= number_format($row['discount'], 2) ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10">
                            <div class="empty-state">
                                <i class="fa fa-hand-holding-heart"></i>
                                <h3>No Service Records Found</h3>
                                <p>No community service records matching your search criteria.</p>
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
window.addEventListener('DOMContentLoaded', function() {
    loadSavedFilters();
});

function loadSavedFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    const savedSemester = localStorage.getItem('serviceSemesterFilter');
    const semesterSelect = document.getElementById('semesterFilter');
    if (savedSemester && savedSemester !== 'all' && semesterSelect) {
        if (!urlParams.has('semester')) {
            semesterSelect.value = savedSemester;
            if (semesterSelect.value && !window.location.search.includes('semester=')) {
                urlParams.set('semester', savedSemester);
                window.location.search = urlParams.toString();
                return;
            }
        }
    }
    const savedSchoolYear = localStorage.getItem('serviceSchoolYearFilter');
    const schoolYearSelect = document.getElementById('schoolYearFilter');
    if (savedSchoolYear && savedSchoolYear !== 'all' && schoolYearSelect) {
        if (!urlParams.has('school_year')) {
            schoolYearSelect.value = savedSchoolYear;
            if (schoolYearSelect.value && !window.location.search.includes('school_year=')) {
                urlParams.set('school_year', savedSchoolYear);
                window.location.search = urlParams.toString();
                return;
            }
        }
    }
}

function saveSemesterFilter() {
    const semesterValue = document.getElementById('semesterFilter').value;
    if (semesterValue) {
        localStorage.setItem('serviceSemesterFilter', semesterValue);
    } else {
        localStorage.setItem('serviceSemesterFilter', 'all');
    }
}

function saveSchoolYearFilter() {
    const schoolYearValue = document.getElementById('schoolYearFilter').value;
    if (schoolYearValue) {
        localStorage.setItem('serviceSchoolYearFilter', schoolYearValue);
    } else {
        localStorage.setItem('serviceSchoolYearFilter', 'all');
    }
}

function clearFilters() {
    localStorage.removeItem('serviceSemesterFilter');
    localStorage.removeItem('serviceSchoolYearFilter');
    window.location.href = 'service.php';
}
</script>
</body>
</html>
<?php $conn->close(); ?>
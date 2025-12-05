<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "csso");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ GET FILTER VALUES FROM URL
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : '';
$school_year_filter = isset($_GET['school_year']) ? $_GET['school_year'] : '';

// ✅ BUILD WHERE CLAUSE BASED ON FILTERS
$whereClause = "WHERE 1=1";

if (!empty($school_year_filter) && !empty($semester_filter)) {
    $whereClause .= " AND r.school_year = '$school_year_filter' AND r.semester = '$semester_filter'";
} elseif (!empty($school_year_filter)) {
    $whereClause .= " AND r.school_year = '$school_year_filter'";
} elseif (!empty($semester_filter)) {
    $whereClause .= " AND r.semester = '$semester_filter'";
}

// ✅ GET DASHBOARD DATA WITH FILTERS
$totalStudents = $conn->query("SELECT COUNT(DISTINCT r.students_id) FROM registration r $whereClause")->fetch_row()[0] ?? 0;
$registrationCollected = $conn->query("SELECT IFNULL(SUM(amount),0) FROM registration r $whereClause AND payment_status='Paid'")->fetch_row()[0] ?? 0;
$finesCollected = $conn->query("SELECT IFNULL(SUM(penalty_amount),0) FROM fines_payments WHERE payment_status='Paid'")->fetch_row()[0] ?? 0;
$totalIncome = $registrationCollected + $finesCollected;

// Get recent registrations with filters
$recent = $conn->query("SELECT sp.FirstName, sp.LastName, r.registration_date 
                        FROM registration r 
                        JOIN student_profile sp ON r.students_id = sp.students_id 
                        $whereClause
                        ORDER BY r.registration_date DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | CSSO</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --bg-primary: #f0f4f8;
    --bg-secondary: #ffffff;
    --text-primary: #1e3a5f;
    --text-secondary: #334155;
    --text-muted: #64748b;
    --border-color: #f1f5f9;
    --hover-bg: #f0f9ff;
    --shadow-sm: 0 3px 12px rgba(0, 0, 0, 0.06);
    --shadow-md: 0 8px 20px rgba(0, 0, 0, 0.12);
}

body.dark-mode {
    --bg-primary: #0f172a;
    --bg-secondary: #1e293b;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-muted: #94a3b8;
    --border-color: #334155;
    --hover-bg: #334155;
    --shadow-sm: 0 3px 12px rgba(0, 0, 0, 0.3);
    --shadow-md: 0 8px 20px rgba(0, 0, 0, 0.5);
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--bg-primary);
    color: var(--text-primary);
    transition: background-color 0.3s ease, color 0.3s ease;
}

.container {
    padding: 24px;
    max-width: 1400px;
    margin: 0 auto;
}

/* ✅ FILTER SECTION */
.filter-section {
    background: var(--bg-secondary);
    border-radius: 12px;
    padding: 20px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}

.filter-section label {
    font-weight: 600;
    color: var(--text-primary);
    font-size: 14px;
}

.filter-section select {
    padding: 10px 40px 10px 14px;
    border-radius: 8px;
    border: 2px solid var(--border-color);
    font-size: 14px;
    outline: none;
    transition: all 0.3s ease;
    background: var(--bg-secondary);
    color: var(--text-primary);
    font-weight: 500;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23334155' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
}

.filter-section select:focus {
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
}

/* Stats Cards Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: var(--bg-secondary);
    border-radius: 12px;
    padding: 24px;
    box-shadow: var(--shadow-sm);
    display: flex;
    align-items: center;
    gap: 18px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    transition: width 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-md);
}

.stat-card:hover::before {
    width: 100%;
    opacity: 0.05;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    flex-shrink: 0;
}

.stat-content h3 {
    font-size: 13px;
    color: var(--text-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
    transition: color 0.3s ease;
}

.stat-content .stat-value {
    font-size: 26px;
    font-weight: 700;
    color: var(--text-primary);
    transition: color 0.3s ease;
}

/* Color Schemes for Cards */
.stat-card.blue::before { background: #0ea5e9; }
.stat-card.blue .stat-icon {
    background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
    color: #0284c7;
}

.stat-card.green::before { background: #10b981; }
.stat-card.green .stat-icon {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #059669;
}

.stat-card.orange::before { background: #f59e0b; }
.stat-card.orange .stat-icon {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #d97706;
}

.stat-card.purple::before { background: #8b5cf6; }
.stat-card.purple .stat-icon {
    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    color: #7c3aed;
}

/* Bottom Section Layout */
.bottom-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 20px;
}

.panel {
    background: var(--bg-secondary);
    border-radius: 12px;
    padding: 24px;
    box-shadow: var(--shadow-sm);
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

.panel-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--border-color);
    transition: border-color 0.3s ease;
}

.panel-header i {
    color: #0ea5e9;
    font-size: 20px;
}

.panel-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
    transition: color 0.3s ease;
}

/* Recent Table */
.recent-table {
    width: 100%;
    border-collapse: collapse;
}

.recent-table thead {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
}

.recent-table th {
    padding: 12px;
    text-align: left;
    color: white;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.recent-table td {
    padding: 14px 12px;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-secondary);
    font-size: 14px;
    transition: color 0.3s ease, border-color 0.3s ease;
}

.recent-table tbody tr {
    transition: all 0.2s ease;
}

.recent-table tbody tr:hover {
    background: var(--hover-bg);
}

.recent-table tbody tr:last-child td {
    border-bottom: none;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-muted);
    transition: color 0.3s ease;
}

.empty-state i {
    font-size: 48px;
    color: var(--text-muted);
    margin-bottom: 12px;
    opacity: 0.5;
}

/* Calendar */
.calendar-container {
    text-align: center;
}

.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.calendar-header h4 {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    transition: color 0.3s ease;
}

.calendar-nav {
    display: flex;
    gap: 8px;
}

.calendar-nav button {
    background: var(--border-color);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    cursor: pointer;
    color: var(--text-muted);
    font-size: 14px;
    transition: all 0.2s ease;
}

.calendar-nav button:hover {
    background: #0ea5e9;
    color: white;
}

.calendar-table {
    width: 100%;
    border-collapse: collapse;
}

.calendar-table th {
    color: var(--text-muted);
    font-weight: 600;
    font-size: 12px;
    padding: 10px 0;
    text-transform: uppercase;
    transition: color 0.3s ease;
}

.calendar-table td {
    padding: 10px;
    text-align: center;
    color: var(--text-secondary);
    font-size: 14px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.calendar-table td:hover:not(.empty-cell) {
    background: #e0f2fe;
    color: #0284c7;
    transform: scale(1.1);
}

body.dark-mode .calendar-table td:hover:not(.empty-cell) {
    background: rgba(14, 165, 233, 0.2);
    color: #38bdf8;
}

.calendar-table .today {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: white;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
}

.calendar-table .empty-cell {
    cursor: default;
}

.calendar-table .other-month {
    color: var(--text-muted);
    opacity: 0.4;
}

/* Responsive */
@media (max-width: 1024px) {
    .bottom-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 22px;
    }
    
    .filter-section {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-section select {
        width: 100%;
    }
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.stat-card {
    animation: fadeInUp 0.5s ease forwards;
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }
</style>
</head>
<body>
<div class="container">
    <!-- ✅ FILTER SECTION -->
    <div class="filter-section">
        <form method="GET" id="filterForm" style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap; width: 100%;">
            <label>Filter by:</label>
            
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
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="stat-content">
                <h3>Total Students</h3>
                <div class="stat-value"><?= number_format($totalStudents) ?></div>
            </div>
        </div>

        <div class="stat-card green">
            <div class="stat-icon">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <div class="stat-content">
                <h3>Registration Collected</h3>
                <div class="stat-value">₱<?= number_format($registrationCollected, 2) ?></div>
            </div>
        </div>

        <div class="stat-card orange">
            <div class="stat-icon">
                <i class="fa-solid fa-gavel"></i>
            </div>
            <div class="stat-content">
                <h3>Fines Collected</h3>
                <div class="stat-value">₱<?= number_format($finesCollected, 2) ?></div>
            </div>
        </div>

        <div class="stat-card purple">
            <div class="stat-icon">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="stat-content">
                <h3>Total Income</h3>
                <div class="stat-value">₱<?= number_format($totalIncome, 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Bottom Grid -->
    <div class="bottom-grid">
        <!-- Recent Registrations -->
        <div class="panel">
            <div class="panel-header">
                <i class="fa-solid fa-user-plus"></i>
                <h3>Recent Registered Students</h3>
            </div>
            <table class="recent-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent && $recent->num_rows > 0): ?>
                        <?php while($r = $recent->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['FirstName'] . ' ' . $r['LastName']) ?></strong></td>
                                <td><?= date('M d, Y', strtotime($r['registration_date'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">
                                <div class="empty-state">
                                    <i class="fa-solid fa-inbox"></i>
                                    <p>No recent registrations found.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Calendar -->
        <div class="panel">
            <div class="panel-header">
                <i class="fa-solid fa-calendar-days"></i>
                <h3>Calendar</h3>
            </div>
            <div class="calendar-container" id="calendar"></div>
        </div>
    </div>
</div>

<script>
// ✅ LOAD SAVED FILTERS
window.addEventListener('DOMContentLoaded', function() {
    loadSavedFilters();
});

function loadSavedFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    
    const savedSemester = localStorage.getItem('userDashboardSemesterFilter');
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
    
    const savedSchoolYear = localStorage.getItem('userDashboardSchoolYearFilter');
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
        localStorage.setItem('userDashboardSemesterFilter', semesterValue);
    } else {
        localStorage.setItem('userDashboardSemesterFilter', 'all');
    }
}

function saveSchoolYearFilter() {
    const schoolYearValue = document.getElementById('schoolYearFilter').value;
    if (schoolYearValue) {
        localStorage.setItem('userDashboardSchoolYearFilter', schoolYearValue);
    } else {
        localStorage.setItem('userDashboardSchoolYearFilter', 'all');
    }
}

// Check parent window for dark mode and sync
function syncDarkMode() {
    try {
        if (window.parent && window.parent.document.body.classList.contains('dark-mode')) {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
    } catch (e) {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
        }
    }
}

window.addEventListener('message', function(event) {
    if (event.data.type === 'themeChange') {
        if (event.data.theme === 'dark') {
            document.body.classList.add('dark-mode');
        } else {
            document.body.classList.remove('dark-mode');
        }
    }
});

syncDarkMode();
setInterval(syncDarkMode, 500);

// Enhanced Calendar Generator
let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();

function generateCalendar(month = currentMonth, year = currentYear) {
    const today = new Date();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const prevLastDay = new Date(year, month, 0);

    const months = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];

    let html = `
        <div class="calendar-header">
            <button onclick="changeMonth(-1)"><i class="fa fa-chevron-left"></i></button>
            <h4>${months[month]} ${year}</h4>
            <button onclick="changeMonth(1)"><i class="fa fa-chevron-right"></i></button>
        </div>
        <table class="calendar-table">
            <thead>
                <tr>
                    <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th>
                    <th>Thu</th><th>Fri</th><th>Sat</th>
                </tr>
            </thead>
            <tbody>
    `;

    let day = 1;
    let nextMonthDay = 1;
    
    for (let week = 0; week < 6; week++) {
        html += '<tr>';
        for (let dayOfWeek = 0; dayOfWeek < 7; dayOfWeek++) {
            if (week === 0 && dayOfWeek < firstDay.getDay()) {
                const prevDay = prevLastDay.getDate() - (firstDay.getDay() - dayOfWeek - 1);
                html += `<td class="empty-cell other-month">${prevDay}</td>`;
            } else if (day > lastDay.getDate()) {
                html += `<td class="empty-cell other-month">${nextMonthDay++}</td>`;
            } else {
                const isToday = (day === today.getDate() && month === today.getMonth() && year === today.getFullYear());
                html += `<td class="${isToday ? 'today' : ''}">${day}</td>`;
                day++;
            }
        }
        html += '</tr>';
        if (day > lastDay.getDate()) break;
    }

    html += '</tbody></table>';
    document.getElementById('calendar').innerHTML = html;
}

function changeMonth(direction) {
    currentMonth += direction;
    if (currentMonth > 11) {
        currentMonth = 0;
        currentYear++;
    } else if (currentMonth < 0) {
        currentMonth = 11;
        currentYear--;
    }
    generateCalendar(currentMonth, currentYear);
}

generateCalendar();
</script>
</body>
</html>
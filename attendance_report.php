<?php
// Attendance Report - By Semester/School Year

$where_clause = "WHERE 1=1" . buildWhereClause('r');

// Add year level filter
if (!empty($year_level_filter)) {
    $where_clause .= " AND sp.YearLevel = '" . $conn->real_escape_string($year_level_filter) . "'";
}

// Add section filter
if (!empty($section_filter)) {
    $where_clause .= " AND sp.Section = '" . $conn->real_escape_string($section_filter) . "'";
}

// Get Attendance Summary
$att_summary_sql = "SELECT 
    sp.Course,
    sp.YearLevel,
    sp.Section,
    COUNT(DISTINCT a.students_id) as total_students,
    COUNT(a.attendance_id) as total_attendance,
    COUNT(CASE WHEN a.amLogin IS NOT NULL THEN 1 END) as am_present,
    COUNT(CASE WHEN a.pmLogin IS NOT NULL THEN 1 END) as pm_present,
    COUNT(CASE WHEN a.ExcuseLetter = 'Yes' THEN 1 END) as with_excuse,
    SUM(a.TotalPenalty) as total_penalties
FROM attendance a
INNER JOIN student_profile sp ON a.students_id = sp.students_id
LEFT JOIN registration r ON a.registration_no = r.registration_no
$where_clause
GROUP BY sp.Course, sp.YearLevel, sp.Section
ORDER BY sp.Course, sp.YearLevel, sp.Section";

$att_summary_result = $conn->query($att_summary_sql);

// Get Event-wise Attendance
$event_att_sql = "SELECT 
    e.event_Name,
    e.event_Date,
    e.location,
    e.Time_Session,
    e.YearLevel as event_year_level,
    r.semester,
    r.school_year,
    COUNT(DISTINCT a.students_id) as total_attended,
    COUNT(CASE WHEN a.amLogin IS NOT NULL THEN 1 END) as am_attended,
    COUNT(CASE WHEN a.pmLogin IS NOT NULL THEN 1 END) as pm_attended,
    COUNT(CASE WHEN a.ExcuseLetter = 'Yes' THEN 1 END) as with_excuse,
    SUM(a.TotalPenalty) as total_penalties
FROM event e
LEFT JOIN attendance a ON e.event_Name = a.event_name
LEFT JOIN registration r ON a.registration_no = r.registration_no
LEFT JOIN student_profile sp ON a.students_id = sp.students_id
WHERE 1=1" . buildWhereClause('r');

if (!empty($year_level_filter)) {
    $event_att_sql .= " AND (e.YearLevel = 'AllLevels' OR e.YearLevel = '" . $conn->real_escape_string($year_level_filter) . "Level')";
}

$event_att_sql .= " GROUP BY e.event_Name, e.event_Date, e.location, e.Time_Session, e.YearLevel, r.semester, r.school_year
ORDER BY e.event_Date DESC";

$event_att_result = $conn->query($event_att_sql);

// Calculate totals
$grand_total_students = 0;
$grand_total_attendance = 0;
$grand_am_present = 0;
$grand_pm_present = 0;
$grand_with_excuse = 0;
$grand_penalties = 0;

if ($att_summary_result) {
    while($row = $att_summary_result->fetch_assoc()) {
        $grand_total_students += $row['total_students'];
        $grand_total_attendance += $row['total_attendance'];
        $grand_am_present += $row['am_present'];
        $grand_pm_present += $row['pm_present'];
        $grand_with_excuse += $row['with_excuse'];
        $grand_penalties += $row['total_penalties'];
    }
}
?>

<div class="report-header">
    <h3>Attendance Report</h3>
    <p>
        <?php 
        if (!empty($semester_filter)) echo $semester_filter . ' • ';
        if (!empty($school_year_filter)) echo 'School Year: ' . $school_year_filter . ' • ';
        if (!empty($year_level_filter)) echo $year_level_filter . ' • ';
        if (!empty($section_filter)) echo $section_filter;
        if (empty($semester_filter) && empty($school_year_filter)) echo 'Overall Attendance';
        ?>
    </p>
</div>

<!-- Summary Cards -->
<div class="summary-grid">
    <div class="summary-card">
        <h4>Total Students</h4>
        <div class="value"><?= number_format($grand_total_students) ?></div>
    </div>
    <div class="summary-card">
        <h4>Total Attendance Records</h4>
        <div class="value"><?= number_format($grand_total_attendance) ?></div>
    </div>
    <div class="summary-card success">
        <h4>AM Session Present</h4>
        <div class="value"><?= number_format($grand_am_present) ?></div>
    </div>
    <div class="summary-card success">
        <h4>PM Session Present</h4>
        <div class="value"><?= number_format($grand_pm_present) ?></div>
    </div>
    <div class="summary-card warning">
        <h4>With Excuse Letter</h4>
        <div class="value"><?= number_format($grand_with_excuse) ?></div>
    </div>
    <div class="summary-card danger">
        <h4>Total Penalties</h4>
        <div class="value">₱<?= number_format($grand_penalties, 2) ?></div>
    </div>
</div>

<!-- Attendance by Year Level/Section -->
<?php if ($att_summary_result && $att_summary_result->num_rows > 0): ?>
    <div class="section-header">
        <h4>Attendance by Year Level & Section</h4>
    </div>
    
    <table class="report-table">
        <thead>
            <tr>
                <th>Course</th>
                <th>Year Level</th>
                <th>Section</th>
                <th>Students</th>
                <th>Total Records</th>
                <th>AM Present</th>
                <th>PM Present</th>
                <th>With Excuse</th>
                <th>Penalties</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $att_summary_result->data_seek(0);
            while($row = $att_summary_result->fetch_assoc()): 
            ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['Course']) ?></strong></td>
                    <td><?= htmlspecialchars($row['YearLevel']) ?></td>
                    <td><?= htmlspecialchars($row['Section']) ?></td>
                    <td><?= number_format($row['total_students']) ?></td>
                    <td><?= number_format($row['total_attendance']) ?></td>
                    <td><span class="badge badge-paid"><?= number_format($row['am_present']) ?></span></td>
                    <td><span class="badge badge-paid"><?= number_format($row['pm_present']) ?></span></td>
                    <td><span class="badge badge-partial"><?= number_format($row['with_excuse']) ?></span></td>
                    <td><strong style="color: #ef4444;">₱<?= number_format($row['total_penalties'], 2) ?></strong></td>
                </tr>
            <?php endwhile; ?>
            
            <tr class="total-row">
                <td colspan="3"><strong>TOTAL</strong></td>
                <td><strong><?= number_format($grand_total_students) ?></strong></td>
                <td><strong><?= number_format($grand_total_attendance) ?></strong></td>
                <td><strong><?= number_format($grand_am_present) ?></strong></td>
                <td><strong><?= number_format($grand_pm_present) ?></strong></td>
                <td><strong><?= number_format($grand_with_excuse) ?></strong></td>
                <td><strong>₱<?= number_format($grand_penalties, 2) ?></strong></td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>

<!-- Event-wise Attendance -->
<?php if ($event_att_result && $event_att_result->num_rows > 0): ?>
    <div class="section-header" style="margin-top: 40px;">
        <h4>Event-wise Attendance</h4>
    </div>
    
    <table class="report-table">
        <thead>
            <tr>
                <th>Event Name</th>
                <th>Date</th>
                <th>Semester</th>
                <th>School Year</th>
                <th>Location</th>
                <th>Session</th>
                <th>Year Level</th>
                <th>Total Attended</th>
                <th>AM</th>
                <th>PM</th>
                <th>Excused</th>
                <th>Penalties</th>
            </tr>
        </thead>
        <tbody>
            <?php while($event = $event_att_result->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($event['event_Name']) ?></strong></td>
                    <td><?= date('M d, Y', strtotime($event['event_Date'])) ?></td>
                    <td><?= htmlspecialchars($event['semester'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($event['school_year'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($event['location']) ?></td>
                    <td><span class="badge badge-paid"><?= htmlspecialchars($event['Time_Session']) ?></span></td>
                    <td><?= htmlspecialchars($event['event_year_level']) ?></td>
                    <td><strong><?= number_format($event['total_attended']) ?></strong></td>
                    <td><?= number_format($event['am_attended']) ?></td>
                    <td><?= number_format($event['pm_attended']) ?></td>
                    <td><?= number_format($event['with_excuse']) ?></td>
                    <td><strong style="color: #ef4444;">₱<?= number_format($event['total_penalties'], 2) ?></strong></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if (!$att_summary_result || $att_summary_result->num_rows == 0): ?>
    <div class="empty-state">
        <i class="fa fa-inbox"></i>
        <h3>No Attendance Records Found</h3>
        <p>No attendance records match your filter criteria</p>
    </div>
<?php endif; ?>
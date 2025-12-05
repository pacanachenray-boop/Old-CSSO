<?php
// Individual Statement Report - shows individual student's complete record

$where_clause = "WHERE 1=1" . buildWhereClause('r');

// Add student ID filter if provided
if (!empty($student_id_filter)) {
    $where_clause .= " AND sp.students_id = '" . $conn->real_escape_string($student_id_filter) . "'";
}

// Add year level filter
if (!empty($year_level_filter)) {
    $where_clause .= " AND sp.YearLevel = '" . $conn->real_escape_string($year_level_filter) . "'";
}

// Add section filter
if (!empty($section_filter)) {
    $where_clause .= " AND sp.Section = '" . $conn->real_escape_string($section_filter) . "'";
}

// Get students with their records
$sql = "SELECT DISTINCT
    sp.students_id,
    sp.FirstName,
    sp.LastName,
    sp.MI,
    sp.Course,
    sp.YearLevel,
    sp.Section
FROM student_profile sp
LEFT JOIN registration r ON sp.students_id = r.students_id
$where_clause
ORDER BY sp.LastName, sp.FirstName";

$students_result = $conn->query($sql);
?>

<div class="report-header">
    <h3>Individual Statement of Records</h3>
    <p>
        <?php 
        if (!empty($semester_filter)) echo $semester_filter . ' • ';
        if (!empty($school_year_filter)) echo 'School Year: ' . $school_year_filter . ' • ';
        if (!empty($year_level_filter)) echo $year_level_filter . ' • ';
        if (!empty($section_filter)) echo $section_filter;
        if (empty($semester_filter) && empty($school_year_filter)) echo 'Overall Records';
        ?>
    </p>
</div>

<?php if ($students_result && $students_result->num_rows > 0): ?>
    <?php while($student = $students_result->fetch_assoc()): ?>
        <?php
        $student_id = $student['students_id'];
        $fullname = trim($student['FirstName'] . ' ' . ($student['MI'] ? $student['MI'] . '. ' : '') . $student['LastName']);
        
        // Get registration records
        $reg_where = "WHERE r.students_id = $student_id" . buildWhereClause('r');
        $reg_sql = "SELECT r.*, r.amount as paid_amount
                    FROM registration r
                    $reg_where
                    ORDER BY r.registration_date DESC";
        $reg_result = $conn->query($reg_sql);
        
        // Get attendance records
        $att_where = "WHERE a.students_id = $student_id" . buildWhereClause('r');
        $att_sql = "SELECT a.*, e.event_name, e.event_Date, r.semester, r.school_year
                    FROM attendance a
                    LEFT JOIN event e ON a.event_name = e.event_Name
                    LEFT JOIN registration r ON a.registration_no = r.registration_no
                    $att_where
                    ORDER BY a.event_date DESC";
        $att_result = $conn->query($att_sql);
        
        // Get fines records
        $fines_where = "WHERE f.students_id = $student_id" . buildWhereClause('r');
        $fines_sql = "SELECT f.*, r.semester, r.school_year
                      FROM fines f
                      LEFT JOIN registration r ON f.registration_no = r.registration_no
                      $fines_where
                      ORDER BY f.event_date DESC";
        $fines_result = $conn->query($fines_sql);
        
        // Get payments
        $payments_sql = "SELECT fp.*, f.event_name, r.semester, r.school_year
                        FROM fines_payments fp
                        JOIN fines f ON fp.fines_id = f.fines_id
                        LEFT JOIN registration r ON f.registration_no = r.registration_no
                        WHERE fp.students_id = $student_id" . buildWhereClause('r') . "
                        ORDER BY fp.payment_date DESC";
        $payments_result = $conn->query($payments_sql);
        
        // Calculate totals
        $total_reg_paid = 0;
        $total_penalties = 0;
        $total_paid_fines = 0;
        $total_unpaid_fines = 0;
        
        if ($reg_result) {
            $reg_result->data_seek(0);
            while($r = $reg_result->fetch_assoc()) {
                if ($r['payment_status'] == 'Paid') $total_reg_paid += $r['paid_amount'];
            }
        }
        
        if ($fines_result) {
            $fines_result->data_seek(0);
            while($f = $fines_result->fetch_assoc()) {
                $total_penalties += $f['PenaltyAmount'];
            }
        }
        
        if ($payments_result) {
            while($p = $payments_result->fetch_assoc()) {
                $total_paid_fines += $p['payment_amount'];
            }
        }
        
        $total_unpaid_fines = $total_penalties;
        ?>
        
        <div class="section-header">
            <h4><?= htmlspecialchars($fullname) ?> - <?= htmlspecialchars($student_id) ?> | <?= htmlspecialchars($student['Course']) ?> <?= htmlspecialchars($student['YearLevel']) ?> - <?= htmlspecialchars($student['Section']) ?></h4>
        </div>
        
        <!-- Summary Cards for this student -->
        <div class="summary-grid">
            <div class="summary-card">
                <h4>Registration Paid</h4>
                <div class="value">₱<?= number_format($total_reg_paid, 2) ?></div>
            </div>
            <div class="summary-card warning">
                <h4>Total Penalties</h4>
                <div class="value">₱<?= number_format($total_penalties, 2) ?></div>
            </div>
            <div class="summary-card success">
                <h4>Fines Paid</h4>
                <div class="value">₱<?= number_format($total_paid_fines, 2) ?></div>
            </div>
            <div class="summary-card danger">
                <h4>Unpaid Balance</h4>
                <div class="value">₱<?= number_format($total_unpaid_fines, 2) ?></div>
            </div>
        </div>
        
        <!-- Registration Records -->
        <?php if ($reg_result && $reg_result->num_rows > 0): ?>
            <h5 style="margin: 20px 0 12px 0; color: #0f172a; font-size: 16px;">Registration Records</h5>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Registration No</th>
                        <th>Date</th>
                        <th>Semester</th>
                        <th>School Year</th>
                        <th>Amount</th>
                        <th>Payment Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $reg_result->data_seek(0);
                    while($r = $reg_result->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['registration_no']) ?></strong></td>
                            <td><?= date('M d, Y', strtotime($r['registration_date'])) ?></td>
                            <td><?= htmlspecialchars($r['semester']) ?></td>
                            <td><?= htmlspecialchars($r['school_year']) ?></td>
                            <td>₱<?= number_format($r['paid_amount'], 2) ?></td>
                            <td><?= htmlspecialchars($r['payment_type']) ?></td>
                            <td>
                                <span class="badge badge-<?= $r['payment_status'] == 'Paid' ? 'paid' : ($r['payment_status'] == 'Unpaid' ? 'unpaid' : 'partial') ?>">
                                    <?= htmlspecialchars($r['payment_status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <!-- Attendance Records -->
        <?php if ($att_result && $att_result->num_rows > 0): ?>
            <h5 style="margin: 20px 0 12px 0; color: #0f172a; font-size: 16px;">Attendance Records</h5>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Date</th>
                        <th>Semester</th>
                        <th>School Year</th>
                        <th>AM Login</th>
                        <th>AM Logout</th>
                        <th>PM Login</th>
                        <th>PM Logout</th>
                        <th>Excuse Letter</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($a = $att_result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($a['event_name']) ?></strong></td>
                            <td><?= date('M d, Y', strtotime($a['event_date'])) ?></td>
                            <td><?= htmlspecialchars($a['semester'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($a['school_year'] ?? 'N/A') ?></td>
                            <td><?= $a['amLogin'] ? date('h:i A', strtotime($a['amLogin'])) : '-' ?></td>
                            <td><?= $a['amLogout'] ? date('h:i A', strtotime($a['amLogout'])) : '-' ?></td>
                            <td><?= $a['pmLogin'] ? date('h:i A', strtotime($a['pmLogin'])) : '-' ?></td>
                            <td><?= $a['pmLogout'] ? date('h:i A', strtotime($a['pmLogout'])) : '-' ?></td>
                            <td><?= htmlspecialchars($a['ExcuseLetter']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <!-- Fines Records -->
        <?php if ($fines_result && $fines_result->num_rows > 0): ?>
            <h5 style="margin: 20px 0 12px 0; color: #0f172a; font-size: 16px;">Fines Records</h5>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Date</th>
                        <th>Semester</th>
                        <th>School Year</th>
                        <th>Location</th>
                        <th>Penalty Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $fines_result->data_seek(0);
                    while($f = $fines_result->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($f['event_name']) ?></strong></td>
                            <td><?= date('M d, Y', strtotime($f['event_date'])) ?></td>
                            <td><?= htmlspecialchars($f['semester'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($f['school_year'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($f['location']) ?></td>
                            <td><strong style="color: #ef4444;">₱<?= number_format($f['PenaltyAmount'], 2) ?></strong></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <!-- Payment Records -->
        <?php if ($payments_result && $payments_result->num_rows > 0): ?>
            <h5 style="margin: 20px 0 12px 0; color: #0f172a; font-size: 16px;">Payment Records</h5>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Event Name</th>
                        <th>Semester</th>
                        <th>School Year</th>
                        <th>Payment Amount</th>
                        <th>Payment Type</th>
                        <th>Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $payments_result->data_seek(0);
                    while($p = $payments_result->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
                            <td><?= htmlspecialchars($p['event_name']) ?></td>
                            <td><?= htmlspecialchars($p['semester'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($p['school_year'] ?? 'N/A') ?></td>
                            <td><strong style="color: #10b981;">₱<?= number_format($p['payment_amount'], 2) ?></strong></td>
                            <td><?= htmlspecialchars($p['payment_type']) ?></td>
                            <td><strong style="color: #ef4444;">₱<?= number_format($p['balance'], 2) ?></strong></td>
                            <td>
                                <span class="badge badge-<?= $p['payment_status'] == 'Paid' ? 'paid' : ($p['payment_status'] == 'Unpaid' ? 'unpaid' : 'partial') ?>">
                                    <?= htmlspecialchars($p['payment_status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div style="margin: 32px 0; border-bottom: 2px solid #e2e8f0;"></div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="empty-state">
        <i class="fa fa-inbox"></i>
        <h3>No Records Found</h3>
        <p>No student records match your filter criteria</p>
    </div>
<?php endif; ?>
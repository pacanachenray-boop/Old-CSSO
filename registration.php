<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "csso";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ AUTO FETCH STUDENT NAME
if (isset($_GET['fetch_student'])) {
    $students_id = $_GET['fetch_student'];
    $query = "SELECT FirstName, LastName, MI, Suffix FROM student_profile WHERE students_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $students_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $fullname = trim($row['FirstName'] . ' ' . ($row['MI'] ? $row['MI'] . '. ' : '') . $row['LastName'] . ' ' . $row['Suffix']);
        echo json_encode(["success" => true, "fullname" => $fullname]);
    } else {
        echo json_encode(["success" => false]);
    }
    exit;
}

// ✅ CHECK DUPLICATE REGISTRATION
if (isset($_GET['check_duplicate'])) {
    $students_id = $_GET['students_id'];
    $school_year = $_GET['school_year'];
    $semester = $_GET['semester'];
    
    $query = "SELECT * FROM registration WHERE students_id = ? AND school_year = ? AND semester = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $students_id, $school_year, $semester);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(["duplicate" => true, "message" => "Student is already registered for this School Year and Semester!"]);
    } else {
        echo json_encode(["duplicate" => false]);
    }
    exit;
}

// ✅ SAVE WHEN PAY BUTTON IS CLICKED
if (isset($_POST['payNow'])) {
    $students_id = $_POST['students_id'];
    $registration_no = 'R' . rand(1000, 9999);
    $registration_date = date("Y-m-d");
    $semester = $_POST['semester'];
    $school_year = $_POST['school_year'];
    $membership_fee = 100.00;
    $amount = $_POST['amount'];
    $payment_type = $_POST['payment_type'];
    $payment_status = 'Paid'; // ✅ AUTOMATICALLY SET TO PAID
    $user_id = $_SESSION['user_id'] ?? null;

    // Check for duplicate before inserting
    $checkQuery = "SELECT * FROM registration WHERE students_id = ? AND school_year = ? AND semester = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("sss", $students_id, $school_year, $semester);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        echo "<script>
        alert('Error: Student is already registered for School Year $school_year and $semester!');
        </script>";
    } else {
        if ($user_id) {
            $query = "INSERT INTO registration (registration_no, students_id, registration_date, semester, school_year, membership_fee, amount, payment_type, payment_status, user_id)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssddssi", $registration_no, $students_id, $registration_date, $semester, $school_year, $membership_fee, $amount, $payment_type, $payment_status, $user_id);
        } else {
            $query = "INSERT INTO registration (registration_no, students_id, registration_date, semester, school_year, membership_fee, amount, payment_type, payment_status)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssddss", $registration_no, $students_id, $registration_date, $semester, $school_year, $membership_fee, $amount, $payment_type, $payment_status);
        }

        if ($stmt->execute()) {
            // fetch student full name for receipt
            $queryName = "SELECT FirstName, LastName, MI, Suffix FROM student_profile WHERE students_id = ?";
            $stmtName = $conn->prepare($queryName);
            $stmtName->bind_param("s", $students_id);
            $stmtName->execute();
            $resultName = $stmtName->get_result();
            $fullname = "";
            if ($resultName && $resultName->num_rows > 0) {
                $row = $resultName->fetch_assoc();
                $fullname = trim($row['FirstName'] . ' ' . ($row['MI'] ? $row['MI'] . '. ' : '') . $row['LastName'] . ' ' . $row['Suffix']);
            }

            echo "<script>
            window.onload = () => showReceiptPopup('$registration_no', '$students_id', '$fullname', '$registration_date', '$semester', '$school_year', '$membership_fee', '$amount', '$payment_type');
            </script>";
        } else {
            echo "<script>alert('Failed to save record. Please try again.');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registration | CSSO</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: #f8fafc;
  color: #1e293b;
  height: 100vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

/* Header */
.page-header {
  background: white;
  padding: 20px 32px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e2e8f0;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 20px;
}

.back-btn {
  background: #64748b;
  color: white;
  border: none;
  padding: 10px 24px;
  border-radius: 10px;
  cursor: pointer;
  font-size: 15px;
  font-weight: 600;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.back-btn:hover {
  background: #475569;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.header-left i {
  background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
  color: white;
  padding: 14px;
  border-radius: 12px;
  font-size: 24px;
  box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
}

.header-left h2 {
  font-size: 26px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.5px;
}

.header-logos {
  display: flex;
  align-items: center;
  gap: 16px;
}

.header-logo {
  height: 52px;
  width: auto;
  object-fit: contain;
  transition: all 0.3s ease;
}

.header-logo:hover {
  transform: scale(1.05);
}

/* Main Content */
.main-content {
  flex: 1;
  display: flex;
  gap: 24px;
  padding: 24px;
  overflow: hidden;
}

/* Form Section */
.form-section {
  flex: 2;
  background: white;
  padding: 32px;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  overflow-y: auto;
  border: 1px solid #e2e8f0;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-weight: 600;
  color: #0f172a;
  margin-bottom: 10px;
  font-size: 16px;
  letter-spacing: -0.2px;
}

.form-group label .required {
  color: #ef4444;
  margin-left: 3px;
  font-size: 18px;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 16px 18px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 16px;
  font-family: inherit;
  transition: all 0.3s ease;
  background: white;
  color: #0f172a;
  font-weight: 500;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #0ea5e9;
  box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.1);
}

.form-group input[readonly] {
  background: #f8fafc;
  color: #64748b;
  font-weight: 600;
  cursor: not-allowed;
  border-color: #f1f5f9;
}

.form-group select {
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'%3E%3Cpath fill='%230f172a' d='M8 11L3 6h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 16px center;
  padding-right: 48px;
}

.form-group select option {
  padding: 12px;
  font-size: 15px;
}

/* Payment Panel */
.payment-panel {
  flex: 1;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
  color: white;
  display: flex;
  flex-direction: column;
  gap: 14px;
  overflow-y: auto;
}

.summary-card {
  background: rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  padding: 14px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  text-align: center;
}

.summary-card h3 {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 8px;
  color: #94a3b8;
}

.summary-card .amount {
  font-size: 28px;
  font-weight: 700;
  margin: 6px 0;
}

.fee-amount { color: #ef4444; }
.paid-amount { color: #10b981; }
.change-amount { color: #f59e0b; }

.summary-card input {
  width: 100%;
  padding: 10px;
  border: 2px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  font-size: 16px;
  text-align: center;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  font-weight: 600;
  transition: all 0.3s ease;
}

.summary-card input:focus {
  outline: none;
  border-color: #0ea5e9;
  background: rgba(255, 255, 255, 0.15);
  box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.2);
}

.summary-card input::placeholder {
  color: rgba(255, 255, 255, 0.5);
}

.pay-button {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  padding: 12px;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
  margin-top: auto;
}

.pay-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

/* Error Message */
.error-message {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 20px;
  display: none;
  align-items: center;
  gap: 10px;
  font-weight: 500;
}

.error-message i {
  font-size: 18px;
}

/* Receipt Popup */
.popup-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: none;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.popup-content {
  background: white;
  padding: 40px;
  border-radius: 16px;
  max-width: 520px;
  width: 90%;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: popIn 0.3s ease;
  max-height: 90vh;
  overflow-y: auto;
}

@keyframes popIn {
  from {
    transform: scale(0.8);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.receipt-header {
  text-align: center;
  margin-bottom: 28px;
  padding-bottom: 24px;
  border-bottom: 3px solid #e0f2fe;
}

.receipt-header h2 {
  color: #0c4a6e;
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 8px;
  letter-spacing: -0.3px;
}

.receipt-header p {
  color: #64748b;
  font-size: 14px;
  margin: 4px 0;
}

.receipt-table {
  width: 100%;
  margin: 24px 0;
}

.receipt-table tr {
  border-bottom: 1px solid #f1f5f9;
}

.receipt-table td {
  padding: 14px 8px;
  color: #334155;
  font-size: 15px;
}

.receipt-table td:first-child {
  font-weight: 600;
  color: #0f172a;
  width: 45%;
}

.receipt-table td:last-child {
  text-align: right;
  font-weight: 500;
}

.receipt-highlight {
  color: #0ea5e9;
  font-weight: 700;
  font-size: 16px;
}

.receipt-actions {
  display: flex;
  gap: 12px;
  margin-top: 28px;
}

.receipt-btn {
  flex: 1;
  padding: 14px;
  border: none;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.print-btn {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.print-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.close-btn {
  background: #64748b;
  color: white;
}

.close-btn:hover {
  background: #475569;
  transform: translateY(-2px);
}

/* Success Popup */
.success-popup {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: none;
  justify-content: center;
  align-items: center;
  z-index: 1001;
}

.success-content {
  background: white;
  padding: 36px 48px;
  border-radius: 16px;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: popIn 0.3s ease;
}

.success-content i {
  color: #10b981;
  font-size: 56px;
  margin-bottom: 16px;
}

.success-content h3 {
  color: #0f172a;
  font-size: 18px;
  font-weight: 600;
  margin: 0;
  line-height: 1.5;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
  background: #94a3b8;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #64748b;
}

/* Responsive */
@media (max-width: 1200px) {
  .main-content {
    flex-direction: column;
  }
  
  .payment-panel {
    order: -1;
    flex-direction: row;
    flex-wrap: wrap;
  }
  
  .summary-card {
    flex: 1;
    min-width: 180px;
  }
  
  .pay-button {
    width: 100%;
  }
}

@media print {
  body * {
    visibility: hidden;
  }
  
  .popup-content, .popup-content * {
    visibility: visible;
  }
  
  .popup-content {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    box-shadow: none;
  }
  
  .receipt-actions {
    display: none;
  }
}
</style>
</head>
<body>

<!-- Header -->
<div class="page-header">
  <div class="header-left">
    <button class="back-btn" onclick="goBackToReglist()">
      Back
    </button>
    <i class="fa-solid fa-user-plus"></i>
    <h2>Student Registration</h2>
  </div>
  <div class="header-logos">
    <img src="../images/cpsclogo.png" alt="CPSC Logo" class="header-logo">
    <img src="../images/cssologo.png" alt="CSSO Logo" class="header-logo">
  </div>
</div>

<!-- Main Content -->
<div class="main-content">
  <!-- Form Section -->
  <div class="form-section">
    <!-- Error Message -->
    <div class="error-message" id="errorMessage">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <span id="errorText"></span>
    </div>

    <form method="POST" id="regForm">
      <div class="form-grid">
        <div class="form-group">
          <label>Student ID <span class="required">*</span></label>
          <input type="text" name="students_id" id="students_id" placeholder="Enter Student ID" required>
        </div>
        
        <div class="form-group">
          <label>Student Name</label>
          <input type="text" id="student_name" placeholder="Auto-filled" readonly>
        </div>
        
        <div class="form-group">
          <label>Registration Date</label>
          <input type="text" name="registration_date" value="<?php echo date('Y-m-d'); ?>" readonly>
        </div>
        
        <div class="form-group">
          <label>Semester <span class="required">*</span></label>
          <select name="semester" id="semester" required>
            <option value="">Select Semester</option>
            <option value="First Semester">First Semester</option>
            <option value="Second Semester">Second Semester</option>
          </select>
        </div>

        <div class="form-group">
          <label>School Year <span class="required">*</span></label>
          <select name="school_year" id="school_year" required>
            <option value="">Select School Year</option>
            <option value="2023-2024">2023-2024</option>
            <option value="2024-2025">2024-2025</option>
            <option value="2025-2026">2025-2026</option>
            <option value="2026-2027">2026-2027</option>
            <option value="2027-2028">2027-2028</option>
          </select>
        </div>
        
        <div class="form-group">
          <label>Membership Fee</label>
          <input type="text" name="membership_fee" value="₱100.00" readonly>
        </div>
        
        <div class="form-group">
          <label>Amount <span class="required">*</span></label>
          <input type="number" name="amount" id="amount" placeholder="Enter amount" required step="0.01">
        </div>
        
        <div class="form-group">
          <label>Payment Type <span class="required">*</span></label>
          <select name="payment_type" required>
            <option value="">Select Payment Type</option>
            <option value="Cash">Cash</option>
            <option value="Gcash">Gcash</option>
            <option value="Other">Other</option>
          </select>
        </div>
        
        <!-- ✅ REMOVED PAYMENT STATUS SELECTION - AUTOMATICALLY SET TO PAID -->
        <input type="hidden" name="payment_status" value="Paid">
      </div>
      <input type="hidden" name="payNow" value="1">
    </form>
  </div>

  <!-- Payment Panel -->
  <div class="payment-panel">
    <div class="summary-card">
      <h3>Membership Fee</h3>
      <div class="amount fee-amount">₱100.00</div>
    </div>
    
    <div class="summary-card">
      <h3>Amount to Pay</h3>
      <div class="amount paid-amount" id="displayAmount">₱0.00</div>
    </div>
    
    <div class="summary-card">
      <h3>Cash Received</h3>
      <input type="number" id="cash" placeholder="Enter cash" step="0.01">
    </div>
    
    <div class="summary-card">
      <h3>Change</h3>
      <div class="amount change-amount" id="changeDisplay">₱0.00</div>
    </div>
    
    <button class="pay-button" id="payBtn" type="button">
      <i class="fa-solid fa-money-bill-wave"></i>
      REGISTER
    </button>
  </div>
</div>

<!-- Receipt Popup -->
<div class="popup-overlay" id="popupBox">
  <div class="popup-content" id="popupContent"></div>
</div>

<!-- Success Popup -->
<div class="success-popup" id="successPopup">
  <div class="success-content">
    <i class="fa-solid fa-circle-check"></i>
    <h3>Student is now a member of the CSSO!</h3>
  </div>
</div>

<script>
// Auto-fetch student name
const studentID = document.getElementById('students_id');
const studentName = document.getElementById('student_name');
const semesterSelect = document.getElementById('semester');
const schoolYearSelect = document.getElementById('school_year');
const errorMessage = document.getElementById('errorMessage');
const errorText = document.getElementById('errorText');

studentID.addEventListener('input', function() {
  const id = this.value.trim();
  if (id.length >= 5) {
    fetch(`registration.php?fetch_student=${id}`)
      .then(res => res.json())
      .then(data => {
        studentName.value = data.success ? data.fullname : "No record found";
        studentName.style.color = data.success ? '#10b981' : '#ef4444';
        
        // Check for duplicate if semester and school year are selected
        if (data.success && semesterSelect.value && schoolYearSelect.value) {
          checkDuplicateRegistration(id, semesterSelect.value, schoolYearSelect.value);
        }
      })
      .catch(() => {
        studentName.value = "Error fetching";
        studentName.style.color = '#ef4444';
      });
  } else {
    studentName.value = "";
    hideError();
  }
});

// Check for duplicate registration when semester or school year changes
semesterSelect.addEventListener('change', checkCurrentDuplicate);
schoolYearSelect.addEventListener('change', checkCurrentDuplicate);

function checkCurrentDuplicate() {
  const studentId = studentID.value.trim();
  const semester = semesterSelect.value;
  const schoolYear = schoolYearSelect.value;
  
  if (studentId && studentId.length >= 5 && semester && schoolYear) {
    checkDuplicateRegistration(studentId, semester, schoolYear);
  } else {
    hideError();
  }
}

function checkDuplicateRegistration(studentId, semester, schoolYear) {
  fetch(`registration.php?check_duplicate=1&students_id=${studentId}&school_year=${schoolYear}&semester=${semester}`)
    .then(res => res.json())
    .then(data => {
      if (data.duplicate) {
        showError(data.message);
        disablePayButton();
      } else {
        hideError();
        enablePayButton();
      }
    })
    .catch(() => {
      hideError();
      enablePayButton();
    });
}

function showError(message) {
  errorText.textContent = message;
  errorMessage.style.display = 'flex';
}

function hideError() {
  errorMessage.style.display = 'none';
}

function disablePayButton() {
  const payBtn = document.getElementById('payBtn');
  payBtn.disabled = true;
  payBtn.style.opacity = '0.6';
  payBtn.style.cursor = 'not-allowed';
}

function enablePayButton() {
  const payBtn = document.getElementById('payBtn');
  payBtn.disabled = false;
  payBtn.style.opacity = '1';
  payBtn.style.cursor = 'pointer';
}

// Amount and change calculation
const amountInput = document.getElementById('amount');
const cashInput = document.getElementById('cash');
const displayAmount = document.getElementById('displayAmount');
const changeDisplay = document.getElementById('changeDisplay');

amountInput.addEventListener('input', () => {
  const amt = parseFloat(amountInput.value || 0);
  displayAmount.textContent = '₱' + amt.toFixed(2);
  calculateChange();
});

cashInput.addEventListener('input', calculateChange);

function calculateChange() {
  const amt = parseFloat(amountInput.value || 0);
  const cash = parseFloat(cashInput.value || 0);
  const change = cash - amt;
  changeDisplay.textContent = '₱' + (change > 0 ? change.toFixed(2) : '0.00');
}

// Pay button
document.getElementById('payBtn').addEventListener('click', function() {
  const form = document.getElementById('regForm');
  
  // Final duplicate check before submitting
  const studentId = studentID.value.trim();
  const semester = semesterSelect.value;
  const schoolYear = schoolYearSelect.value;
  
  if (studentId && semester && schoolYear) {
    fetch(`registration.php?check_duplicate=1&students_id=${studentId}&school_year=${schoolYear}&semester=${semester}`)
      .then(res => res.json())
      .then(data => {
        if (data.duplicate) {
          showError(data.message);
          disablePayButton();
        } else {
          if (form.checkValidity()) {
            form.submit();
          } else {
            form.reportValidity();
          }
        }
      })
      .catch(() => {
        if (form.checkValidity()) {
          form.submit();
        } else {
          form.reportValidity();
        }
      });
  } else {
    if (form.checkValidity()) {
      form.submit();
    } else {
      form.reportValidity();
    }
  }
});

// Receipt popup
function showReceiptPopup(no, id, fullname, date, sem, schoolYear, fee, amount, type) {
  const popup = document.getElementById('popupBox');
  const content = document.getElementById('popupContent');
  popup.style.display = 'flex';
  
  content.innerHTML = `
    <div class="receipt-header">
      <h2>COMPUTER STUDIES STUDENT ORGANIZATION</h2>
      <p>Camiguin Polytechnic State College</p>
      <p>Balbagon, Mambajao 9100, Camiguin Province</p>
    </div>
    
    <table class="receipt-table">
      <tr>
        <td>Registration No:</td>
        <td class="receipt-highlight">${no}</td>
      </tr>
      <tr>
        <td>Student ID:</td>
        <td>${id}</td>
      </tr>
      <tr>
        <td>Student Name:</td>
        <td><strong>${fullname}</strong></td>
      </tr>
      <tr>
        <td>Date:</td>
        <td>${date}</td>
      </tr>
      <tr>
        <td>School Year:</td>
        <td><strong>${schoolYear}</strong></td>
      </tr>
      <tr>
        <td>Semester:</td>
        <td>${sem}</td>
      </tr>
      <tr>
        <td>Membership Fee:</td>
        <td>₱${parseFloat(fee).toFixed(2)}</td>
      </tr>
      <tr>
        <td>Amount Paid:</td>
        <td class="receipt-highlight">₱${parseFloat(amount).toFixed(2)}</td>
      </tr>
      <tr>
        <td>Payment Type:</td>
        <td>${type}</td>
      </tr>
      <tr>
        <td>Payment Status:</td>
        <td class="receipt-highlight"><strong>PAID</strong></td>
      </tr>
    </table>
    
    <div class="receipt-actions">
      <button class="receipt-btn print-btn" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Print
      </button>
      <button class="receipt-btn close-btn" id="closeReceiptBtn">
        <i class="fa-solid fa-xmark"></i> Close
      </button>
    </div>
  `;
  
  document.getElementById('closeReceiptBtn').onclick = function() {
    popup.style.display = 'none';
    showSuccessMessage();
  };
}

function showSuccessMessage() {
  const successPopup = document.getElementById('successPopup');
  successPopup.style.display = 'flex';
  setTimeout(() => {
    successPopup.style.display = 'none';
    window.location.href = 'registration.php';
  }, 2500);
}

// Go back to reglist in admin dashboard
function goBackToReglist() {
  if (window.top !== window.self) {
    window.top.location.href = 'admin_dashboard.php';
  } else {
    window.location.href = 'admin_dashboard.php';
  }
}
</script>

</body>
</html>
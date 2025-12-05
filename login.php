<?php
session_start();

$conn = new mysqli("localhost", "root", "", "csso");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";
$success = false;
$loginSuccess = false;
$redirectPage = "";

if (isset($_POST['check_username']) && !empty($_POST['check_username'])) {
    header('Content-Type: application/json');
    $username = trim($_POST['check_username']);
    
    $sql = "SELECT usertype FROM users WHERE username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        echo json_encode(['success' => true, 'usertype' => $row['usertype']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Username not found']);
    }
    exit;
}

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $usertype = trim($_POST['usertype']);

    $sql = "SELECT * FROM users WHERE username=? AND usertype=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $usertype);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $role = strtolower($row['usertype']);

        if ($role === 'governor') {
            $statusOk = true;
        } else {
            $statusOk = false;
            if (isset($row['status'])) {
                $statusOk = ($row['status'] == 1 || strtolower($row['status']) === 'on');
            }
        }

        if (!$statusOk) {
            $message = "Your account is not yet activated. Please wait for admin approval.";
        }
        elseif (!password_verify($password, $row['password'])) {
            $message = "Invalid password!";
        }
        else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['usertype'] = $row['usertype'];

            $loginSuccess = true;

            $userType = $row['usertype'];
            
            if ($userType == "Governor" || $userType == "Vice Governor") {
                $redirectPage = "admin/admin_dashboard.php";
            }
            elseif (in_array($userType, ['Secretary', 'Auditor', 'Treasurer'])) {
                $redirectPage = "user/user_dashboard.php";
            }
            elseif (in_array($userType, ['Social Manager', 'Senator'])) {
                $redirectPage = "member/member_dashboard.php";
            }
            else {
                $redirectPage = "user/user_dashboard.php";
            }
        }
    } else {
        $message = "Username or usertype not found!";
    }
}

if (isset($_POST['signup'])) {
    $fname = trim($_POST['first_name']);
    $lname = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $rawPassword = trim($_POST['password']);
    $usertype = trim($_POST['usertype']);

    $limits = [
        'Governor'        => 1,
        'Vice Governor'   => 1,
        'Secretary'       => 1,
        'Auditor'         => 1,
        'Treasurer'       => 1,
        'Social Manager'  => 5,
        'Senator'         => 5
    ];

    if (!array_key_exists($usertype, $limits)) {
        $message = "Invalid user type selected!";
    } else {
        $checkUsername = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE username = ?");
        $checkUsername->bind_param("s", $username);
        $checkUsername->execute();
        $resUser = $checkUsername->get_result()->fetch_assoc();

        if ($resUser['total'] > 0) {
            $message = "Username already taken! Please choose another.";
        } else {
            $checkRole = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE usertype = ?");
            $checkRole->bind_param("s", $usertype);
            $checkRole->execute();
            $resRole = $checkRole->get_result()->fetch_assoc();

            $limit = $limits[$usertype];
            if ($resRole['total'] >= $limit) {
                $message = "$usertype is already registered. Only one $usertype is allowed.";
            } else {
                if (strlen($rawPassword) < 8) {
                    $message = "Password must be at least 8 characters long!";
                } else {
                    $password = password_hash($rawPassword, PASSWORD_DEFAULT);
                    $defaultStatus = ($usertype == 'Governor') ? 'on' : 'off';

                    $sql = "INSERT INTO users (first_name, last_name, username, password, usertype, status) 
                            VALUES (?,?,?,?,?,?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssss", $fname, $lname, $username, $password, $usertype, $defaultStatus);

                    if ($stmt->execute()) {
                        $success = true;
                    } else {
                        $message = "Error: " . $stmt->error;
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CSSO - Computer Studies Student Organization</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-image: url('images/cpscshool.jpg');
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
  height: 100vh;
  overflow: hidden;
  position: relative;
}

body::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(26, 42, 108, 0.5);
  z-index: 0;
}

/* Header Navigation */
.header-nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 60px;
  background: linear-gradient(135deg, #ffffff 0%, #0ea5e9 100%);
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  z-index: 100;
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 15px;
}

.logo-section img {
  width: 50px;
  height: 50px;
  object-fit: contain;
}

.logo-section .text-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.logo-section h2 {
  color: #1a2a6c;
  font-size: 20px;
  font-weight: 700;
  letter-spacing: 0.5px;
  line-height: 1.2;
  margin: 0;
}

.logo-section .address {
  color: #1a2a6c;
  font-size: 11px;
  font-weight: 400;
  opacity: 0.8;
  line-height: 1;
  margin: 0;
}

.nav-buttons {
  display: flex;
  gap: 15px;
}

.nav-btn {
  padding: 12px 25px;
  border: 2px solid #1a2a6c;
  background: transparent;
  color: #1a2a6c;
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  border-radius: 25px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.nav-btn:hover {
  background: #1a2a6c;
  color: #ffffff;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(26, 42, 108, 0.5);
}

/* Main Content */
.main-content {
  position: relative;
  z-index: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  padding: 100px 40px 40px;
}

.content-wrapper {
  display: flex;
  align-items: center;
  gap: 50px;
  max-width: 1400px;
}

.logo-container {
  display: flex;
  align-items: center;
  opacity: 0;
  animation: fadeInLeft 1s ease-out forwards;
}

.logo-container img {
  width: 280px;
  height: 280px;
  object-fit: contain;
  filter: drop-shadow(0 8px 20px rgba(0, 0, 0, 0.5));
}

.title-section {
  text-align: left;
}

.title-section .welcome-text {
  font-size: 28px;
  font-weight: 400;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 10px;
  text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
  opacity: 0;
  animation: fadeInUp 1s ease-out 0.3s forwards;
}

.title-section h1 {
  font-size: 82px;
  font-weight: 900;
  line-height: 1.2;
  color: #ffffff;
  text-shadow: 3px 3px 12px rgba(0, 0, 0, 0.7);
  opacity: 0;
  animation: fadeInUp 1s ease-out 0.5s forwards;
}

.title-section h1 .highlight {
  color: #00e5ff;
}

.title-section h1 .highlight {
  color: #00e5ff;
}

.title-section h1 .subtitle {
  display: block;
  font-size: 60px;
  font-weight: 360;
  color: #ffffff;
  margin-top: 10px;
  letter-spacing: 7px;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Modal Overlay */
.modal-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(1px);
  z-index: 200;
  justify-content: center;
  align-items: center;
  padding: 20px;
  animation: fadeIn 0.3s ease;
}

.modal-overlay.active {
  display: flex;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* Login Modal */
.login-modal {
  background: rgba(26, 42, 108, 0.6);   /* Very transparent! */
  backdrop-filter: blur(30px);           /* Strong blur */
  width: 100%;
  max-width: 450px;
  padding: 45px 40px;
  border-radius: 24px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5);
  border: 2px solid rgba(0, 229, 255, 0.4);  /* Slightly brighter border */
  position: relative;
  animation: slideIn 0.4s ease;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-50px) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.close-modal {
  position: absolute;
  top: 20px;
  right: 20px;
  background: transparent;
  border: none;
  color: rgba(255, 255, 255, 0.7);
  font-size: 28px;
  cursor: pointer;
  transition: all 0.3s ease;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

.close-modal:hover {
  color: #ff4444;
  background: rgba(255, 68, 68, 0.1);
  transform: rotate(90deg);
}

.form-title {
  text-align: center;
  margin-bottom: 35px;
  color: #ffffff;
  font-weight: 700;
  font-size: 32px;
  text-transform: uppercase;
  letter-spacing: 2px;
}

.input-group {
  margin-bottom: 25px;
  position: relative;
}

.input-group i {
  position: absolute;
  left: 18px;
  top: 50%;
  transform: translateY(-50%);
  color: rgba(255, 255, 255, 0.7);
  font-size: 16px;
  z-index: 1;
}

.input-group input,
.input-group select {
  width: 100%;
  height: 55px;
  padding: 15px 20px 15px 50px;
  border-radius: 30px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  outline: none;
  font-size: 15px;
  color: #ffffff;
  background: rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.input-group input::placeholder {
  color: rgba(255, 255, 255, 0.6);
}

.input-group input:focus,
.input-group select:focus {
  border-color: #00e5ff;
  background: rgba(255, 255, 255, 0.15);
  box-shadow: 0 0 15px rgba(0, 229, 255, 0.3);
}

.input-group select {
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23ffffff' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 20px center;
  padding-right: 45px;
}

.input-group select option {
  background: #1a2a6c;
  color: #ffffff;
}

.input-group input.error,
.input-group select.error {
  border-color: #ff4444 !important;
  background: rgba(255, 68, 68, 0.15) !important;
  animation: shake 0.5s ease;
}

.input-group input.success {
  border-color: #00ff88 !important;
  background: rgba(0, 255, 136, 0.15) !important;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}

.error-message {
  color: #ff6b6b;
  font-size: 12px;
  margin-top: 5px;
  margin-left: 20px;
  display: none;
  animation: fadeIn 0.3s ease;
}

.error-message.show {
  display: block;
}

.success-message {
  color: #00ff88;
  font-size: 12px;
  margin-top: 5px;
  margin-left: 20px;
  display: none;
  animation: fadeIn 0.3s ease;
}

.success-message.show {
  display: block;
}

.usertype-display {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  height: 55px;
  padding: 15px 20px 15px 50px;
  border-radius: 30px;
  border: 1px solid rgba(0, 229, 255, 0.5);
  background: rgba(0, 229, 255, 0.15);
  backdrop-filter: blur(10px);
  color: #00e5ff;
  font-size: 15px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.usertype-display.empty {
  color: rgba(255, 255, 255, 0.5);
  border-color: rgba(255, 255, 255, 0.3);
  background: rgba(255, 255, 255, 0.1);
}

.usertype-display i.fa-check-circle {
  color: #00e5ff;
  font-size: 18px;
}

.usertype-display.empty i.fa-check-circle {
  color: rgba(255, 255, 255, 0.3);
}

.input-group .loading-indicator {
  position: absolute;
  right: 20px;
  top: 50%;
  transform: translateY(-50%);
  color: #00e5ff;
  font-size: 14px;
  display: none;
}

.input-group .loading-indicator.show {
  display: block;
}

.btn-primary {
  width: 100%;
  height: 55px;
  background: linear-gradient(135deg, #00e5ff 0%, #00d4ff 100%);
  color: #ffffff;
  border: none;
  border-radius: 30px;
  font-size: 17px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 2px;
  box-shadow: 0 6px 20px rgba(0, 229, 255, 0.5);
  margin-top: 10px;
  position: relative;
  overflow: hidden;
}

.btn-primary::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.5s ease;
}

.btn-primary:hover:not(:disabled)::before {
  left: 100%;
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #00d4ff 0%, #00e5ff 100%);
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(0, 229, 255, 0.7);
}

.btn-primary:active:not(:disabled) {
  transform: translateY(0);
}

.btn-primary:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.separator {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 30px 0;
  color: rgba(255, 255, 255, 0.7);
  font-size: 14px;
  font-weight: 500;
}

.separator::before,
.separator::after {
  content: "";
  flex: 1;
  height: 1px;
  background: rgba(255, 255, 255, 0.3);
  margin: 0 15px;
}

.toggle-section {
  text-align: center;
  margin-top: 20px;
  color: rgba(255, 255, 255, 0.8);
  font-size: 14px;
}

.toggle-link {
  cursor: pointer;
  color: #00e5ff;
  font-weight: 700;
  transition: color 0.3s ease;
}

.toggle-link:hover {
  color: #ffffff;
  text-decoration: underline;
}

.row {
  display: flex;
  gap: 15px;
}

.row .input-group {
  flex: 1;
}

#signupForm {
  opacity: 0;
  height: 0;
  overflow: hidden;
  pointer-events: none;
  transition: all 0.4s ease;
}

#signupForm.active {
  opacity: 1;
  height: auto;
  pointer-events: auto;
}

#loginForm.hide {
  opacity: 0;
  height: 0;
  overflow: hidden;
  pointer-events: none;
  transition: all 0.4s ease;
}

@media (max-width: 768px) {
  .header-nav {
    padding: 15px 20px;
  }
  
  .logo-section img {
    width: 40px;
    height: 40px;
  }
  
  .logo-section h2 {
    font-size: 14px;
  }
  
  .logo-section .address {
    font-size: 9px;
  }
  
  .nav-btn {
    padding: 10px 18px;
    font-size: 12px;
  }
  
  .main-content {
    padding: 100px 20px 20px;
  }
  
  .content-wrapper {
    flex-direction: column;
    gap: 30px;
    text-align: center;
  }
  
  .logo-container img {
    width: 150px;
    height: 150px;
  }
  
  .title-section {
    text-align: center;
  }
  
  .title-section .welcome-text {
    font-size: 18px;
  }
  
  .title-section h1 {
    font-size: 36px;
  }
  
  .login-modal {
    padding: 35px 25px;
  }
  
  .row {
    flex-direction: column;
    gap: 0;
  }
}
</style>
</head>

<body>
<!-- Header Navigation -->
<div class="header-nav">
  <div class="logo-section">
    <img src="images/cssologo.png" alt="CSSO Logo">
    <img src="images/cpsclogo.png" alt="CPSC Logo">
    <div class="text-content">
      <h2>Camiguin Polytechnic State College</h2>
      <p class="address">Balbagon, Mambajao 9100, Camiguin Prov</p>
    </div>
  </div>
  
  <div class="nav-buttons">
    <button class="nav-btn" onclick="openLoginModal()">
      <i class="fa fa-user"></i> Login
    </button>
    <button class="nav-btn" onclick="window.location.href='settings/settings.php'">
      <i class="fa fa-info-circle"></i> About
    </button>
  </div>
</div>

<!-- Main Content -->
<div class="main-content">
  <div class="content-wrapper">
    <div class="title-section">
      <p class="welcome-text">Welcome to the</p>
      <h1>
        <span class="highlight">Computer Studies<br>Student Organization</span>
        <span class="subtitle">Management System</span>
      </h1>
    </div>
    
    <div class="logo-container">
      <img src="images/cssologo.png" alt="CSSO Logo">
    </div>
  </div>
</div>

<!-- Login Modal -->
<div class="modal-overlay" id="modalOverlay">
  <div class="login-modal">
    <button class="close-modal" onclick="closeLoginModal()">
      <i class="fa fa-times"></i>
    </button>
    
    <h2 class="form-title" id="formTitle">Login</h2>

    <!-- Login Form -->
    <form method="POST" id="loginForm" autocomplete="off">
      <div class="input-group">
        <i class="fa fa-user"></i>
        <input type="text" name="username" id="loginUsername" placeholder="Enter your username" required>
        <span class="loading-indicator" id="loadingIndicator">
          <i class="fa fa-spinner fa-spin"></i>
        </span>
        <div class="error-message" id="loginUsernameError">Please enter your username</div>
      </div>
      
      <input type="hidden" name="usertype" id="hiddenUsertype" value="">
      
      <div class="input-group">
        <i class="fa fa-id-badge"></i>
        <div class="usertype-display empty" id="usertypeDisplay">
          <span id="usertypeText">Usertype will appear here</span>
          <i class="fa fa-check-circle"></i>
        </div>
      </div>
      
      <div class="input-group">
        <i class="fa fa-lock"></i>
        <input type="password" name="password" id="loginPassword" placeholder="Enter your password" required disabled>
        <div class="error-message" id="loginPasswordError">Please enter your password</div>
      </div>
      
      <button type="submit" class="btn-primary" name="login" id="loginBtn" disabled>Login</button>

      <div class="separator">OR</div>

      <div class="toggle-section">
        Don't have an account? <span class="toggle-link" onclick="toggleForms('signup')">Sign up</span>
      </div>
    </form>

    <!-- Signup Form -->
    <form method="POST" id="signupForm" autocomplete="off">
      <div class="row">
        <div class="input-group">
          <i class="fa fa-user"></i>
          <input type="text" name="first_name" id="signupFirstName" placeholder="First name" required>
          <div class="error-message" id="firstNameError">First name is required</div>
        </div>
        <div class="input-group">
          <i class="fa fa-user"></i>
          <input type="text" name="last_name" id="signupLastName" placeholder="Last name" required>
          <div class="error-message" id="lastNameError">Last name is required</div>
        </div>
      </div>
      
      <div class="input-group">
        <i class="fa fa-at"></i>
        <input type="text" name="username" id="signupUsername" placeholder="Username" required>
        <div class="error-message" id="usernameError">Username must be at least 3 characters</div>
        <div class="success-message" id="usernameSuccess">Username is valid</div>
      </div>
      
      <div class="input-group">
        <i class="fa fa-lock"></i>
        <input type="password" name="password" id="signupPassword" placeholder="Password (min. 8 characters)" required>
        <div class="error-message" id="passwordError">Password must be at least 8 characters</div>
        <div class="success-message" id="passwordSuccess">Password is strong</div>
      </div>
      
      <div class="input-group">
        <i class="fa fa-id-badge"></i>
        <select name="usertype" id="signupUsertype" required>
          <option value="" disabled selected>Select User Type</option>
          <option value="Governor">Governor</option>
          <option value="Vice Governor">Vice Governor</option>
          <option value="Secretary">Secretary</option>
          <option value="Auditor">Auditor</option>
          <option value="Treasurer">Treasurer</option>
          <option value="Social Manager">Social Manager</option>
          <option value="Senator">Senator</option>
        </select>
        <div class="error-message" id="usertypeError">Please select a user type</div>
      </div>
      
      <button type="submit" class="btn-primary" name="signup" id="signupBtn">Create Account</button>

      <div class="toggle-section">
        Already have an account? <span class="toggle-link" onclick="toggleForms('login')">Login</span>
      </div>
    </form>
  </div>
</div>

<?php if ($message != ""): ?>
<script>
Swal.fire({
  title: "Error!",
  text: "<?php echo $message; ?>",
  icon: "error",
  confirmButtonColor: "#1a2a6c",
  confirmButtonText: "OK"
});
</script>
<?php endif; ?>

<?php if ($success): ?>
<script>
Swal.fire({
  title: "Signup Successful!",
  text: "Your account has been created and is now pending admin approval.",
  icon: "success",
  confirmButtonColor: "#1a2a6c"
}).then(() => {
  toggleForms('login');
});
</script>
<?php endif; ?>

<?php if ($loginSuccess): ?>
<script>
Swal.fire({
  title: "Login Successful!",
  text: "Welcome back!",
  icon: "success",
  confirmButtonColor: "#1a2a6c",
  timer: 1500,
  showConfirmButton: false
}).then(() => {
  window.location.href = "<?php echo $redirectPage; ?>";
});
</script>
<?php endif; ?>

<script>
// Modal Functions
function openLoginModal() {
  document.getElementById('modalOverlay').classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeLoginModal() {
  document.getElementById('modalOverlay').classList.remove('active');
  document.body.style.overflow = 'auto';
  
  // Reset forms
  document.querySelectorAll('input[type="text"], input[type="password"]').forEach(i => {
    i.value = '';
    i.classList.remove('error', 'success');
  });
  document.querySelectorAll('select').forEach(s => {
    s.selectedIndex = 0;
    s.classList.remove('error');
  });
  document.querySelectorAll('.error-message, .success-message').forEach(msg => {
    msg.classList.remove('show');
  });
  
  const usertypeDisplay = document.getElementById('usertypeDisplay');
  const usertypeText = document.getElementById('usertypeText');
  const hiddenUsertype = document.getElementById('hiddenUsertype');
  const passwordField = document.getElementById('loginPassword');
  
  usertypeDisplay.classList.add('empty');
  usertypeText.textContent = 'Usertype will appear here';
  hiddenUsertype.value = '';
  loginBtn.disabled = true;
  passwordField.disabled = true;
  signupBtn.disabled = true;
  
  if (!document.getElementById('loginForm').classList.contains('hide')) {
    document.getElementById('formTitle').innerText = "Login";
  }
}

// Close modal when clicking outside
document.getElementById('modalOverlay').addEventListener('click', function(e) {
  if (e.target === this) {
    closeLoginModal();
  }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeLoginModal();
  }
});

// Login Form Validation
let typingTimer;
const doneTypingInterval = 500;

const loginUsername = document.getElementById('loginUsername');
const loginPassword = document.getElementById('loginPassword');
const loginBtn = document.getElementById('loginBtn');

loginUsername.addEventListener('input', function() {
  clearTimeout(typingTimer);
  const username = this.value.trim();
  const usertypeDisplay = document.getElementById('usertypeDisplay');
  const usertypeText = document.getElementById('usertypeText');
  const hiddenUsertype = document.getElementById('hiddenUsertype');
  const loadingIndicator = document.getElementById('loadingIndicator');
  const usernameError = document.getElementById('loginUsernameError');
  
  if (username.length === 0) {
    this.classList.add('error');
    this.classList.remove('success');
    usernameError.classList.add('show');
    usertypeDisplay.classList.add('empty');
    usertypeText.textContent = 'Usertype will appear here';
    hiddenUsertype.value = '';
    loginBtn.disabled = true;
    loginPassword.disabled = true;
    loginPassword.value = '';
    loadingIndicator.classList.remove('show');
    return;
  }
  
  this.classList.remove('error');
  usernameError.classList.remove('show');
  
  if (username.length >= 2) {
    typingTimer = setTimeout(function() {
      loadingIndicator.classList.add('show');
      usertypeDisplay.classList.add('empty');
      usertypeText.textContent = 'Checking username...';
      loginBtn.disabled = true;
      loginPassword.disabled = true;
      
      const formData = new URLSearchParams();
      formData.append('check_username', username);
      
      fetch(window.location.href, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
      })
      .then(response => response.json())
      .then(data => {
        loadingIndicator.classList.remove('show');
        
        if (data.success && data.usertype) {
          loginUsername.classList.remove('error');
          loginUsername.classList.add('success');
          usertypeDisplay.classList.remove('empty');
          usertypeText.textContent = data.usertype;
          hiddenUsertype.value = data.usertype;
          loginPassword.disabled = false;
          
          setTimeout(() => {
            loginPassword.focus();
          }, 100);
          
          usertypeDisplay.style.transform = 'scale(1.05)';
          setTimeout(() => {
            usertypeDisplay.style.transform = 'scale(1)';
          }, 200);
          
          checkLoginFormValidity();
        } else {
          loginUsername.classList.add('error');
          loginUsername.classList.remove('success');
          usertypeDisplay.classList.add('empty');
          usertypeText.textContent = '❌ Username not found';
          hiddenUsertype.value = '';
          loginBtn.disabled = true;
          loginPassword.disabled = true;
        }
      })
      .catch(error => {
        console.error('Error:', error);
        loadingIndicator.classList.remove('show');
        loginUsername.classList.add('error');
        usertypeDisplay.classList.add('empty');
        usertypeText.textContent = '⚠️ Connection error';
        hiddenUsertype.value = '';
        loginBtn.disabled = true;
        loginPassword.disabled = true;
      });
    }, doneTypingInterval);
  } else {
    usertypeDisplay.classList.add('empty');
    usertypeText.textContent = 'Type your username...';
    hiddenUsertype.value = '';
    loginBtn.disabled = true;
    loginPassword.disabled = true;
  }
});

loginPassword.addEventListener('input', function() {
  const value = this.value;
  const passwordError = document.getElementById('loginPasswordError');
  
  if (value.length === 0) {
    this.classList.add('error');
    this.classList.remove('success');
    passwordError.classList.add('show');
  } else if (value.length < 6) {
    this.classList.add('error');
    this.classList.remove('success');
    passwordError.textContent = 'Password is too short';
    passwordError.classList.add('show');
  } else {
    this.classList.remove('error');
    this.classList.add('success');
    passwordError.classList.remove('show');
  }
  checkLoginFormValidity();
});

function checkLoginFormValidity() {
  const username = loginUsername.value.trim();
  const password = loginPassword.value;
  const usertype = document.getElementById('hiddenUsertype').value;
  
  const isValid = 
    username.length >= 2 &&
    password.length >= 6 &&
    usertype !== '';
  
  loginBtn.disabled = !isValid;
}

// Signup Form Validation
const signupFirstName = document.getElementById('signupFirstName');
const signupLastName = document.getElementById('signupLastName');
const signupUsername = document.getElementById('signupUsername');
const signupPassword = document.getElementById('signupPassword');
const signupUsertype = document.getElementById('signupUsertype');
const signupBtn = document.getElementById('signupBtn');

signupFirstName.addEventListener('input', function() {
  const value = this.value.trim();
  const errorMsg = document.getElementById('firstNameError');
  
  if (value.length === 0) {
    this.classList.add('error');
    this.classList.remove('success');
    errorMsg.classList.add('show');
    errorMsg.textContent = 'First name is required';
  } else if (value.length < 2) {
    this.classList.add('error');
    this.classList.remove('success');
    errorMsg.classList.add('show');
    errorMsg.textContent = 'First name is too short';
  } else {
    this.classList.remove('error');
    this.classList.add('success');
    errorMsg.classList.remove('show');
  }
  checkSignupFormValidity();
});

signupLastName.addEventListener('input', function() {
  const value = this.value.trim();
  const errorMsg = document.getElementById('lastNameError');
  
  if (value.length === 0) {
    this.classList.add('error');
    this.classList.remove('success');
    errorMsg.classList.add('show');
    errorMsg.textContent = 'Last name is required';
  } else if (value.length < 2) {
    this.classList.add('error');
    this.classList.remove('success');
    errorMsg.classList.add('show');
    errorMsg.textContent = 'Last name is too short';
  } else {
    this.classList.remove('error');
    this.classList.add('success');
    errorMsg.classList.remove('show');
  }
  checkSignupFormValidity();
});

signupUsername.addEventListener('input', function() {
  const value = this.value.trim();
  const errorMsg = document.getElementById('usernameError');
  const successMsg = document.getElementById('usernameSuccess');
  
  if (value.length === 0) {
    this.classList.add('error');
    this.classList.remove('success');
    errorMsg.classList.add('show');
    successMsg.classList.remove('show');
    errorMsg.textContent = 'Username is required';
  } else if (value.length < 3) {
    this.classList.add('error');
    this.classList.remove('success');
    errorMsg.classList.add('show');
    successMsg.classList.remove('show');
    errorMsg.textContent = 'Username must be at least 3 characters';
  } else {
    this.classList.remove('error');
    this.classList.add('success');
    errorMsg.classList.remove('show');
    successMsg.classList.add('show');
  }
  checkSignupFormValidity();
});

signupPassword.addEventListener('input', function() {
  const value = this.value;
  const errorMsg = document.getElementById('passwordError');
  const successMsg = document.getElementById('passwordSuccess');
  
  if (value.length === 0) {
    this.classList.remove('error', 'success');
    errorMsg.classList.remove('show');
    successMsg.classList.remove('show');
  } else if (value.length < 8) {
    this.classList.add('error');
    this.classList.remove('success');
    errorMsg.classList.add('show');
    successMsg.classList.remove('show');
    errorMsg.textContent = `Password must be at least 8 characters (${value.length}/8)`;
  } else {
    this.classList.remove('error');
    this.classList.add('success');
    errorMsg.classList.remove('show');
    successMsg.classList.add('show');
  }
  checkSignupFormValidity();
});

signupUsertype.addEventListener('change', function() {
  const value = this.value;
  const errorMsg = document.getElementById('usertypeError');
  
  if (value === '') {
    this.classList.add('error');
    errorMsg.classList.add('show');
  } else {
    this.classList.remove('error');
    errorMsg.classList.remove('show');
  }
  checkSignupFormValidity();
});

function checkSignupFormValidity() {
  const firstName = signupFirstName.value.trim();
  const lastName = signupLastName.value.trim();
  const username = signupUsername.value.trim();
  const password = signupPassword.value;
  const usertype = signupUsertype.value;
  
  const isValid = 
    firstName.length >= 2 &&
    lastName.length >= 2 &&
    username.length >= 3 &&
    password.length >= 8 &&
    usertype !== '';
  
  signupBtn.disabled = !isValid;
}

document.getElementById('signupForm').addEventListener('submit', function(e) {
  const password = signupPassword.value;
  
  if (password.length < 8) {
    e.preventDefault();
    Swal.fire({
      title: "Invalid Password",
      text: "Password must be at least 8 characters long!",
      icon: "error",
      confirmButtonColor: "#1a2a6c"
    });
    signupPassword.focus();
    signupPassword.classList.add('error');
    return false;
  }
});

document.getElementById('loginForm').addEventListener('submit', function(e) {
  const username = loginUsername.value.trim();
  const password = loginPassword.value;
  const usertype = document.getElementById('hiddenUsertype').value;
  
  if (username.length === 0) {
    e.preventDefault();
    loginUsername.classList.add('error');
    document.getElementById('loginUsernameError').classList.add('show');
    loginUsername.focus();
    return false;
  }
  
  if (usertype === '') {
    e.preventDefault();
    Swal.fire({
      title: "Username Not Found",
      text: "Please enter a valid username!",
      icon: "error",
      confirmButtonColor: "#1a2a6c"
    });
    loginUsername.focus();
    return false;
  }
  
  if (password.length === 0) {
    e.preventDefault();
    loginPassword.classList.add('error');
    document.getElementById('loginPasswordError').classList.add('show');
    loginPassword.focus();
    return false;
  }
});

function toggleForms(form) {
  const loginForm = document.getElementById('loginForm');
  const signupForm = document.getElementById('signupForm');
  const formTitle = document.getElementById('formTitle');
  
  document.querySelectorAll('input[type="text"], input[type="password"]').forEach(i => {
    i.value = '';
    i.classList.remove('error', 'success');
  });
  document.querySelectorAll('select').forEach(s => {
    s.selectedIndex = 0;
    s.classList.remove('error');
  });
  
  document.querySelectorAll('.error-message, .success-message').forEach(msg => {
    msg.classList.remove('show');
  });
  
  const usertypeDisplay = document.getElementById('usertypeDisplay');
  const usertypeText = document.getElementById('usertypeText');
  const hiddenUsertype = document.getElementById('hiddenUsertype');
  const passwordField = document.getElementById('loginPassword');
  
  usertypeDisplay.classList.add('empty');
  usertypeText.textContent = 'Usertype will appear here';
  hiddenUsertype.value = '';
  loginBtn.disabled = true;
  passwordField.disabled = true;
  
  signupBtn.disabled = true;
  
  if (form === 'signup') {
    loginForm.classList.add('hide');
    signupForm.classList.add('active');
    formTitle.innerText = "Sign Up";
  } else {
    loginForm.classList.remove('hide');
    signupForm.classList.remove('active');
    formTitle.innerText = "Login";
  }
}

loginBtn.disabled = true;
signupBtn.disabled = true;
</script>
</body>
</html>
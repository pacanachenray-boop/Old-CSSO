<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings - CSSO</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: #f5f5f5;
  height: 100vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

/* Header Navigation */
.header-nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 18px 60px;
  background: linear-gradient(135deg, #ffffff 0%, #0ea5e9 100%);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.nav-buttons {
  display: flex;
  gap: 15px;
  align-items: center;
  flex-wrap: wrap;
  justify-content: center;
}

.nav-btn {
  padding: 12px 28px;
  border: 2px solid #1a2a6c;
  background: transparent;
  color: #1a2a6c;
  font-size: 14px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  border-radius: 30px;
  cursor: pointer;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.nav-btn:hover {
  background: #1a2a6c;
  color: #ffffff;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(26, 42, 108, 0.5);
}

.nav-btn.active {
  background: #1a2a6c;
  color: #ffffff;
  box-shadow: 0 4px 15px rgba(26, 42, 108, 0.6);
}

/* Main Content Container - FULL SCREEN */
.main-content {
  position: fixed;
  top: 74px;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1;
  background: #ffffff;
  overflow: hidden;
}

.content-frame {
  width: 100%;
  height: 100%;
  border: none;
  background: #ffffff;
}

@media (max-width: 1024px) {
  .header-nav {
    padding: 15px 30px;
  }
  
  .nav-buttons {
    gap: 10px;
  }
  
  .nav-btn {
    padding: 10px 22px;
    font-size: 12px;
  }
  
  .main-content {
    top: 70px;
  }
}

@media (max-width: 768px) {
  .header-nav {
    padding: 12px 20px;
  }
  
  .nav-buttons {
    gap: 8px;
  }
  
  .nav-btn {
    padding: 8px 16px;
    font-size: 10px;
  }
  
  .main-content {
    top: 90px;
  }
}
</style>
</head>

<body>
<!-- Header Navigation - BUTTONS ONLY -->
<div class="header-nav">
  <div class="nav-buttons">
    <button class="nav-btn active" onclick="loadPage('aboutus.php', this)">
      <i class="fa fa-info-circle"></i> ABOUT US
    </button>
    <button class="nav-btn" onclick="loadPage('randysalon.php', this)">
      <i class="fa fa-user"></i> RANDY SALON
    </button>
    <button class="nav-btn" onclick="loadPage('caytuna.php', this)">
      <i class="fa fa-user"></i> CAYTONA
    </button>
    <button class="nav-btn" onclick="loadPage('pacana.php', this)">
      <i class="fa fa-user"></i> PACANA
    </button>
    <button class="nav-btn" onclick="loadPage('erd.php', this)">
      <i class="fa fa-database"></i> ERD
    </button>
    <button class="nav-btn" onclick="loadPage('data.php', this)">
      <i class="fa fa-chart-line"></i> DATAFLOW
    </button>
  </div>
</div>

<!-- Main Content - FULL SCREEN -->
<div class="main-content" id="mainContent">
  <!-- Content will be loaded here via iframe -->
</div>

<script>
function loadPage(page, button) {
  const mainContent = document.getElementById('mainContent');
  
  // Clear existing content
  mainContent.innerHTML = '';
  
  // Create iframe
  const iframe = document.createElement('iframe');
  iframe.className = 'content-frame';
  iframe.src = page;
  mainContent.appendChild(iframe);
  
  // Update active button
  document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.classList.remove('active');
  });
  button.classList.add('active');
}

// Load default page on startup
window.addEventListener('DOMContentLoaded', () => {
  loadPage('aboutus.php', document.querySelector('.nav-btn.active'));
});
</script>

</body>
</html>
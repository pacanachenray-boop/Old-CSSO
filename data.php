<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CSSO - Data Flow Diagram</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Arial', sans-serif;
  background: #f5f5f5;
  padding: 40px 20px;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
}

.header {
  text-align: center;
  margin-bottom: 40px;
}

.header h1 {
  color: #2c3e50;
  font-size: 36px;
  font-weight: 700;
  margin-bottom: 10px;
}

.header p {
  color: #666;
  font-size: 16px;
}

.dfd-wrapper {
  background: #ffffff;
  border-radius: 12px;
  padding: 40px;
  overflow-x: auto;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.dfd-canvas {
  position: relative;
  min-width: 1200px;
  min-height: 900px;
  background: #ffffff;
}

/* Entity Boxes */
.entity-box {
  position: absolute;
  border: 3px solid #2c3e50;
  border-radius: 8px;
  padding: 40px 50px;
  font-size: 28px;
  font-weight: 600;
  text-align: center;
  color: #000;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.student-box {
  top: 50px;
  left: 420px;
  width: 360px;
  height: 150px;
  background: #5DADE2;
}

.cso-system {
  top: 380px;
  left: 380px;
  width: 440px;
  height: 180px;
  background: #52BE80;
}

.cso-officer {
  top: 730px;
  left: 40px;
  width: 420px;
  height: 170px;
  background: #1ABC9C;
}

.admin-box {
  top: 730px;
  left: 740px;
  width: 320px;
  height: 170px;
  background: #F4D03F;
}

/* Data Flow Labels */
.flow-label {
  position: absolute;
  font-size: 14px;
  font-weight: 600;
  color: #000;
  background: #e8e8e8;
  padding: 8px 16px;
  border-radius: 4px;
  white-space: nowrap;
  z-index: 10;
  border: 1px solid #ccc;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.legend-box {
  margin-top: 30px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
  border-left: 4px solid #52BE80;
}

.legend-title {
  font-size: 18px;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 15px;
}

.legend-items {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 12px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: #555;
}

@media (max-width: 1400px) {
  .dfd-canvas {
    transform: scale(0.85);
    transform-origin: top left;
  }
}

@media (max-width: 1000px) {
  .dfd-canvas {
    transform: scale(0.65);
    transform-origin: top left;
  }
}

@media (max-width: 768px) {
  .dfd-canvas {
    transform: scale(0.45);
    transform-origin: top left;
  }
}
</style>
</head>

<body>
<div class="container">
  <div class="header">
    <h1>CSSO Management System - Data Flow Diagram</h1>
    <p>Computer Studies Student Organization Data Flow</p>
  </div>

  <div class="dfd-wrapper">
    <div class="dfd-canvas">
      
      <!-- Entity Boxes -->
      <div class="entity-box student-box">Student</div>
      <div class="entity-box cso-system">CSO System</div>
      <div class="entity-box cso-officer">CSO Officer</div>
      <div class="entity-box admin-box">Admin</div>

      <!-- SVG for Connection Lines and Arrows -->
      <svg style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1;">
        <defs>
          <marker id="arrowWhite" markerWidth="12" markerHeight="12" refX="10" refY="4" orient="auto" markerUnits="strokeWidth">
            <path d="M0,0 L0,8 L10,4 z" fill="#2c3e50" />
          </marker>
        </defs>

        <!-- Student to System - LEFT arrow (Register, Attend, Pay) -->
        <path d="M 520 200 L 520 380" stroke="#2c3e50" stroke-width="3" fill="none" marker-end="url(#arrowWhite)"/>
        
        <!-- System to Student - RIGHT arrow (Confirmations) -->
        <path d="M 680 380 L 680 200" stroke="#2c3e50" stroke-width="3" fill="none" marker-end="url(#arrowWhite)"/>

        <!-- System to Officer - LEFT bottom arrow (Reports) -->
        <path d="M 420 560 L 250 730" stroke="#2c3e50" stroke-width="3" fill="none" marker-end="url(#arrowWhite)"/>
        
        <!-- Officer to System - LEFT arrow (Manage Events, Track) -->
        <path d="M 300 730 L 470 560" stroke="#2c3e50" stroke-width="3" fill="none" marker-end="url(#arrowWhite)"/>

        <!-- System to Admin - RIGHT bottom arrow (View Reports) -->
        <path d="M 730 560 L 900 730" stroke="#2c3e50" stroke-width="3" fill="none" marker-end="url(#arrowWhite)"/>
        
        <!-- Admin to System - RIGHT arrow (Analytics) -->
        <path d="M 850 730 L 680 560" stroke="#2c3e50" stroke-width="3" fill="none" marker-end="url(#arrowWhite)"/>

      </svg>

      <!-- Data Flow Labels -->
      <!-- Student to System -->
      <div class="flow-label" style="top: 270px; left: 220px;">Register, Attend, Pay</div>
      
      <!-- System to Student -->
      <div class="flow-label" style="top: 270px; left: 690px;">Confirmations</div>

      <!-- System to Officer -->
      <div class="flow-label" style="top: 620px; left: 120px;">Reports</div>
      
      <!-- Officer to System -->
      <div class="flow-label" style="top: 620px; left: 270px;">Manage Events, Track</div>

      <!-- System to Admin -->
      <div class="flow-label" style="top: 620px; left: 750px;">View Reports</div>
      
      <!-- Admin to System -->
      <div class="flow-label" style="top: 620px; left: 570px;">Analytics</div>

    </div>

    <div class="legend-box">
      <div class="legend-title"><i class="fas fa-info-circle"></i> Legend</div>
      <div class="legend-items">
        <div class="legend-item">
          <i class="fas fa-arrow-right" style="color: #2c3e50;"></i>
          <span>Data Flow Direction</span>
        </div>
        <div class="legend-item">
          <i class="fas fa-user-graduate"></i>
          <span>Student - Registers, Attends Events, Makes Payments</span>
        </div>
        <div class="legend-item">
          <i class="fas fa-server"></i>
          <span>CSO System - Central Management Platform</span>
        </div>
        <div class="legend-item">
          <i class="fas fa-users-cog"></i>
          <span>CSO Officer - Manages Events and Tracks Activities</span>
        </div>
        <div class="legend-item">
          <i class="fas fa-user-shield"></i>
          <span>Admin - Views Reports and Analytics</span>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
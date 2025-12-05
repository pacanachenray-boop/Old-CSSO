<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CSSO - Entity Relationship Diagram</title>
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
  max-width: 1800px;
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

.erd-wrapper {
  background: #ffffff;
  border-radius: 12px;
  padding: 40px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  overflow-x: auto;
}

.erd-canvas {
  position: relative;
  min-width: 1600px;
  min-height: 1200px;
  background: #ffffff;
}

/* Table Styles */
.table-box {
  position: absolute;
  background: #ffffff;
  border: 2px solid #000;
  border-radius: 4px;
  font-size: 11px;
  box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.1);
  cursor: move;
  user-select: none;
}

.table-box:hover {
  box-shadow: 4px 4px 12px rgba(0, 229, 255, 0.3);
}

.table-box.dragging {
  opacity: 0.7;
  box-shadow: 6px 6px 20px rgba(0, 229, 255, 0.5);
}

.table-header {
  padding: 8px 12px;
  font-weight: 700;
  text-align: center;
  border-bottom: 2px solid #000;
  background: #00e5ff;
  color: #000;
}

.table-body {
  background: #ffffff;
}

.table-row {
  padding: 6px 12px;
  border-bottom: 1px solid #ddd;
  display: flex;
  align-items: center;
  gap: 8px;
}

.table-row:last-child {
  border-bottom: none;
}

.pk-badge {
  background: #000;
  color: #fff;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 9px;
  font-weight: 700;
}

.fk-badge {
  background: #666;
  color: #fff;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 9px;
  font-weight: 700;
}

/* Cardinality Labels */
.cardinality {
  position: absolute;
  background: #fff;
  padding: 4px 10px;
  border: 2px solid #00e5ff;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
  color: #000;
  white-space: nowrap;
  z-index: 10;
  cursor: move;
  user-select: none;
}

.cardinality:hover {
  background: #e0f7ff;
  box-shadow: 2px 2px 8px rgba(0, 229, 255, 0.3);
}

.cardinality.dragging {
  opacity: 0.7;
  box-shadow: 4px 4px 12px rgba(0, 229, 255, 0.5);
}

.legend-box {
  margin-top: 30px;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
  border-left: 4px solid #00e5ff;
}

.legend-title {
  font-size: 18px;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 15px;
}

.legend-items {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 12px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: #555;
}

.legend-item i {
  color: #00e5ff;
}

@media (max-width: 1400px) {
  .erd-canvas {
    transform: scale(0.8);
    transform-origin: top left;
  }
}

@media (max-width: 768px) {
  .erd-canvas {
    transform: scale(0.5);
    transform-origin: top left;
  }
}
</style>
</head>

<body>
<div class="container">
  <div class="header">
    <h1>CSSO Database - Entity Relationship Diagram</h1>
    <p>Computer Studies Student Organization Management System</p>
  </div>

  <div class="erd-wrapper">
    <div class="erd-canvas" id="erdCanvas">
      
      <!-- SVG for connection lines -->
      <svg id="connectionSvg" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0;">
      </svg>

      <!-- USERS Table -->
      <div class="table-box users-table" data-table="users" style="top: 20px; left: 20px; width: 180px;">
        <div class="table-header">USERS</div>
        <div class="table-body">
          <div class="table-row"><span class="pk-badge">PK</span> user_id</div>
          <div class="table-row">first_name</div>
          <div class="table-row">last_name</div>
          <div class="table-row">username</div>
          <div class="table-row">password</div>
          <div class="table-row">usertype</div>
          <div class="table-row">date_in</div>
          <div class="table-row">time_in</div>
          <div class="table-row">status</div>
        </div>
      </div>

      <!-- STUDENT_PROFILE Table -->
      <div class="table-box student-table" data-table="student" style="top: 300px; left: 280px; width: 180px;">
        <div class="table-header">STUDENT_PROFILE</div>
        <div class="table-body">
          <div class="table-row"><span class="pk-badge">PK</span> students_id</div>
          <div class="table-row"><span class="fk-badge">FK</span> user_id</div>
          <div class="table-row">FirstName</div>
          <div class="table-row">LastName</div>
          <div class="table-row">MI</div>
          <div class="table-row">Suffix</div>
          <div class="table-row">Course</div>
          <div class="table-row">YearLevel</div>
          <div class="table-row">Section</div>
          <div class="table-row">PhoneNumber</div>
          <div class="table-row">Gender</div>
          <div class="table-row">DOB</div>
          <div class="table-row">Age</div>
          <div class="table-row">Religion</div>
          <div class="table-row">EmailAddress</div>
          <div class="table-row">Street</div>
          <div class="table-row">Barangay</div>
          <div class="table-row">Municipality</div>
          <div class="table-row">Province</div>
          <div class="table-row">Zipcode</div>
        </div>
      </div>

      <!-- REGISTRATION Table -->
      <div class="table-box registration-table" data-table="registration" style="top: 360px; left: 480px; width: 180px;">
        <div class="table-header">REGISTRATION</div>
        <div class="table-body">
          <div class="table-row"><span class="pk-badge">PK</span> registration_no</div>
          <div class="table-row"><span class="fk-badge">FK</span> user_id</div>
          <div class="table-row"><span class="fk-badge">FK</span> students_id</div>
          <div class="table-row">registration_date</div>
          <div class="table-row">semester</div>
          <div class="table-row">membership_fee</div>
          <div class="table-row">amount</div>
          <div class="table-row">payment_type</div>
          <div class="table-row">payment_status</div>
          <div class="table-row">school_year</div>
        </div>
      </div>

      <!-- EVENT Table -->
      <div class="table-box event-table" data-table="event" style="top: 80px; left: 680px; width: 180px;">
        <div class="table-header">EVENT</div>
        <div class="table-body">
          <div class="table-row"><span class="pk-badge">PK</span> event_Name</div>
          <div class="table-row">event_Date</div>
          <div class="table-row">location</div>
          <div class="table-row">Time_Session</div>
          <div class="table-row">YearLevel</div>
          <div class="table-row">Semester</div>
          <div class="table-row">school_year</div>
        </div>
      </div>

      <!-- ATTENDANCE Table -->
      <div class="table-box attendance-table" data-table="attendance" style="top: 460px; left: 680px; width: 180px;">
        <div class="table-header">ATTENDANCE</div>
        <div class="table-body">
          <div class="table-row"><span class="pk-badge">PK</span> attendance_id</div>
          <div class="table-row"><span class="fk-badge">FK</span> UserID</div>
          <div class="table-row"><span class="fk-badge">FK</span> students_id</div>
          <div class="table-row"><span class="fk-badge">FK</span> registration_no</div>
          <div class="table-row">event_name</div>
          <div class="table-row">event_date</div>
          <div class="table-row">location</div>
          <div class="table-row">amLogin</div>
          <div class="table-row">amLogout</div>
          <div class="table-row">pmLogin</div>
          <div class="table-row">pmLogout</div>
          <div class="table-row">ExcuseLetter</div>
          <div class="table-row">TotalPenalty</div>
        </div>
      </div>

      <!-- FINES Table -->
      <div class="table-box fines-table" data-table="fines" style="top: 300px; left: 880px; width: 180px;">
        <div class="table-header">FINES</div>
        <div class="table-body">
          <div class="table-row"><span class="pk-badge">PK</span> fines_id</div>
          <div class="table-row"><span class="fk-badge">FK</span> user_id</div>
          <div class="table-row"><span class="fk-badge">FK</span> students_id</div>
          <div class="table-row"><span class="fk-badge">FK</span> registration_no</div>
          <div class="table-row"><span class="fk-badge">FK</span> attendance_id</div>
          <div class="table-row">event_name</div>
          <div class="table-row">event_date</div>
          <div class="table-row">location</div>
          <div class="table-row">PenaltyAmount</div>
        </div>
      </div>

      <!-- FINES_PAYMENTS Table -->
      <div class="table-box payment-table" data-table="payment" style="top: 60px; left: 1080px; width: 180px;">
        <div class="table-header">FINES_PAYMENTS</div>
        <div class="table-body">
          <div class="table-row"><span class="pk-badge">PK</span> payment_id</div>
          <div class="table-row"><span class="fk-badge">FK</span> fines_id</div>
          <div class="table-row"><span class="fk-badge">FK</span> students_id</div>
          <div class="table-row">payment_amount</div>
          <div class="table-row">penalty_amount</div>
          <div class="table-row">payment_type</div>
          <div class="table-row">balance</div>
          <div class="table-row">payment_status</div>
          <div class="table-row">payment_date</div>
        </div>
      </div>

      <!-- COMSERVICE Table -->
      <div class="table-box service-table" data-table="service" style="top: 360px; left: 1080px; width: 180px;">
        <div class="table-header">COMSERVICE</div>
        <div class="table-body">
          <div class="table-row"><span class="pk-badge">PK</span> service_id</div>
          <div class="table-row"><span class="fk-badge">FK</span> fines_id</div>
          <div class="table-row"><span class="fk-badge">FK</span> students_id</div>
          <div class="table-row">penalty_amount</div>
          <div class="table-row">total_hours</div>
          <div class="table-row">service_date</div>
          <div class="table-row">status</div>
          <div class="table-row">discount</div>
        </div>
      </div>

      <!-- FAMILY_BACKGROUND Table -->
      <div class="table-box family-table" data-table="family" style="top: 480px; left: 20px; width: 200px;">
        <div class="table-header">FAMILY_BACKGROUND</div>
        <div class="table-body">
          <div class="table-row"><span class="pk-badge">PK</span> fam_id</div>
          <div class="table-row"><span class="fk-badge">FK</span> students_id</div>
          <div class="table-row">father_name</div>
          <div class="table-row">father_occupation</div>
          <div class="table-row">mother_name</div>
          <div class="table-row">mother_occupation</div>
          <div class="table-row">phone_number</div>
          <div class="table-row">siblings_count</div>
          <div class="table-row">guardian_name</div>
          <div class="table-row">guardian_occupation</div>
          <div class="table-row">contact_number</div>
          <div class="table-row">street</div>
          <div class="table-row">barangay</div>
          <div class="table-row">municipality</div>
          <div class="table-row">province</div>
          <div class="table-row">zipcode</div>
        </div>
      </div>

      <!-- EDUCATIONAL_BACKGROUND Table -->
      <div class="table-box education-table" data-table="education" style="top: 920px; left: 20px; width: 220px;">
        <div class="table-header">EDUCATIONAL_BACKGROUND</div>
        <div class="table-body">
          <div class="table-row"><span class="pk-badge">PK</span> edu_id</div>
          <div class="table-row"><span class="fk-badge">FK</span> students_id</div>
          <div class="table-row">elementary</div>
          <div class="table-row">elem_year_grad</div>
          <div class="table-row">elem_received</div>
          <div class="table-row">junior_high</div>
          <div class="table-row">jr_high_grad</div>
          <div class="table-row">jr_received</div>
          <div class="table-row">senior_high</div>
          <div class="table-row">sr_high_grad</div>
          <div class="table-row">sr_received</div>
        </div>
      </div>

      <!-- Cardinality Labels -->
      <div class="cardinality" data-card="users-student-1"></div>
      <div class="cardinality" data-card="users-student-m"></div>
      <div class="cardinality" data-card="student-family-1"></div>
      <div class="cardinality" data-card="student-family-m"></div>
      <div class="cardinality" data-card="student-education-1"></div>
      <div class="cardinality" data-card="student-education-m"></div>
      <div class="cardinality" data-card="student-registration-1"></div>
      <div class="cardinality" data-card="student-registration-m"></div>
      <div class="cardinality" data-card="registration-attendance-1"></div>
      <div class="cardinality" data-card="registration-attendance-m"></div>
      <div class="cardinality" data-card="attendance-fines-1"></div>
      <div class="cardinality" data-card="attendance-fines-m"></div>
      <div class="cardinality" data-card="fines-payment-1a"></div>
      <div class="cardinality" data-card="fines-payment-1b"></div>
      <div class="cardinality" data-card="fines-service-1a"></div>
      <div class="cardinality" data-card="fines-service-1b"></div>
      <div class="cardinality" data-card="event-attendance-1"></div>
      <div class="cardinality" data-card="event-attendance-m"></div>

    </div>

    <div class="legend-box">
      <div class="legend-title"><i class="fas fa-info-circle"></i> Legend</div>
      <div class="legend-items">
        <div class="legend-item">
          <span class="pk-badge">PK</span> = Primary Key
        </div>
        <div class="legend-item">
          <span class="fk-badge">FK</span> = Foreign Key
        </div>
        <div class="legend-item">
          <i class="fas fa-database"></i> 10 Tables Total
        </div>
        <div class="legend-item">
          <i class="fas fa-link"></i> Cardinality: 1 = One, M = Many
        </div>
        <div class="legend-item">
          <i class="fas fa-arrows-alt-h"></i> One-to-Many Relationships (1:M)
        </div>
        <div class="legend-item">
          <i class="fas fa-exchange-alt"></i> One-to-One Relationships (1:1)
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Relationship definitions
const relationships = [
  { from: 'users', to: 'student', type: '1:M', cards: ['users-student-1', 'users-student-m'] },
  { from: 'student', to: 'family', type: '1:M', cards: ['student-family-1', 'student-family-m'] },
  { from: 'student', to: 'education', type: '1:M', cards: ['student-education-1', 'student-education-m'] },
  { from: 'student', to: 'registration', type: '1:M', cards: ['student-registration-1', 'student-registration-m'] },
  { from: 'registration', to: 'attendance', type: '1:M', cards: ['registration-attendance-1', 'registration-attendance-m'] },
  { from: 'attendance', to: 'fines', type: '1:M', cards: ['attendance-fines-1', 'attendance-fines-m'] },
  { from: 'fines', to: 'payment', type: '1:1', cards: ['fines-payment-1a', 'fines-payment-1b'] },
  { from: 'fines', to: 'service', type: '1:1', cards: ['fines-service-1a', 'fines-service-1b'] },
  { from: 'event', to: 'attendance', type: '1:M', cards: ['event-attendance-1', 'event-attendance-m'] }
];

// Drag functionality
let draggedElement = null;
let offsetX = 0;
let offsetY = 0;
let isDraggingCardinality = false;

// Add drag listeners to tables
document.querySelectorAll('.table-box').forEach(table => {
  table.addEventListener('mousedown', startDrag);
});

// Add drag listeners to cardinality labels
document.querySelectorAll('.cardinality').forEach(card => {
  card.addEventListener('mousedown', startCardinalityDrag);
});

function startDrag(e) {
  draggedElement = e.currentTarget;
  draggedElement.classList.add('dragging');
  isDraggingCardinality = false;
  
  const rect = draggedElement.getBoundingClientRect();
  const canvas = document.getElementById('erdCanvas').getBoundingClientRect();
  
  offsetX = e.clientX - rect.left;
  offsetY = e.clientY - rect.top;
  
  document.addEventListener('mousemove', drag);
  document.addEventListener('mouseup', stopDrag);
  e.preventDefault();
}

function startCardinalityDrag(e) {
  draggedElement = e.currentTarget;
  draggedElement.classList.add('dragging');
  isDraggingCardinality = true;
  
  const rect = draggedElement.getBoundingClientRect();
  const canvas = document.getElementById('erdCanvas').getBoundingClientRect();
  
  offsetX = e.clientX - rect.left;
  offsetY = e.clientY - rect.top;
  
  document.addEventListener('mousemove', drag);
  document.addEventListener('mouseup', stopDrag);
  e.preventDefault();
  e.stopPropagation();
}

function drag(e) {
  if (!draggedElement) return;
  
  const canvas = document.getElementById('erdCanvas');
  const canvasRect = canvas.getBoundingClientRect();
  
  let newLeft = e.clientX - canvasRect.left - offsetX;
  let newTop = e.clientY - canvasRect.top - offsetY;
  
  // Keep within bounds
  newLeft = Math.max(0, Math.min(newLeft, canvasRect.width - draggedElement.offsetWidth));
  newTop = Math.max(0, Math.min(newTop, canvasRect.height - draggedElement.offsetHeight));
  
  draggedElement.style.left = newLeft + 'px';
  draggedElement.style.top = newTop + 'px';
  
  // Only update connections if dragging a table
  if (!isDraggingCardinality) {
    updateConnections();
  }
}

function stopDrag() {
  if (draggedElement) {
    draggedElement.classList.remove('dragging');
    draggedElement = null;
  }
  isDraggingCardinality = false;
  document.removeEventListener('mousemove', drag);
  document.removeEventListener('mouseup', stopDrag);
}

// Get center point of a table
function getTableCenter(tableName) {
  const table = document.querySelector(`[data-table="${tableName}"]`);
  if (!table) return { x: 0, y: 0 };
  
  const rect = table.getBoundingClientRect();
  const canvas = document.getElementById('erdCanvas').getBoundingClientRect();
  
  return {
    x: rect.left - canvas.left + rect.width / 2,
    y: rect.top - canvas.top + rect.height / 2
  };
}

// Update connection lines only (not cardinality positions)
function updateConnections() {
  const svg = document.getElementById('connectionSvg');
  svg.innerHTML = '';
  
  relationships.forEach(rel => {
    const from = getTableCenter(rel.from);
    const to = getTableCenter(rel.to);
    
    // Create line
    const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    line.setAttribute('x1', from.x);
    line.setAttribute('y1', from.y);
    line.setAttribute('x2', to.x);
    line.setAttribute('y2', to.y);
    line.setAttribute('stroke', '#00e5ff');
    line.setAttribute('stroke-width', '3');
    svg.appendChild(line);
  });
}

// Initial setup - position cardinality labels
function initializeCardinality() {
  relationships.forEach(rel => {
    const from = getTableCenter(rel.from);
    const to = getTableCenter(rel.to);
    
    const midX = (from.x + to.x) / 2;
    const midY = (from.y + to.y) / 2;
    const angle = Math.atan2(to.y - from.y, to.x - from.x);
    const offset = 30;
    
    // First cardinality (near 'from' table)
    const card1 = document.querySelector(`[data-card="${rel.cards[0]}"]`);
    if (card1) {
      card1.textContent = '1';
      card1.style.left = (from.x + Math.cos(angle) * offset - 15) + 'px';
      card1.style.top = (from.y + Math.sin(angle) * offset - 10) + 'px';
    }
    
    // Second cardinality (near 'to' table)
    const card2 = document.querySelector(`[data-card="${rel.cards[1]}"]`);
    if (card2) {
      card2.textContent = rel.type.includes('M') ? 'M' : '1';
      card2.style.left = (to.x - Math.cos(angle) * offset - 15) + 'px';
      card2.style.top = (to.y - Math.sin(angle) * offset - 10) + 'px';
    }
  });
}

// Initial draw
initializeCardinality();
updateConnections();
</script>

</body>
</html>
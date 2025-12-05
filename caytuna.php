<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mikhaela Caytona - QA Tester & Assistant Developer</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: #ffffff;
  color: #000000;
  overflow-x: hidden;
}

/* Hero Section */
.hero-section {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px 40px;
  background: linear-gradient(135deg, #ffffff 0%, #ffe0f7 100%);
  position: relative;
  overflow: hidden;
}

.hero-section::before {
  content: '';
  position: absolute;
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, rgba(255, 0, 229, 0.1) 0%, transparent 70%);
  border-radius: 50%;
  top: -200px;
  right: -200px;
  animation: float 8s ease-in-out infinite;
}

.hero-section::after {
  content: '';
  position: absolute;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(255, 0, 229, 0.08) 0%, transparent 70%);
  border-radius: 50%;
  bottom: -100px;
  left: -100px;
  animation: float 6s ease-in-out infinite reverse;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0) translateX(0);
  }
  50% {
    transform: translateY(-30px) translateX(30px);
  }
}

.main-container {
  max-width: 1400px;
  width: 100%;
  position: relative;
  z-index: 1;
}

/* Top Section - Name & Title (CENTER) */
.top-section {
  text-align: center;
  margin-bottom: 50px;
  opacity: 0;
  animation: fadeInDown 1s ease-out 0.3s forwards;
}

@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-50px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.name-typewriter {
  font-size: 52px;
  font-weight: 900;
  color: #000000;
  margin-bottom: 15px;
  line-height: 1.2;
  position: relative;
  display: inline-block;
  overflow: hidden;
  border-right: 3px solid #ff00e5;
  white-space: nowrap;
  animation: typing 3s steps(30) 0.7s forwards, blink 0.75s step-end infinite;
  width: 0;
}

@keyframes typing {
  from {
    width: 0;
  }
  to {
    width: 100%;
  }
}

@keyframes blink {
  from, to {
    border-color: transparent;
  }
  50% {
    border-color: #ff00e5;
  }
}

.name-typewriter::after {
  content: '';
  position: absolute;
  bottom: -8px;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 5px;
  background: linear-gradient(90deg, #ff00e5 0%, #ff00d4 100%);
  border-radius: 3px;
}

.subtitle {
  font-size: 22px;
  font-weight: 600;
  color: #000000;
  margin-top: 25px;
  opacity: 0;
  animation: fadeInUp 0.8s ease-out 4s forwards;
}

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

/* Middle Section - Picture & Responsibilities (LEFT & RIGHT) */
.middle-section {
  display: grid;
  grid-template-columns: 420px 1fr;
  gap: 60px;
  align-items: start;
  margin-bottom: 50px;
}

/* LEFT - Picture & Personal Info */
.image-section {
  position: relative;
  opacity: 0;
  animation: slideInLeft 1s ease-out 4.2s forwards;
  perspective: 1200px;
}

@keyframes slideInLeft {
  from {
    opacity: 0;
    transform: translateX(-80px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.image-wrapper {
  position: relative;
  border-radius: 30px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(255, 0, 229, 0.3);
  transition: all 0.6s ease;
  width: 100%;
  height: auto;
  transform-style: preserve-3d;
  perspective: 1000px;
  margin-bottom: 25px;
}

.image-wrapper:hover {
  transform: rotateY(-15deg) rotateX(10deg) scale(1.05) translateZ(50px);
  box-shadow: 0 40px 100px rgba(255, 0, 229, 0.5), 
              -20px 0 60px rgba(255, 0, 229, 0.3),
              20px 20px 80px rgba(0, 0, 0, 0.3);
}

.image-wrapper::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(255, 0, 229, 0.2) 0%, transparent 50%);
  z-index: 1;
  opacity: 0;
  transition: opacity 0.4s ease;
}

.image-wrapper:hover::before {
  opacity: 1;
}

.main-image {
  width: 100%;
  height: auto;
  display: block;
  border-radius: 30px;
  object-fit: cover;
  transform: translateZ(30px);
  transition: transform 0.6s ease;
}

.image-wrapper:hover .main-image {
  transform: translateZ(80px);
}

.image-badge {
  position: absolute;
  bottom: 20px;
  left: 20px;
  background: rgba(255, 0, 229, 0.95);
  color: #ffffff;
  padding: 12px 24px;
  border-radius: 50px;
  font-weight: 700;
  font-size: 15px;
  z-index: 2;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
  animation: pulse 2s ease-in-out infinite;
  transform: translateZ(100px);
  transition: transform 0.6s ease;
}

.image-wrapper:hover .image-badge {
  transform: translateZ(150px) scale(1.1);
}

@keyframes pulse {
  0%, 100% {
    transform: translateZ(100px) scale(1);
  }
  50% {
    transform: translateZ(100px) scale(1.05);
  }
}

.personal-info {
  background: #f8f9fa;
  padding: 30px;
  border-radius: 20px;
  border-left: 5px solid #ff00e5;
  box-shadow: 0 5px 20px rgba(255, 0, 229, 0.1);
}

.info-item {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 15px;
  color: #2c2c2c;
  margin-bottom: 15px;
}

.info-item:last-child {
  margin-bottom: 0;
}

.info-item i {
  color: #000000;
  font-size: 20px;
  width: 30px;
  text-align: center;
}

.info-item strong {
  color: #000000;
  font-weight: 700;
  min-width: 100px;
}

/* RIGHT - Responsibilities */
.responsibilities-section {
  opacity: 0;
  animation: slideInRight 1s ease-out 4.4s forwards;
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(80px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.section-title {
  font-size: 32px;
  font-weight: 800;
  color: #000000;
  margin-bottom: 30px;
  position: relative;
  display: inline-block;
  width: 100%;
}

.section-title::after {
  content: '';
  position: absolute;
  bottom: -10px;
  left: 0;
  width: 100px;
  height: 5px;
  background: linear-gradient(90deg, #ff00e5 0%, #ff00d4 100%);
  border-radius: 3px;
}

.responsibilities-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 20px;
}

.responsibility-card {
  background: #f8f9fa;
  padding: 25px;
  border-radius: 18px;
  border-top: 5px solid #ff00e5;
  transition: all 0.4s ease;
  height: 100%;
}

.responsibility-card:hover {
  background: #ffe0f7;
  transform: translateY(-5px);
  box-shadow: 0 15px 40px rgba(255, 0, 229, 0.3);
}

.responsibility-card h3 {
  font-size: 18px;
  font-weight: 700;
  color: #000000;
  margin-bottom: 15px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.responsibility-card h3 i {
  color: #ff00e5;
  font-size: 24px;
}

.responsibility-card ul {
  list-style: none;
  padding-left: 0;
}

.responsibility-card ul li {
  font-size: 13px;
  color: #2c2c2c;
  line-height: 1.8;
  padding-left: 25px;
  position: relative;
  margin-bottom: 10px;
}

.responsibility-card ul li::before {
  content: '▸';
  position: absolute;
  left: 0;
  color: #ff00e5;
  font-weight: 700;
  font-size: 16px;
}

/* Bottom Section - Social Links (CENTER/HORIZONTAL) */
.social-section {
  text-align: center;
  margin-top: 50px;
  opacity: 0;
  animation: fadeInUp 0.8s ease-out 4.6s forwards;
}

.social-links {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  flex-wrap: wrap;
}

.social-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 15px 25px;
  background: #f8f9fa;
  border: 2px solid #ff00e5;
  border-radius: 12px;
  color: #000000;
  text-decoration: none;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.social-btn:hover {
  background: #ff00e5;
  color: #ffffff;
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(255, 0, 229, 0.4);
}

.social-btn i {
  font-size: 20px;
  color: #000000;
}

.social-btn:hover i {
  color: #ffffff;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .middle-section {
    grid-template-columns: 380px 1fr;
    gap: 40px;
  }
}

@media (max-width: 1024px) {
  .middle-section {
    grid-template-columns: 1fr;
    gap: 40px;
  }
  
  .image-section {
    max-width: 500px;
    margin: 0 auto;
  }
  
  .name-typewriter {
    font-size: 42px;
  }
  
  .subtitle {
    font-size: 20px;
  }
  
  .section-title {
    font-size: 26px;
  }
}

@media (max-width: 768px) {
  .hero-section {
    padding: 50px 20px;
  }
  
  .name-typewriter {
    font-size: 36px;
  }
  
  .subtitle {
    font-size: 18px;
  }
  
  .section-title {
    font-size: 24px;
  }
  
  .responsibility-card h3 {
    font-size: 16px;
  }
  
  .social-links {
    flex-direction: column;
    gap: 12px;
  }
  
  .social-btn {
    width: 100%;
    max-width: 300px;
  }
}
</style>
</head>

<body>
<section class="hero-section">
  <div class="main-container">
    
    <!-- TOP SECTION - Name & Title (CENTER) -->
    <div class="top-section">
      <h1 class="name-typewriter">Hello, It's me Mikhaela Caytona</h1>
      <p class="subtitle">QA Tester, Feature Reviewer & Assistant Developer</p>
    </div>

    <!-- MIDDLE SECTION - Picture & Personal Info (LEFT) | Responsibilities (RIGHT) -->
    <div class="middle-section">
      <!-- LEFT - Picture & Personal Info -->
      <div class="image-section">
        <div class="image-wrapper">
          <img src="../images/caytuna.png" class="main-image" alt="Mikhaela Caytona">
          <div class="image-badge">QA Tester</div>
        </div>
        
        <div class="personal-info">
          <div class="info-item">
            <i class="fas fa-calendar"></i>
            <strong>Age:</strong> 21
          </div>
          <div class="info-item">
            <i class="fas fa-birthday-cake"></i>
            <strong>Birthdate:</strong> October 21, 2004
          </div>
          <div class="info-item">
            <i class="fas fa-church"></i>
            <strong>Religion:</strong> Roman Catholic
          </div>
          <div class="info-item">
            <i class="fas fa-palette"></i>
            <strong>Habits/Interests:</strong> Make-up artistry & singing
          </div>
        </div>
      </div>

      <!-- RIGHT - Core Responsibilities -->
      <div class="responsibilities-section">
        <h2 class="section-title">Core Responsibilities & Contributions</h2>
        
        <div class="responsibilities-grid">
          <div class="responsibility-card">
            <h3><i class="fas fa-project-diagram"></i> System Planning & Analysis</h3>
            <ul>
              <li>Created the Gantt Chart to establish the project timeline, workflow, and development scheduling.</li>
              <li>Refined the ERD (Entity Relationship Diagram) to ensure accurate data structure and relationships.</li>
              <li>Conducted thorough System Design Reviews, aligning system features with project goals.</li>
            </ul>
          </div>
          
          <div class="responsibility-card">
            <h3><i class="fas fa-code"></i> Module Development</h3>
            <ul>
              <li>Developed the User Module, focusing on account creation, credentials, and role management.</li>
              <li>Built and structured the Students Profile Module for managing personal and academic information.</li>
            </ul>
          </div>
          
          <div class="responsibility-card">
            <h3><i class="fas fa-paint-brush"></i> UI Enhancements & Quality Improvements</h3>
            <ul>
              <li>Refined UI Designs, enhancing layout, clarity, and user flow for a smoother experience.</li>
              <li>Assisted in improving interface consistency across the system.</li>
            </ul>
          </div>
          
          <div class="responsibility-card">
            <h3><i class="fas fa-bug"></i> Quality Assurance & Testing</h3>
            <ul>
              <li>Served as the primary System Tester, checking for errors, bugs, and inconsistencies.</li>
              <li>Provided detailed feedback to ensure each module functions correctly before deployment.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    
    <!-- BOTTOM SECTION - Social Links (CENTER/HORIZONTAL) -->
    <div class="social-section">
      <div class="social-links">
        <a href="https://facebook.com" target="_blank" class="social-btn">
          <i class="fab fa-facebook"></i>
          Mikhaela Caytona
        </a>
        <a href="https://instagram.com" target="_blank" class="social-btn">
          <i class="fab fa-instagram"></i>
          Mikhaela Caytona
        </a>
        <a href="https://github.com" target="_blank" class="social-btn">
          <i class="fab fa-github"></i>
          Mikhaela Caytona
        </a>
        <a href="mailto:mikhaelacaytona@gmail.com" class="social-btn">
          <i class="fas fa-envelope"></i>
          mikhaelacaytona@gmail.com
        </a>
      </div>
    </div>
    
  </div>
</section>

</body>
</html>
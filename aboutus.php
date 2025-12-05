<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us - CSSO</title>
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
  background: linear-gradient(135deg, #ffffff 0%, #e0f7ff 100%);
  position: relative;
  overflow: hidden;
}

.hero-section::before {
  content: '';
  position: absolute;
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, rgba(0, 229, 255, 0.1) 0%, transparent 70%);
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
  background: radial-gradient(circle, rgba(0, 229, 255, 0.08) 0%, transparent 70%);
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

.container {
  max-width: 1400px;
  width: 100%;
  display: grid;
  grid-template-columns: 500px 1fr;
  gap: 60px;
  align-items: center;
  position: relative;
  z-index: 1;
}

/* Image Section */
.image-section {
  position: relative;
  opacity: 0;
  animation: slideInLeft 1s ease-out 0.3s forwards;
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
  box-shadow: 0 20px 60px rgba(0, 229, 255, 0.3);
  transition: all 0.4s ease;
  width: 100%;
  height: auto;
}

.image-wrapper:hover {
  transform: scale(1.03) translateY(-10px);
  box-shadow: 0 30px 80px rgba(0, 229, 255, 0.4);
}

.image-wrapper::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(0, 229, 255, 0.2) 0%, transparent 50%);
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
}

.image-badge {
  position: absolute;
  bottom: 20px;
  left: 20px;
  background: rgba(0, 229, 255, 0.95);
  color: #000000;
  padding: 12px 24px;
  border-radius: 50px;
  font-weight: 700;
  font-size: 15px;
  z-index: 2;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

/* Content Section */
.content-section {
  opacity: 0;
  animation: slideInRight 1s ease-out 0.5s forwards;
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

.title {
  font-size: 52px;
  font-weight: 900;
  color: #000000;
  margin-bottom: 15px;
  line-height: 1.2;
  position: relative;
  display: inline-block;
}

.title::after {
  content: '';
  position: absolute;
  bottom: -8px;
  left: 0;
  width: 80px;
  height: 5px;
  background: linear-gradient(90deg, #00e5ff 0%, #00d4ff 100%);
  border-radius: 3px;
}

.subtitle {
  font-size: 22px;
  font-weight: 600;
  color: #00e5ff;
  margin-bottom: 25px;
  opacity: 0;
  animation: fadeInUp 0.8s ease-out 0.7s forwards;
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

.description {
  font-size: 16px;
  line-height: 1.8;
  color: #2c2c2c;
  margin-bottom: 20px;
  text-align: justify;
  opacity: 0;
  animation: fadeInUp 0.8s ease-out 0.9s forwards;
}

.description strong {
  color: #000000;
  font-weight: 700;
}

.highlight-box {
  background: linear-gradient(135deg, #00e5ff 0%, #00d4ff 100%);
  color: #000000;
  padding: 22px 28px;
  border-radius: 18px;
  margin: 25px 0;
  font-size: 15px;
  line-height: 1.7;
  box-shadow: 0 10px 30px rgba(0, 229, 255, 0.3);
  opacity: 0;
  animation: fadeInUp 0.8s ease-out 1.1s forwards;
  font-weight: 500;
}

.instructor-name {
  background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
  color: #000000;
  padding: 4px 12px;
  border-radius: 8px;
  font-weight: 800;
  font-size: 17px;
  box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
  display: inline-block;
  animation: glow 2s ease-in-out infinite;
}

@keyframes glow {
  0%, 100% {
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
  }
  50% {
    box-shadow: 0 4px 25px rgba(255, 215, 0, 0.7);
  }
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 18px;
  margin-top: 30px;
  opacity: 0;
  animation: fadeInUp 0.8s ease-out 1.3s forwards;
}

.feature-card {
  background: #f8f9fa;
  padding: 22px;
  border-radius: 15px;
  border-left: 4px solid #00e5ff;
  transition: all 0.3s ease;
}

.feature-card:hover {
  background: #e0f7ff;
  transform: translateX(10px);
  box-shadow: 0 8px 25px rgba(0, 229, 255, 0.2);
}

.feature-card i {
  font-size: 28px;
  color: #00e5ff;
  margin-bottom: 12px;
}

.feature-card h3 {
  font-size: 17px;
  color: #000000;
  margin-bottom: 8px;
  font-weight: 700;
}

.feature-card p {
  font-size: 14px;
  color: #555555;
  line-height: 1.6;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .container {
    grid-template-columns: 450px 1fr;
    gap: 50px;
  }
}

@media (max-width: 1024px) {
  .container {
    grid-template-columns: 1fr;
    gap: 50px;
    padding: 40px 30px;
  }
  
  .image-section {
    max-width: 600px;
    margin: 0 auto;
  }
  
  .title {
    font-size: 42px;
  }
  
  .subtitle {
    font-size: 20px;
  }
  
  .description {
    font-size: 15px;
  }
}

@media (max-width: 768px) {
  .hero-section {
    padding: 50px 20px;
  }
  
  .title {
    font-size: 36px;
  }
  
  .subtitle {
    font-size: 18px;
  }
  
  .description {
    font-size: 14px;
  }
  
  .highlight-box {
    font-size: 14px;
    padding: 18px 22px;
  }
  
  .image-badge {
    font-size: 13px;
    padding: 10px 20px;
  }
  
  .features-grid {
    grid-template-columns: 1fr;
  }
}
</style>
</head>

<body>
<section class="hero-section">
  <div class="container">
    <!-- Image Section -->
    <div class="image-section">
      <div class="image-wrapper">
        <img src="../images/aboutus.jpg" class="main-image">
        </div>
      </div>
    </div>

    <!-- Content Section -->
    <div class="content-section">
      <h1 class="title">About Us</h1>
      <p class="subtitle">Building the Future of Student Organizations</p>
      
      <p class="description">
        We are <strong>third-year Bachelor of Science in Information Technology students from BSIT 3A</strong>, developing an innovative system titled <strong>"Computer Studies Student Organizations Management System."</strong>
      </p>
      
      <p class="description">
        This project was created as part of our course <strong>IM 103 – Advanced Database System 2</strong>, under the guidance of our instructor, <span class="instructor-name">Mr. Roland L. Vios</span>.
      </p>
      
      <div class="highlight-box">
        <strong>Our Mission:</strong> The system aims to streamline the management of student organizations within the College of Computer Studies by providing an efficient, automated platform for tracking members, handling event records, and maintaining organizational data.
      </div>
      
      <p class="description">
        Through this project, we applied <strong>advanced database concepts, data modeling, normalization, and system integration</strong> to deliver a functional and user-centered solution.
      </p>
      
      <div class="features-grid">
        <div class="feature-card">
          <i class="fas fa-code"></i>
          <h3>Technical Excellence</h3>
          <p>Applied advanced database concepts and system integration techniques</p>
        </div>
        
        <div class="feature-card">
          <i class="fas fa-users-cog"></i>
          <h3>Collaborative Skills</h3>
          <p>Demonstrated strong teamwork and project management abilities</p>
        </div>
        
        <div class="feature-card">
          <i class="fas fa-lightbulb"></i>
          <h3>Problem Solving</h3>
          <p>Created systems that solve real-world organizational challenges</p>
        </div>
        
        <div class="feature-card">
          <i class="fas fa-graduation-cap"></i>
          <h3>Continuous Learning</h3>
          <p>Committed to growth and excellence in information technology</p>
        </div>
      </div>
    </div>
  </div>
</section>

</body>
</html>
<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

if(isset($_POST['login'])) {
  $username = $_POST['username'];
  $password = md5($_POST['password']); 
  $sql ="SELECT ID FROM tbladmin WHERE UserName=:username and Password=:password LIMIT 1";
  $query=$dbh->prepare($sql);
  $query->bindParam(':username', $username, PDO::PARAM_STR);
  $query->bindParam(':password', $password, PDO::PARAM_STR);
  $query->execute();
  $result=$query->fetch(PDO::FETCH_OBJ);

  if($result){
    $_SESSION['sturecmsaid']=$result->ID;
    if(!empty($_POST["remember"])) {
      setcookie("user_login", $_POST["username"], time()+ (10 * 365 * 24 * 60 * 60), "/");
      setcookie("userpassword", $_POST["password"], time()+ (10 * 365 * 24 * 60 * 60), "/");
    } else {
      if(isset($_COOKIE["user_login"])) setcookie("user_login","", time()-3600, "/");
      if(isset($_COOKIE["userpassword"])) setcookie("userpassword","", time()-3600, "/");
    }
    $_SESSION['login']=$_POST['username'];
    echo "<script>location='dashboard.php';</script>";
  } else {
    echo "<script>alert('Invalid Details');</script>";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Management System | Login</title>
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Particles.js -->
  <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>

  <style>
    body {
      margin: 0;
      padding: 0;
      height: 100vh;
      font-family: 'Plus Jakarta Sans', sans-serif;
      overflow: hidden;
      background: radial-gradient(circle at 50% 50%, #1e1e2f 0%, #0c0d14 100%);
    }

    #particles-js {
      position: absolute;
      width: 100%;
      height: 100%;
      z-index: 1;
    }

    .page {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      position: relative;
      z-index: 2;
    }

    .card {
      background: rgba(255, 255, 255, 0.96);
      backdrop-filter: blur(16px);
      padding: 42px 36px;
      border-radius: 22px;
      width: 380px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.1);
      text-align: center;
      color: #1e293b;
      animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .logo-container {
      text-align: center;
      margin-bottom: 18px;
    }

    .logo-container img {
      width: 90px;
      height: auto;
      filter: drop-shadow(0 4px 12px rgba(79, 70, 229, 0.25));
      transition: transform 0.3s ease;
    }

    .logo-container:hover img {
      transform: scale(1.05);
    }

    .brand {
      font-size: 21px;
      font-weight: 800;
      margin-bottom: 6px;
      letter-spacing: -0.02em;
      color: #0f172a;
    }

    .subtitle {
      font-size: 13.5px;
      margin-bottom: 28px;
      color: #64748b;
      font-weight: 500;
    }

    .field {
      position: relative;
      margin-bottom: 22px;
    }

    .input {
      width: 100%;
      padding: 14px 16px;
      border: 1.5px solid #e2e8f0;
      border-radius: 12px;
      background: #f8fafc;
      color: #0f172a;
      font-size: 14px;
      font-weight: 500;
      outline: none;
      transition: all 0.25s ease;
      box-sizing: border-box;
    }

    .input:focus {
      background: #ffffff;
      border-color: #6366f1;
      box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
    }

    .label {
      position: absolute;
      top: 50%;
      left: 16px;
      transform: translateY(-50%);
      font-size: 13.5px;
      color: #94a3b8;
      font-weight: 500;
      transition: 0.25s ease;
      pointer-events: none;
      background: transparent;
    }

    .input:focus + .label,
    .input:not(:placeholder-shown) + .label {
      top: -9px;
      left: 12px;
      font-size: 11.5px;
      font-weight: 700;
      color: #4f46e5;
      background: #ffffff;
      padding: 0 6px;
      border-radius: 4px;
    }

    .btn {
      width: 100%;
      padding: 13px;
      border: none;
      border-radius: 12px;
      background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
      color: #ffffff;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
      transition: all 0.25s ease;
    }

    .btn:hover {
      background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%);
      box-shadow: 0 6px 20px rgba(79, 70, 229, 0.5);
      transform: translateY(-2px);
    }

    .btn:active {
      transform: translateY(0);
    }

    .row {
      margin-top: 18px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 13px;
      font-weight: 500;
    }

    .checkbox {
      color: #475569;
      cursor: pointer;
      display: flex;
      align-items: center;
    }

    .checkbox input {
      margin-right: 8px;
      accent-color: #4f46e5;
    }

    .link {
      color: #4f46e5;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s;
    }

    .link:hover {
      color: #3730a3;
      text-decoration: underline;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(24px) scale(0.97); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }
  </style>
</head>
<body>

  <!-- Particles Background -->
  <div id="particles-js"></div>
  <div class="page">
    <div class="card">
      <!-- Logo -->
      <div class="logo-container">
        <img src="images/logo/techisland.png" alt="Logo">
      </div>

      <div class="brand">Student Management System</div>
      <div class="subtitle">Welcome back, please login</div>

      <form id="login" method="post" name="login" autocomplete="off">
        <div class="field">
          <input id="username" type="text" name="username" required class="input" placeholder=" "
            value="<?php if(isset($_COOKIE['user_login'])) echo htmlspecialchars($_COOKIE['user_login']); ?>">
          <label for="username" class="label">Username</label>
        </div>
        <div class="field">
          <input id="password" type="password" name="password" required class="input" placeholder=" "
            value="<?php if(isset($_COOKIE['userpassword'])) echo htmlspecialchars($_COOKIE['userpassword']); ?>">
          <label for="password" class="label">Password</label>
        </div>
        <button type="submit" name="login" class="btn">Sign In</button>

        <div class="row">
          <label class="checkbox">
            <input type="checkbox" name="remember" <?php if(isset($_COOKIE["user_login"])) echo "checked"; ?>> Remember me
          </label>
          <a href="forgot-password.php" class="link">Forgot password?</a>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Particles Config -->
  <script>
  particlesJS("particles-js", {
    "particles": {
      "number": { "value": 100, "density": { "enable": true, "value_area": 800 } },
      "color": { "value": "#818cf8" },
      "shape": { "type": "circle" },
      "opacity": { "value": 0.4 },
      "size": { "value": 3, "random": true },
      "line_linked": { "enable": true, "distance": 150, "color": "#6366f1", "opacity": 0.35, "width": 1 },
      "move": { "enable": true, "speed": 4 }
    },
    "interactivity": {
      "detect_on": "canvas",
      "events": {
        "onhover": { "enable": true, "mode": "grab" }, 
        "onclick": { "enable": true, "mode": ["push", "repulse"] } 
      },
      "modes": {
        "grab": { "distance": 200, "line_linked": { "opacity": 0.7 } },
        "push": { "particles_nb": 4 },
        "repulse": { "distance": 200, "duration": 0.4 }
      }
    },
    "retina_detect": true
  });
  </script>

</body>
</html>

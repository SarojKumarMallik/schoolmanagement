<link rel="stylesheet" href="css/clean-theme.css?v=<?php echo time(); ?>">
<nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
        <div class="navbar-brand-wrapper d-flex align-items-center">
          <a class="navbar-brand brand-logo" href="dashboard.php">
            <img src="images/logo/logo-horizontal.png" alt="TECHISLAND" />
          </a>
          <a class="navbar-brand brand-logo-mini" href="dashboard.php"><img src="images/logo/logo-mini.png" alt="TECHISLAND" style="height: 38px; width: auto;" /></a>
        </div><?php
if (!isset($dbh)) {
    include_once(__DIR__ . '/dbconnection.php');
}
         $aid= $_SESSION['sturecmsaid'];
$sql="SELECT * from tbladmin where ID=:aid";

$query = $dbh -> prepare($sql);
$query->bindParam(':aid',$aid,PDO::PARAM_STR);
$query->execute();
$results=$query->fetchAll(PDO::FETCH_OBJ);

$cnt=1;
if($query->rowCount() > 0)
{
foreach($results as $row)
{               ?>
        <div class="navbar-menu-wrapper d-flex align-items-center flex-grow-1">
          <h5 class="mb-0 font-weight-medium d-none d-lg-flex align-items-center">
            <span class="admin-badge-pulse mr-2"></span>
            Welcome back, <strong style="color: #4f46e5; margin: 0 4px;"><?php echo htmlentities($row->AdminName); ?></strong> 👋
          </h5>
          <ul class="navbar-nav navbar-nav-right ml-auto">
            <li class="nav-item dropdown d-none d-xl-inline-flex user-dropdown">
              <a class="nav-link dropdown-toggle" id="UserDropdown" href="#" data-toggle="dropdown" aria-expanded="false">
                <img class="img-xs rounded-circle ml-2" src="images/faces/face8.jpg" alt="Profile image"> <span class="font-weight-normal"> <?php  echo htmlentities($row->AdminName);?> </span></a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                <div class="dropdown-header text-center">
                  <img class="img-md rounded-circle" src="images/faces/face8.jpg" alt="Profile image" style="width: 60px; height: 60px; max-width: 60px; max-height: 60px; object-fit: cover; border-radius: 50%; margin: 0 auto 10px auto; display: block; border: 2px solid #6366f1;">
                  <p class="mb-1 mt-1" style="font-weight: 700; color: #fff; font-size: 14px;"><?php  echo htmlentities($row->AdminName);?></p>
                  <p class="font-weight-light text-muted mb-0" style="color: #94a3b8; font-size: 12px;"><?php  echo htmlentities($row->Email);?></p>
                </div><?php $cnt=$cnt+1;}} ?>
                <a class="dropdown-item" href="profile.php"><i class="dropdown-item-icon icon-user text-primary"></i> My Profile</a>
                <a class="dropdown-item" href="change-password.php"><i class="dropdown-item-icon icon-energy text-primary"></i> Setting</a>
                <a class="dropdown-item" href="logout.php"><i class="dropdown-item-icon icon-power text-danger"></i> Sign Out</a>
              </div>
            </li>
          </ul>
          <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
            <span class="icon-menu"></span>
          </button>
        </div>
      </nav>
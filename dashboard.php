<?php
session_start();
//error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid'] == 0)) {
  header('location:logout.php');
  exit();
} else {

  // Fetch KPI Counts
  // 1. Total Staff
  $sql1 = "SELECT * from tblstaff";
  $query1 = $dbh->prepare($sql1);
  $query1->execute();
  $totstaff = $query1->rowCount();

  // 2. Total Students
  $sql2 = "SELECT * from tblstudent where isactive=1";
  $query2 = $dbh->prepare($sql2);
  $query2->execute();
  $totstu = $query2->rowCount();

  // 3. Total Classes
  $sql3 = "SELECT * from tblclass";
  $query3 = $dbh->prepare($sql3);
  $query3->execute();
  $totclass = $query3->rowCount();

  // 4. Total Notices
  $sql4 = "SELECT * from tblnotice";
  $query4 = $dbh->prepare($sql4);
  $query4->execute();
  $totnotices = $query4->rowCount();

  // 5. Class-wise Student Distribution for Chart
  $sqlChart = "SELECT c.ClassName, COUNT(s.ID) as total_students
               FROM tblclass c
               LEFT JOIN tblstudent s ON c.ClassName = s.StudentClass AND s.isactive=1
               GROUP BY c.ClassName";
  $queryChart = $dbh->prepare($sqlChart);
  $queryChart->execute();
  $resultsChart = $queryChart->fetchAll(PDO::FETCH_OBJ);

  $classes = [];
  $studentCounts = [];
  foreach ($resultsChart as $row) {
    $classes[] = $row->ClassName;
    $studentCounts[] = $row->total_students;
  }

  // 6. Recently Enrolled Students
  $sqlRecent = "SELECT StudentName, StudentClass, Gender, DateofAdmission, ID 
                FROM tblstudent 
                WHERE isactive=1 
                ORDER BY ID DESC LIMIT 5";
  $queryRecent = $dbh->prepare($sqlRecent);
  $queryRecent->execute();
  $recentStudents = $queryRecent->fetchAll(PDO::FETCH_OBJ);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <title>Student Management System | Dashboard</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
  <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
  <!-- Layout styles -->
  <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
  <!-- jQuery + CounterUp -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/waypoints/4.0.1/jquery.waypoints.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Counter-Up/1.0.0/jquery.counterup.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
  <div class="container-scroller">
    <!-- Navbar -->
    <?php include_once('includes/header.php'); ?>
    
    <div class="container-fluid page-body-wrapper">
      <!-- Sidebar -->
      <?php include_once('includes/sidebar.php'); ?>

      <!-- Main Content Panel -->
      <div class="main-panel">
        <div class="content-wrapper">
          
          <!-- Top Greeting & Overview Banner -->
          <div class="dashboard-header-card">
            <div class="d-sm-flex align-items-center justify-content-between flex-wrap gap-2">
              <div>
                <h4 class="font-weight-bold mb-1" style="color: #0f172a; letter-spacing: -0.02em;">
                  Academic Overview & Metrics
                </h4>
                <p class="text-muted mb-0" style="font-size: 13px;">
                  Key institutional summaries, realtime metrics, and administrative shortcuts
                </p>
              </div>
              <div class="d-flex align-items-center mt-3 mt-sm-0 flex-wrap gap-2">
                <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 600; padding: 7px 14px; font-size: 12px; border: 1px solid #e2e8f0;">
                  📅 <?php echo date('D, d M Y'); ?>
                </span>
                <span class="badge badge-success ml-2" style="padding: 7px 14px; font-size: 12px;">
                  ● System Online
                </span>
                <button class="btn btn-sm ml-2" onclick="location.reload()" style="background: #ffffff; color: #475569; border: 1.5px solid #e2e8f0; border-radius: 8px; font-weight: 600; padding: 6px 14px;">
                  ↻ Refresh
                </button>
              </div>
            </div>
          </div>

          <!-- 4 Executive KPI Stat Cards -->
          <div class="row">
            
            <!-- Card 1: Total Students -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="stat-kpi-card kpi-card-indigo">
                <div>
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="stat-label-text">Total Students</div>
                      <div class="stat-value-text text-indigo">
                        <span class="counter"><?php echo htmlentities($totstu); ?></span>
                      </div>
                    </div>
                    <div class="stat-icon-badge badge-indigo">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 0 0-5-4M9 20H4v-2a4 4 0 0 1 5-4m4-4a4 4 0 1 1-8 0 4 4 0 0 1 8 0zm6 0a4 4 0 1 1-8 0 4 4 0 0 1 8 0z" />
                      </svg>
                    </div>
                  </div>
                </div>
                <a href="manage-students.php" class="stat-action-footer footer-indigo">
                  <span>Manage Students</span>
                  <span class="action-circle"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></span>
                </a>
              </div>
            </div>

            <!-- Card 2: Total Staff -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="stat-kpi-card kpi-card-emerald">
                <div>
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="stat-label-text">Total Staff</div>
                      <div class="stat-value-text text-emerald">
                        <span class="counter"><?php echo htmlentities($totstaff); ?></span>
                      </div>
                    </div>
                    <div class="stat-icon-badge badge-emerald">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7h-4V4a1 1 0 0 0-1-1h-6a1 1 0 0 0-1 1v3H4a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2zM10 5h4v2h-4V5z"/>
                      </svg>
                    </div>
                  </div>
                </div>
                <a href="manage-staff.php" class="stat-action-footer footer-emerald">
                  <span>Staff Directory</span>
                  <span class="action-circle"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></span>
                </a>
              </div>
            </div>

            <!-- Card 3: Total Classes -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="stat-kpi-card kpi-card-purple">
                <div>
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="stat-label-text">Total Classes</div>
                      <div class="stat-value-text text-purple">
                        <span class="counter"><?php echo htmlentities($totclass); ?></span>
                      </div>
                    </div>
                    <div class="stat-icon-badge badge-purple">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                      </svg>
                    </div>
                  </div>
                </div>
                <a href="manage-class.php" class="stat-action-footer footer-purple">
                  <span>Manage Classes</span>
                  <span class="action-circle"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></span>
                </a>
              </div>
            </div>

            <!-- Card 4: Notices & Bulletins -->
            <div class="col-xl-3 col-md-6 mb-4">
              <div class="stat-kpi-card kpi-card-amber">
                <div>
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="stat-label-text">Notice Board</div>
                      <div class="stat-value-text text-amber">
                        <span class="counter"><?php echo htmlentities($totnotices); ?></span>
                      </div>
                    </div>
                    <div class="stat-icon-badge badge-amber">
                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 0 1-3.417.592l-2.147-6.15M18 13a3 3 0 1 0 0-6M5.436 13.683A4.001 4.001 0 0 1 7 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 0 1-1.564-.317z"/>
                      </svg>
                    </div>
                  </div>
                </div>
                <a href="manage-notice.php" class="stat-action-footer footer-amber">
                  <span>View Notice Board</span>
                  <span class="action-circle"><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg></span>
                </a>
              </div>
            </div>

          </div>

          <!-- Middle Section: Chart (8 cols) & Quick Navigation Hub (4 cols) -->
          <div class="row">
            
            <!-- Chart Widget -->
            <div class="col-lg-8 mb-4">
              <div class="card h-100" style="border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.04);">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2" style="background: #ffffff; border-bottom: 1px solid #f1f5f9; padding: 18px 24px;">
                  <div>
                    <h5 class="mb-0 fw-bold" style="color: #0f172a; font-size: 15.5px; display: flex; align-items: center; gap: 8px;">
                      <span style="display: inline-flex; width: 30px; height: 30px; border-radius: 8px; background: #eef2ff; color: #4f46e5; align-items: center; justify-content: center; font-size: 15px;">📊</span>
                      Class-wise Student Distribution
                    </h5>
                    <p class="text-muted mb-0" style="font-size: 12px; margin-top: 2px;">
                      Comparative enrollment strength across registered standards
                    </p>
                  </div>
                  <span class="badge" style="background: #eef2ff; color: #4f46e5; font-size: 11.5px; padding: 6px 12px; font-weight: 600;">
                    Active Enrollment
                  </span>
                </div>
                <div class="card-body" style="padding: 24px;">
                  <div style="position: relative; height: 260px; width: 100%;">
                    <canvas id="classChart"></canvas>
                  </div>
                </div>
              </div>
            </div>

            <!-- Quick Action Hub -->
            <div class="col-lg-4 mb-4">
              <div class="card h-100" style="border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.04);">
                <div class="card-header" style="background: #ffffff; border-bottom: 1px solid #f1f5f9; padding: 18px 24px;">
                  <h5 class="mb-0 fw-bold" style="color: #0f172a; font-size: 15.5px; display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-flex; width: 30px; height: 30px; border-radius: 8px; background: #fef3c7; color: #b45309; align-items: center; justify-content: center; font-size: 15px;">⚡</span>
                    Quick Navigation
                  </h5>
                  <p class="text-muted mb-0" style="font-size: 12px; margin-top: 2px;">
                    Frequent administrative shortcuts
                  </p>
                </div>
                <div class="card-body" style="padding: 20px 24px;">
                  
                  <a href="add-students.php" class="quick-action-tile">
                    <div class="action-icon-box" style="background: #eef2ff; color: #4f46e5;">
                      📝
                    </div>
                    <div>
                      <span class="action-title-text">Add New Student</span>
                      <span class="action-desc-text">Register a new student admission</span>
                    </div>
                    <span class="action-arrow-icon">›</span>
                  </a>

                  <a href="manage-fees.php" class="quick-action-tile">
                    <div class="action-icon-box" style="background: #ecfdf5; color: #059669;">
                      💳
                    </div>
                    <div>
                      <span class="action-title-text">Fee Management</span>
                      <span class="action-desc-text">Collect admission & monthly fees</span>
                    </div>
                    <span class="action-arrow-icon">›</span>
                  </a>

                  <a href="save_attendance.php" class="quick-action-tile">
                    <div class="action-icon-box" style="background: #f5f3ff; color: #7c3aed;">
                      📅
                    </div>
                    <div>
                      <span class="action-title-text">Daily Attendance</span>
                      <span class="action-desc-text">Mark student & staff attendance</span>
                    </div>
                    <span class="action-arrow-icon">›</span>
                  </a>

                  <a href="issue_stock.php" class="quick-action-tile">
                    <div class="action-icon-box" style="background: #fffbeb; color: #d97706;">
                      📦
                    </div>
                    <div>
                      <span class="action-title-text">Issue Stock Items</span>
                      <span class="action-desc-text">Distribute books, uniform & kits</span>
                    </div>
                    <span class="action-arrow-icon">›</span>
                  </a>

                </div>
              </div>
            </div>

          </div>

          <!-- Bottom Row: Recently Enrolled Students Table -->
          <div class="row">
            <div class="col-12 mb-4">
              <div class="card" style="border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.04);">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2" style="background: #ffffff; border-bottom: 1px solid #f1f5f9; padding: 18px 24px;">
                  <div>
                    <h5 class="mb-0 fw-bold" style="color: #0f172a; font-size: 15.5px; display: flex; align-items: center; gap: 8px;">
                      <span style="display: inline-flex; width: 30px; height: 30px; border-radius: 8px; background: #ecfdf5; color: #059669; align-items: center; justify-content: center; font-size: 15px;">🎓</span>
                      Recently Enrolled Students
                    </h5>
                    <p class="text-muted mb-0" style="font-size: 12px; margin-top: 2px;">
                      Latest students admitted to the institution
                    </p>
                  </div>
                  <a href="manage-students.php" style="color: #4f46e5; font-size: 13px; font-weight: 600; text-decoration: none;">
                    View All Students →
                  </a>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table mb-0">
                      <thead>
                        <tr>
                          <th>Student Name</th>
                          <th>Class</th>
                          <th>Gender</th>
                          <th>Admission Date</th>
                          <th>Status</th>
                          <th style="text-align: right;">Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        if (!empty($recentStudents)) {
                          $badgeColors = ['#eef2ff' => '#4f46e5', '#ecfdf5' => '#059669', '#f5f3ff' => '#7c3aed', '#fffbeb' => '#d97706'];
                          $keys = array_keys($badgeColors);
                          $idx = 0;
                          foreach ($recentStudents as $stu) {
                            $initials = '';
                            $parts = explode(' ', trim($stu->StudentName));
                            if (count($parts) >= 2) {
                              $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
                            } else {
                              $initials = strtoupper(substr($stu->StudentName, 0, 2));
                            }
                            $bg = $keys[$idx % count($keys)];
                            $fg = $badgeColors[$bg];
                            $idx++;
                            
                            $dateFmt = !empty($stu->DateofAdmission) ? date('d M Y, h:i A', strtotime($stu->DateofAdmission)) : 'N/A';
                            $genderClass = strtolower($stu->Gender) == 'female' ? 'background: #fdf2f8; color: #db2777;' : 'background: #eff6ff; color: #2563eb;';
                        ?>
                        <tr>
                          <td>
                            <div class="d-flex align-items-center">
                              <span class="avatar-initials-circle" style="background: <?php echo $bg; ?>; color: <?php echo $fg; ?>;">
                                <?php echo $initials; ?>
                              </span>
                              <span style="font-weight: 600; color: #0f172a;">
                                <?php echo htmlentities($stu->StudentName); ?>
                              </span>
                            </div>
                          </td>
                          <td>
                            <span class="badge" style="background: #eef2ff; color: #4f46e5; font-weight: 600; font-size: 11.5px; padding: 4px 10px;">
                              <?php echo htmlentities($stu->StudentClass); ?>
                            </span>
                          </td>
                          <td>
                            <span class="badge" style="<?php echo $genderClass; ?> font-weight: 600; font-size: 11px; padding: 4px 10px;">
                              <?php echo htmlentities($stu->Gender); ?>
                            </span>
                          </td>
                          <td style="color: #64748b; font-size: 13px;">
                            <?php echo $dateFmt; ?>
                          </td>
                          <td>
                            <span class="badge badge-success" style="padding: 4px 10px; font-size: 11px;">
                              Active
                            </span>
                          </td>
                          <td style="text-align: right;">
                            <a href="edit-student-detail.php?editid=<?php echo htmlentities($stu->ID); ?>" class="btn btn-sm btn-outline-primary" style="padding: 4px 12px; font-size: 12px; border-radius: 6px;">
                              Edit Details
                            </a>
                          </td>
                        </tr>
                        <?php } } else { ?>
                        <tr>
                          <td colspan="6" class="text-center py-4 text-muted">
                            No student enrollments found.
                          </td>
                        </tr>
                        <?php } ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
        <!-- content-wrapper ends -->
        
        <!-- Footer -->
        <?php include_once('includes/footer.php'); ?>
      </div>
      <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->

  <!-- Counter Animation & Chart.js Config -->
  <script>
    $(document).ready(function () {
      $('.counter').counterUp({
        delay: 10,
        time: 1000
      });
    });

    document.addEventListener("DOMContentLoaded", function () {
      const ctx = document.getElementById('classChart').getContext('2d');

      const chartData = {
        labels: <?php echo json_encode($classes); ?>,
        datasets: [{
          label: 'Students Enrolled',
          data: <?php echo json_encode($studentCounts, JSON_NUMERIC_CHECK); ?>,
          backgroundColor: [
            'rgba(79, 70, 229, 0.85)',
            'rgba(6, 182, 212, 0.85)',
            'rgba(16, 185, 129, 0.85)',
            'rgba(245, 158, 11, 0.85)',
            'rgba(139, 92, 246, 0.85)',
            'rgba(236, 72, 153, 0.85)'
          ],
          borderColor: [
            '#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'
          ],
          borderWidth: 1.5,
          borderRadius: 8,
          borderSkipped: false
        }]
      };

      new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: '#0f172a',
              titleFont: { family: 'Plus Jakarta Sans', size: 13, weight: 'bold' },
              bodyFont: { family: 'Plus Jakarta Sans', size: 12 },
              padding: 12,
              cornerRadius: 8,
              boxPadding: 4
            }
          },
          scales: {
            x: {
              grid: { display: false },
              ticks: { font: { family: 'Plus Jakarta Sans', size: 12, weight: '600' }, color: '#64748b' }
            },
            y: {
              beginAtZero: true,
              grid: { color: '#f1f5f9' },
              ticks: { stepSize: 1, font: { family: 'Plus Jakarta Sans', size: 12 }, color: '#64748b' }
            }
          }
        }
      });
    });
  </script>

  <!-- plugins:js -->
  <script src="vendors/js/vendor.bundle.base.js"></script>
  <script src="js/off-canvas.js"></script>
  <script src="js/misc.js"></script>
</body>

</html>
<?php } ?>

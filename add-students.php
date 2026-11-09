<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']==0)) {
  header('location:logout.php');
  } else{
   if(isset($_POST['submit']))
  {
 $stuname=$_POST['stuname'];
 $stuemail=$_POST['stuemail'];
 $stuclass=$_POST['stuclass'];
 $stusection=$_POST['stusection'];
 $gender=$_POST['gender'];
 $dob=$_POST['dob'];
 $stuid=$_POST['stuid'];
 $bloodGroup=$_POST['bldgr'];
 $address=$_POST['address'];

//  $uname=$_POST['uname'];
//  $password=md5($_POST['password']);
 $image=$_FILES["image"]["name"];
 $ret="select UserName from tblstudent where UserName=:uname || StuID=:stuid";
 $query= $dbh -> prepare($ret);
$query->bindParam(':uname',$uname,PDO::PARAM_STR);
$query->bindParam(':stuid',$stuid,PDO::PARAM_STR);
$query-> execute();
     $results = $query -> fetchAll(PDO::FETCH_OBJ);
if($query -> rowCount() == 0)
{
$extension = substr($image,strlen($image)-4,strlen($image));
$allowed_extensions = array(".jpg","jpeg",".png",".gif");
if(!in_array($extension,$allowed_extensions))
{
echo "<script>alert('Logo has Invalid format. Only jpg / jpeg/ png /gif format allowed');</script>";
}
else
{
$image=md5($image).time().$extension;
move_uploaded_file($_FILES["image"]["tmp_name"],"images/".$image);
$address=$_POST['address'];
$fname=$_POST['fname'];
$faddhar=$_POST['faddhar'];
$fimage=$_FILES["fimage"]["name"];
$fimage=md5($fimage).time().$extension;
move_uploaded_file($_FILES["fimage"]["tmp_name"],"images/".$fimage);
$mname=$_POST['mname'];
$maddhar=$_POST['maddhar'];
$mimage=$_FILES["mimage"]["name"];
$fmimage=md5($mimage).time().$extension;
move_uploaded_file($_FILES["mimage"]["tmp_name"],"images/".$mimage);
$connum=$_POST['connum'];
$altconnum=$_POST['altconnum'];
$addDate=$_POST['adddate'];
$joindate=$_POST['joindate'];
$addAmt=$_POST['addamt'];
$dueAmt=$_POST['dueamt'];
$book=$_POST['book'];
$bag=$_POST['bag'];
$uniform=$_POST['uniform'];
$idCard=$_POST['idcard'];
$belt=$_POST['stock_belt'];
$sql="insert into tblstudent(StudentName,StudentEmail,StudentClass,StudentSection,Gender,DOB,StuID,FatherName,MotherName,ContactNumber,AltenateNumber,Address,Image,fatherAddhar,fatherImage,motherAddhar,motherImage,addDate,joindate,addAmt,dueAmt,book,bag,uniform,idCard,belt)values(:stuname,:stuemail,:stuclass,:stusection,:gender,:dob,:stuid,:fname,:mname,:connum,:altconnum,:address,:image,:fatherAddhar,:fatherImage,:motherAddhar,:motherImage,:addDate,:joindate,:addAmt,:dueAmt,:book,:bag,:uniform,:idCard,:belt)";
$query=$dbh->prepare($sql);
$query->bindParam(':stuname',$stuname,PDO::PARAM_STR);
$query->bindParam(':stuemail',$stuemail,PDO::PARAM_STR);
$query->bindParam(':stuclass',$stuclass,PDO::PARAM_STR);
$query->bindParam(':stusection',$stusection,PDO::PARAM_STR);
$query->bindParam(':gender',$gender,PDO::PARAM_STR);
$query->bindParam(':dob',$dob,PDO::PARAM_STR);
$query->bindParam(':stuid',$stuid,PDO::PARAM_STR);
$query->bindParam(':fname',$fname,PDO::PARAM_STR);
$query->bindParam(':mname',$mname,PDO::PARAM_STR);
$query->bindParam(':connum',$connum,PDO::PARAM_STR);
$query->bindParam(':altconnum',$altconnum,PDO::PARAM_STR);
$query->bindParam(':address',$address,PDO::PARAM_STR);
$query->bindParam(':image',$image,PDO::PARAM_STR);
$query->bindParam(':fatherAddhar',$faddhar,PDO::PARAM_STR);
$query->bindParam(':fatherImage',$fimage,PDO::PARAM_STR);
$query->bindParam(':motherAddhar',$maddhar,PDO::PARAM_STR);
$query->bindParam(':motherImage',$mimage,PDO::PARAM_STR);
$query->bindParam(':addDate',$addDate,PDO::PARAM_STR);
$query->bindParam(':joindate',$joindate,PDO::PARAM_STR);
$query->bindParam(':addAmt',$addAmt,PDO::PARAM_STR);
$query->bindParam(':dueAmt',$dueAmt,PDO::PARAM_STR);
$query->bindParam(':book',$book,PDO::PARAM_STR);
$query->bindParam(':bag',$bag,PDO::PARAM_STR);
$query->bindParam(':uniform',$uniform,PDO::PARAM_STR);
$query->bindParam(':idCard',$idCard,PDO::PARAM_STR);
$query->bindParam(':belt',$belt,PDO::PARAM_STR);
 $query->execute();
   $LastInsertId=$dbh->lastInsertId();
   if ($LastInsertId>0) {
    echo '<script>alert("Student has been added.")</script>';
echo "<script>window.location.href ='add-students.php'</script>";
  }
  else
    {
         echo '<script>alert("Something Went Wrong. Please try again")</script>';
    }
}}

else
{

echo "<script>alert('Username or Student Id  already exist. Please try again');</script>";
}
}
  ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">


    <title>Student Management System|| Add Students</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="vendors/select2/select2.min.css">
    <link rel="stylesheet" href="vendors/select2-bootstrap-theme/select2-bootstrap.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="css/style.css" />

</head>

<body>
    <div class="container-scroller">
        <!-- partial:partials/_navbar.html -->
        <?php include_once('includes/header.php');?>
        <!-- partial -->
        <div class="container-fluid page-body-wrapper">
            <!-- partial:partials/_sidebar.html -->
            <?php include_once('includes/sidebar.php');?>
            <!-- partial -->
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="page-header">
                        <h3 class="page-title"> Add Students </h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page"> Add Students</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="row">

                        <div class="col-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <!--<h3 class="card-title" style="text-align: center;">Add Students</h3>-->
                                    <hr />

                                    <form class="forms-sample" method="post" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-6">
                                                <h4>Student details</h4>
                                                <hr />
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Student Name</label>
                                                    <input type="text" name="stuname" value="" class="form-control"
                                                        >
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Student Aadhaar/Birth Certificate</label>
                                                    <input type="text" name="stuemail" value="" class="form-control"
                                                        >
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputEmail3">Student Class</label>
                                                    <select name="stuclass" class="form-control" id="classDropdown">
                                                        <option value="">Select Class</option>
                                                        <?php 

$sql2 = "SELECT DISTINCT ClassName from tblclass";
$query2 = $dbh -> prepare($sql2);
$query2->execute();
$result2=$query2->fetchAll(PDO::FETCH_OBJ);

foreach($result2 as $row1)
{          
    ?>
                                                        <option value="<?php echo htmlentities($row1->ClassName);?>">
                                                            <?php echo htmlentities($row1->ClassName);?>
                                                            </option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputEmail3">Student Section</label>
                                                    <select name="stusection" class="form-control" id="sectionDropdown">
                                                        <option value="">Select Section</option>
                                                            <!-- This will be dynamically populated using AJAX -->
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label for="exampleInputName1">Gender</label>
                                                    <select name="gender" value="" class="form-control" >
                                                        <option value="">Choose Gender</option>
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Date of Birth</label>
                                                    <input type="date" name="dob" value="" class="form-control"
                                                        >
                                                </div>

                                                <div class="form-group">
                                                    <label for="exampleInputName1">Student ID</label>
                                                    <input type="text" name="stuid" value="" class="form-control"
                                                        >
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Blood Group</label>
                                                    <input type="text" name="bldgr" value="" class="form-control"
                                                        >
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Student Photo</label>
                                                    <input type="file" name="image" value="" class="form-control"
                                                        >
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Address</label>
                                                    <textarea name="address" class="form-control"
                                                        ></textarea>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <h4>Parents/Guardian's details</h4>
                                                <hr />
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Father's Name</label>
                                                    <input type="text" name="fname" value="" class="form-control"
                                                        >
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Father's Addhar No</label>
                                                    <input type="text" name="faddhar" value="" class="form-control" maxlength="12" pattern="[0-9]{12}" title="Please enter a 12-digit Aadhaar number" required
                                                        >
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Father's Photo</label>
                                                    <input type="file" name="fimage" value="" class="form-control"
                                                        >
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Mother's Name</label>
                                                    <input type="text" name="mname" value="" class="form-control"
                                                        >
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Mother's Addhar No</label>
                                                    <input type="text" name="maddhar" value="" class="form-control" 
                                                        maxlength="12" pattern="[0-9]{12}" title="Please enter a 12-digit Aadhaar number" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Mother's Photo</label>
                                                    <input type="file" name="mimage" value="" class="form-control"
                                                        >
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Contact Number</label>
                                                    <input type="text" name="connum" value="" class="form-control"
                                                         maxlength="10" pattern="[0-9]{10}" 
       title="Please enter a valid 10-digit contact number" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Alternate Contact Number</label>
                                                    <input type="text" name="altconnum" value="" class="form-control"
                                                         maxlength="10" pattern="[0-9]{10}" 
       title="Please enter a valid 10-digit contact number">
                                                </div>

                                            </div>

                                            <div class="col-12 mt-3">
                                                <h4>School details</h4>
                                                <hr />
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="exampleInputName1">Admission Date</label>
                                                            <input type="date" name="adddate" value="" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="exampleInputName1">Joining Date</label>
                                                            <input type="date" name="joindate" value="" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="exampleInputName1">Admission Fee</label>
                                                            <input type="text" name="addamt" value="" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="exampleInputName1">Monthly Fee</label>
                                                            <input type="text" name="dueamt" value="" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="exampleInputName1">Books</label>
                                                            <select name="book" value="" class="form-control">
                                                                <option value="">Choose Option</option>
                                                                <option value="Yes">Yes</option>
                                                                <option value="No">No</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="exampleInputName1">Bag</label>
                                                            <select name="bag" value="" class="form-control">
                                                                <option value="">Choose Option</option>
                                                                <option value="Yes">Yes</option>
                                                                <option value="No">No</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="exampleInputName1">Uniform</label>
                                                            <select name="uniform" value="" class="form-control">
                                                                <option value="">Choose Option</option>
                                                                <option value="Yes">Yes</option>
                                                                <option value="No">No</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="exampleInputName1">ID card</label>
                                                            <select name="idcard" value="" class="form-control">
                                                                <option value="">Choose Option</option>
                                                                <option value="Yes">Yes</option>
                                                                <option value="No">No</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="exampleInputName1">Stocks, Belt</label>
                                                            <select name="stock_belt" value="" class="form-control">
                                                                <option value="">Choose Option</option>
                                                                <option value="Yes">Yes</option>
                                                                <option value="No">No</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary mr-2" name="submit">Add</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- content-wrapper ends -->
                <!-- partial:partials/_footer.html -->
                <?php include_once('includes/footer.php');?>
                <!-- partial -->
            </div>
            <!-- main-panel ends -->
        </div>
        <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <script src="vendors/select2/select2.min.js"></script>
    <script src="vendors/typeahead.js/typeahead.bundle.min.js"></script>
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="js/off-canvas.js"></script>
    <script src="js/misc.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page -->
    <script src="js/typeahead.js"></script>
    <script src="js/select2.js"></script>
    <script>
$(document).ready(function() {
    $('#classDropdown').change(function() {
        var className = $(this).val();
        $('#sectionDropdown').html('<option value="">Select Section</option>'); // Reset

        if (className !== "") {
            $.ajax({
                url: 'get_sections.php',
                type: 'POST',
                data: { class_name: className },
                dataType: 'json',
                success: function(response) {
                    if(response.length > 0) {
                        $.each(response, function(index, section) {
                            $('#sectionDropdown').append('<option value="'+section.Section+'">'+section.Section+'</option>');
                        });
                    } else {
                        $('#sectionDropdown').append('<option value="">No Section Found</option>');
                    }
                }
            });
        }
    });
});
</script>
    <!-- End custom js for this page -->
</body>

</html><?php }  ?>
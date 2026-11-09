<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (strlen($_SESSION['sturecmsaid']==0)) {
  header('location:logout.php');
} else {
    if(isset($_POST['submit']))
    {
        $stuname=$_POST['stuname'];
        $stuemail=$_POST['stuemail'];
        $stuclass=$_POST['stuclass'];
        $stusection=$_POST['stusection'];
        $gender=$_POST['gender'];
        $dob=$_POST['dob'];
        $stuid=$_POST['stuid'];
        $fname=$_POST['fname'];
        $mname=$_POST['mname'];
        $connum=$_POST['connum'];
        $altconnum=$_POST['altconnum'];
        $address=$_POST['address'];
        $faddhar=$_POST['faddhar'];
        $maddhar=$_POST['maddhar'];
        $addDate=$_POST['adddate'];
        $joindate=$_POST['joindate'];
        $addAmt=$_POST['addamt'];
        $dueAmt=$_POST['dueamt'];
        $book=$_POST['book'];
        $bag=$_POST['bag'];
        $uniform=$_POST['uniform'];
        $idCard=$_POST['idcard'];
        $belt=$_POST['stock_belt'];
        $eid=$_GET['editid'];
        
        // Handle image uploads
        $studentImage = $_POST['existing_student_image'];
        $fatherImage = $_POST['existing_father_image'];
        $motherImage = $_POST['existing_mother_image'];
        
        // Student Image Upload
        if(!empty($_FILES['student_image']['name'])) {
            $studentImage = time() . '_' . $_FILES['student_image']['name'];
            $target_dir = "images/";
            $target_file = $target_dir . basename($studentImage);
            
            // Check if image file is a actual image or fake image
            $check = getimagesize($_FILES['student_image']['tmp_name']);
            if($check !== false) {
                if(move_uploaded_file($_FILES['student_image']['tmp_name'], $target_file)) {
                    // Delete old image if it exists and is not the default
                    if(!empty($_POST['existing_student_image']) && $_POST['existing_student_image'] != 'default.png') {
                        @unlink("images/" . $_POST['existing_student_image']);
                    }
                }
            }
        }
        
        // Father Image Upload
        if(!empty($_FILES['father_image']['name'])) {
            $fatherImage = 'father_' . time() . '_' . $_FILES['father_image']['name'];
            $target_dir = "images/";
            $target_file = $target_dir . basename($fatherImage);
            
            $check = getimagesize($_FILES['father_image']['tmp_name']);
            if($check !== false) {
                if(move_uploaded_file($_FILES['father_image']['tmp_name'], $target_file)) {
                    if(!empty($_POST['existing_father_image']) && $_POST['existing_father_image'] != 'default.png') {
                        @unlink("images/" . $_POST['existing_father_image']);
                    }
                }
            }
        }
        
        // Mother Image Upload
        if(!empty($_FILES['mother_image']['name'])) {
            $motherImage = 'mother_' . time() . '_' . $_FILES['mother_image']['name'];
            $target_dir = "images/";
            $target_file = $target_dir . basename($motherImage);
            
            $check = getimagesize($_FILES['mother_image']['tmp_name']);
            if($check !== false) {
                if(move_uploaded_file($_FILES['mother_image']['tmp_name'], $target_file)) {
                    if(!empty($_POST['existing_mother_image']) && $_POST['existing_mother_image'] != 'default.png') {
                        @unlink("images/" . $_POST['existing_mother_image']);
                    }
                }
            }
        }

        $sql="update tblstudent set StudentName=:stuname,StudentEmail=:stuemail,StudentClass=:stuclass,StudentSection=:stusection,Gender=:gender,DOB=:dob,StuID=:stuid,FatherName=:fname,MotherName=:mname,ContactNumber=:connum,AltenateNumber=:altconnum,Address=:address,fatherAddhar=:fatherAddhar,motherAddhar=:motherAddhar,addDate=:addDate,joindate=:joindate,addAmt=:addAmt,dueAmt=:dueAmt,book=:book,bag=:bag,uniform=:uniform,idCard=:idCard,belt=:belt,Image=:studentImage,fatherImage=:fatherImage,motherImage=:motherImage where ID=:eid";

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
        $query->bindParam(':fatherAddhar',$faddhar,PDO::PARAM_STR);
        $query->bindParam(':motherAddhar',$maddhar,PDO::PARAM_STR);
        $query->bindParam(':addDate',$addDate,PDO::PARAM_STR);
        $query->bindParam(':joindate',$joindate,PDO::PARAM_STR);
        $query->bindParam(':addAmt',$addAmt,PDO::PARAM_STR);
        $query->bindParam(':dueAmt',$dueAmt,PDO::PARAM_STR);
        $query->bindParam(':book',$book,PDO::PARAM_STR);
        $query->bindParam(':bag',$bag,PDO::PARAM_STR);
        $query->bindParam(':uniform',$uniform,PDO::PARAM_STR);
        $query->bindParam(':idCard',$idCard,PDO::PARAM_STR);
        $query->bindParam(':belt',$belt,PDO::PARAM_STR);
        $query->bindParam(':studentImage',$studentImage,PDO::PARAM_STR);
        $query->bindParam(':fatherImage',$fatherImage,PDO::PARAM_STR);
        $query->bindParam(':motherImage',$motherImage,PDO::PARAM_STR);
        $query->bindParam(':eid',$eid,PDO::PARAM_STR);
        
        if($query->execute()) {
            echo '<script>alert("Student has been updated")</script>';
        } else {
            echo '<script>alert("Something went wrong. Please try again")</script>';
        }
    }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Student Management System|| Update Students</title>
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
    <style>
        .image-preview {
            max-width: 150px;
            max-height: 150px;
            margin-top: 10px;
            display: none;
        }
        .image-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
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
                        <h3 class="page-title"> Update Students </h3>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page"> Update Students Details</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="row">
                        <div class="col-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <!--<h4 class="card-title" style="text-align: center;">Update Students Details</h4>-->
                                    <!--<hr />-->
                                    <form class="forms-sample" method="post" enctype="multipart/form-data">
                                        <?php
                                        $eid=$_GET['editid'];
                                        $sql="SELECT tblstudent.StudentName,tblstudent.id as sid,tblstudent.StudentEmail,tblstudent.StudentClass,tblstudent.Gender,tblstudent.DOB,tblstudent.StuID,tblstudent.FatherName,tblstudent.MotherName,tblstudent.ContactNumber,tblstudent.AltenateNumber,tblstudent.Address,tblstudent.Image,tblstudent.DateofAdmission,tblstudent.fatherAddhar,tblstudent.fatherImage,tblstudent.motherAddhar,tblstudent.motherImage,tblstudent.addDate,tblstudent.joindate,tblstudent.addAmt,tblstudent.dueAmt,tblstudent.book,tblstudent.bag,tblstudent.uniform,tblstudent.idCard,tblstudent.belt,tblstudent.StudentClass,tblstudent.StudentSection from tblstudent where tblstudent.ID=:eid";
                                        $query = $dbh -> prepare($sql);
                                        $query->bindParam(':eid',$eid,PDO::PARAM_STR);
                                        $query->execute();
                                        $results=$query->fetchAll(PDO::FETCH_OBJ);
                                        $cnt=1;
                                        if($query->rowCount() > 0) {
                                            foreach($results as $row) { 
                                        ?>
                                        <input type="hidden" name="existing_student_image" value="<?php echo htmlentities($row->Image); ?>">
                                        <input type="hidden" name="existing_father_image" value="<?php echo htmlentities($row->fatherImage); ?>">
                                        <input type="hidden" name="existing_mother_image" value="<?php echo htmlentities($row->motherImage); ?>">
                                        
                                        <div class="row">
                                            <div class="col-6">
                                                <h4>Student details</h4>
                                                <hr />
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Student Name</label>
                                                    <input type="text" name="stuname"
                                                        value="<?php  echo htmlentities($row->StudentName);?>"
                                                        class="form-control" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Student Addhar/Birth Certificate</label>
                                                    <input type="text" name="stuemail"
                                                        value="<?php  echo htmlentities($row->StudentEmail);?>"
                                                        class="form-control" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputEmail3">Student Class</label>
                                                    <select name="stuclass" class="form-control" id="classDropdown" required>
                                                        <option value="<?php  echo htmlentities($row->StudentClass);?>">
                                                            <?php  echo htmlentities($row->StudentClass);?>
                                                        </option>
                                                        <?php 
                                                        $sql2 = "SELECT DISTINCT ClassName from tblclass";
                                                        $query2 = $dbh -> prepare($sql2);
                                                        $query2->execute();
                                                        $result2=$query2->fetchAll(PDO::FETCH_OBJ);
                                                        foreach($result2 as $row1) { 
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
                                                        <option value="<?php echo htmlentities($row->StudentSection);?>"><?php echo htmlentities($row->StudentSection);?></option>
                                                        <!-- This will be dynamically populated using AJAX -->
                                                            </option>                                                        
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Gender</label>
                                                    <select name="gender" class="form-control" required>
                                                        <option value="<?php  echo htmlentities($row->Gender);?>">
                                                            <?php  echo htmlentities($row->Gender);?></option>
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Date of Birth</label>
                                                    <input type="date" name="dob"
                                                        value="<?php  echo htmlentities($row->DOB);?>"
                                                        class="form-control" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Student ID</label>
                                                    <input type="text" name="stuid"
                                                        value="<?php  echo htmlentities($row->StuID);?>"
                                                        class="form-control" readonly='true'>
                                                </div>
                                                
                                                <!-- Student Image Section -->
                                                <div class="image-section">
                                                    <div class="form-group">
                                                        <label for="exampleInputName1">Student Photo</label><br>
                                                        <img src="images/<?php echo $row->Image;?>" width="100" height="100" 
                                                             style="border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Change Student Photo</label>
                                                        <input type="file" name="student_image" class="form-control" 
                                                               accept="image/*" onchange="previewImage(this, 'studentPreview')">
                                                        <img id="studentPreview" class="image-preview" src="#" alt="Student Image Preview">
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Address</label>
                                                    <textarea name="address" class="form-control" required><?php  echo htmlentities($row->Address);?></textarea>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <h4>Parents/Guardian's details</h4>
                                                <hr />
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Father's Name</label>
                                                    <input type="text" name="fname"
                                                        value="<?php  echo htmlentities($row->FatherName);?>"
                                                        class="form-control" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Father's Addhar No</label>
                                                    <input type="text" name="faddhar"
                                                        value="<?php  echo htmlentities($row->fatherAddhar);?>"
                                                        class="form-control" pattern="[0-9]{12}" title="Please enter a 12-digit Aadhaar number" required>
                                                </div>
                                                
                                                <!-- Father Image Section -->
                                                <div class="image-section">
                                                    <div class="form-group">
                                                        <label for="exampleInputName1">Father's Photo</label><br>
                                                        <img src="images/<?php echo $row->fatherImage;?>" width="100" height="100"
                                                             style="border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Change Father's Photo</label>
                                                        <input type="file" name="father_image" class="form-control" 
                                                               accept="image/*" onchange="previewImage(this, 'fatherPreview')">
                                                        <img id="fatherPreview" class="image-preview" src="#" alt="Father Image Preview">
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Mother's Name</label>
                                                    <input type="text" name="mname"
                                                        value="<?php  echo htmlentities($row->MotherName);?>"
                                                        class="form-control" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Mother's Addhar No</label>
                                                    <input type="text" name="maddhar"
                                                        value="<?php  echo htmlentities($row->motherAddhar);?>"
                                                        class="form-control" pattern="[0-9]{12}" title="Please enter a 12-digit Aadhaar number" required>
                                                </div>
                                                
                                                <!-- Mother Image Section -->
                                                <div class="image-section">
                                                    <div class="form-group">
                                                        <label for="exampleInputName1">Mother's Photo</label><br>
                                                        <img src="images/<?php echo $row->motherImage;?>" width="100" height="100"
                                                             style="border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Change Mother's Photo</label>
                                                        <input type="file" name="mother_image" class="form-control" 
                                                               accept="image/*" onchange="previewImage(this, 'motherPreview')">
                                                        <img id="motherPreview" class="image-preview" src="#" alt="Mother Image Preview">
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Contact Number</label>
                                                    <input type="text" name="connum"
                                                        value="<?php  echo htmlentities($row->ContactNumber);?>"
                                                        class="form-control" maxlength="10" pattern="[0-9]{10}" 
                                                        title="Please enter a valid 10-digit contact number" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="exampleInputName1">Alternate Contact Number</label>
                                                    <input type="text" name="altconnum"
                                                        value="<?php  echo htmlentities($row->AltenateNumber);?>"
                                                        class="form-control" maxlength="10" pattern="[0-9]{10}" 
                                                        title="Please enter a valid 10-digit contact number">
                                                </div>
                                            </div>
                                            
                                            <!-- Rest of your form remains the same -->
                                            <div class="col-12 mt-3">
                                                <h4>College details</h4>
                                                <hr />
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="exampleInputName1">Admission Date</label>
                                                            <input type="date" name="adddate" value="<?php echo htmlentities($row->addDate);?>" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="exampleInputName1">Joining Date</label>
                                                            <input type="date" name="joindate" value="<?php echo htmlentities($row->joindate);?>" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="exampleInputName1">Admission Fee</label>
                                                            <input type="text" name="addamt" value="<?php echo htmlentities($row->addAmt);?>" class="form-control">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="exampleInputName1">Monthly Fee</label>
                                                            <input type="text" name="dueamt" value="<?php echo htmlentities($row->dueAmt);?>" class="form-control">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="exampleInputName1">Books</label>
                                                            <select name="book" class="form-control">
                                                                <option value="<?php echo htmlentities($row->book);?>"><?php echo htmlentities($row->book);?></option>
                                                                <option value="Yes">Yes</option>
                                                                <option value="No">No</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="exampleInputName1">Bag</label>
                                                            <select name="bag" class="form-control">
                                                                <option value="<?php echo htmlentities($row->bag);?>"><?php echo htmlentities($row->bag);?></option>
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
                                                            <select name="uniform" class="form-control">
                                                                <option value="<?php echo htmlentities($row->uniform);?>"><?php echo htmlentities($row->uniform);?></option>
                                                                <option value="Yes">Yes</option>
                                                                <option value="No">No</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="exampleInputName1">ID card</label>
                                                            <select name="idcard" class="form-control">
                                                                <option value="<?php echo htmlentities($row->idCard);?>"><?php echo htmlentities($row->idCard);?></option>
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
                                                            <select name="stock_belt" class="form-control">
                                                                <option value="<?php echo htmlentities($row->belt);?>"><?php echo htmlentities($row->belt);?></option>
                                                                <option value="Yes">Yes</option>
                                                                <option value="No">No</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php $cnt=$cnt+1;}} ?>
                                        <button type="submit" class="btn btn-primary mr-2" name="submit">Update</button>
                                        <button type="button" class="btn btn-light" onclick="window.history.back();">Cancel</button>
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
    <!-- End custom js for this page -->
    
    <script>
    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        const file = input.files[0];
        
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
            preview.src = '#';
        }
    }
    </script>
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
    <!-- End custom js for this page -->
    </script>
</body>
</html>
<?php } ?>
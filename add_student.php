<?php
require_once 'connect.php';

$error = '';

// 1. ดึงข้อมูลจังหวัดและงานอดิเรก (ไม่มีการเปลี่ยนแปลง)
$provinces = $conn->query("SELECT * FROM province ORDER BY province_name_th ASC");
$hobby_data = $conn->query("SELECT * FROM hobby ORDER BY hobby_id ASC");

// 2. ตรวจสอบการส่งข้อมูลแบบ POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับค่าจากฟอร์ม (ไม่มีการเปลี่ยนแปลง)
    $prefix = $_POST['prefix'];
    $fname_th = $_POST['first_name_th'];
    $lname_th = $_POST['last_name_th'];
    $fname_en = $_POST['first_name_en'];
    $lname_en = $_POST['last_name_en'];
    $province_id = $_POST['province_id'];
    $hobby = isset($_POST['hobby']) ? $_POST['hobby'] : []; 

    // ************************************************
    // ** แก้ไข: การหา ID ถัดไปสำหรับตาราง Student **
    // ************************************************
    $next_student_id_result = $conn->query("SELECT MAX(student_id) AS max_id FROM student");
    $max_student_id = $next_student_id_result->fetch_assoc()['max_id'];
    $next_student_id = ($max_student_id === NULL) ? 1 : $max_student_id + 1;


    $conn->begin_transaction();
    try {
        // 3.1. Insert into student table (มีการส่ง next_student_id เข้าไปด้วย)
        $stmt = $conn->prepare("INSERT INTO student (student_id, prefix, first_name_th, last_name_th, first_name_en, last_name_en, province_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssi", $next_student_id, $prefix, $fname_th, $lname_th, $fname_en, $lname_en, $province_id);
        $stmt->execute();
        $stmt->close();

        // 3.2. Insert into student_hobby table
        if (!empty($hobby)) {
            $sql_hobby = "INSERT INTO student_hobby (student_hobby_id, student_id, hobby_id) VALUES (?, ?, ?)";
            $stmt_hobby = $conn->prepare($sql_hobby);
            
            // เตรียมการหา ID สำหรับ student_hobby
            $max_sh_id_result = $conn->query("SELECT MAX(student_hobby_id) AS max_sh_id FROM student_hobby");
            $current_max_sh_id = $max_sh_id_result->fetch_assoc()['max_sh_id'];
            $next_sh_id = ($current_max_sh_id === NULL) ? 1 : $current_max_sh_id + 1;
            
            foreach ($hobby as $hobby_id) {
                $stmt_hobby->bind_param("iii", $next_sh_id, $next_student_id, $hobby_id);
                $stmt_hobby->execute();
                $next_sh_id++; // เพิ่ม ID สำหรับแถวถัดไป
            }
            $stmt_hobby->close();
        }

        $conn->commit();
        header("Location: index.php"); 
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มรายชื่อนักศึกษา</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>เพิ่มรายชื่อนักศึกษา (Add New Student)</h2>
        <?php if ($error) echo '<p class="error">' . $error . '</p>'; ?>
        <form action="add_student.php" method="post" class="student-form">
            <fieldset>
                <legend>ข้อมูลส่วนตัว (Personal Information)</legend>
                <div class="form-group">
                    <label for="prefix">คำนำหน้า:</label>
                    <select name="prefix" id="prefix" required>
                        <option value="นาย">นาย</option>
                        <option value="นาง">นาง</option>
                        <option value="นางสาว">นางสาว</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="first_name_th">ชื่อ (ไทย):</label>
                    <input type="text" id="first_name_th" name="first_name_th" required>
                </div>
                <div class="form-group">
                    <label for="last_name_th">นามสกุล (ไทย):</label>
                    <input type="text" id="last_name_th" name="last_name_th" required>
                </div>
                <div class="form-group">
                    <label for="first_name_en">ชื่อ (อังกฤษ):</label>
                    <input type="text" id="first_name_en" name="first_name_en" required>
                </div>
                <div class="form-group">
                    <label for="last_name_en">นามสกุล (อังกฤษ):</label>
                    <input type="text" id="last_name_en" name="last_name_en" required>
                </div>
                <div class="form-group">
                    <label for="province_id">จังหวัด:</label>
                    <select name="province_id" id="province_id" required>
                        <option value="">เลือกจังหวัด</option>
                        <?php while($prov = $provinces->fetch_assoc()): ?>
                            <option value="<?= $prov['province_id'] ?>"><?= $prov['province_name_th'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </fieldset>

            <fieldset>
                <legend>งานอดิเรกและความสนใจ (Hobbies & Interests)</legend>
                <div class="checkbox-group">
                    <?php while($hobby = $hobby_data->fetch_assoc()): ?>
                        <label>
                            <input type="checkbox" name="hobby[]" value="<?= $hobby['hobby_id'] ?>">
                            <?= $hobby['hobby_name_th'] ?>
                        </label>
                    <?php endwhile; ?>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn-primary">เพิ่มข้อมูล</button>
                <a href="index.php" class="btn-cancel">ยกเลิก</a>
            </div>
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>
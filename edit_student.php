<?php
require_once 'connect.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$student_id = $_GET['id'];
$error = '';

// 1. ดึงข้อมูลนักศึกษาเดิม, จังหวัด, และงานอดิเรกที่ถูกเลือก

// 1.1 ดึงข้อมูลหลักนักศึกษา
$sql_student = "SELECT student_id, prefix, first_name_th, last_name_th, 
                       first_name_en, last_name_en, province_id 
                FROM student 
                WHERE student_id = ?";
$stmt_student = $conn->prepare($sql_student);

if (!$stmt_student) {
    die("Prepare failed for student: (" . $conn->errno . ") " . $conn->error);
}

$stmt_student->bind_param("i", $student_id);
$stmt_student->execute();

// ***** ส่วนที่แก้ไข: เปลี่ยนมาใช้ bind_result() แทน get_result() *****
$stmt_student->bind_result(
    $s_id, $s_prefix, $s_fname_th, $s_lname_th, 
    $s_fname_en, $s_lname_en, $s_province_id
);

if ($stmt_student->fetch()) {
    // สร้าง Array $student เพื่อให้โค้ด HTML ใช้ได้ตามเดิม
    $student = [
        'student_id' => $s_id,
        'prefix' => $s_prefix,
        'first_name_th' => $s_fname_th,
        'last_name_th' => $s_lname_th,
        'first_name_en' => $s_fname_en,
        'last_name_en' => $s_lname_en,
        'province_id' => $s_province_id
    ];
} else {
    $student = false;
}
$stmt_student->close();

if (!$student) {
    echo "ไม่พบข้อมูลนักศึกษาที่ต้องการแก้ไข";
    $conn->close();
    exit();
}
// *******************************************************************

// 1.2 ดึงงานอดิเรกที่ถูกเลือก (ส่วนที่ต้องแก้ไข)
$sql_selected_hobby = "SELECT hobby_id FROM student_hobby WHERE student_id = ?";
$stmt_sh = $conn->prepare($sql_selected_hobby);

// ตรวจสอบ Prepare Error
if (!$stmt_sh) {
    die("Prepare failed for selected hobby: (" . $conn->errno . ") " . $conn->error);
}

$stmt_sh->bind_param("i", $student_id);
$stmt_sh->execute();

// ***** ส่วนที่แก้ไข: เปลี่ยนมาใช้ bind_result() แทน get_result() *****
// ผูกตัวแปรเข้ากับคอลัมน์ผลลัพธ์
$stmt_sh->bind_result($h_id); 
$selected_hobby = [];

// วนลูปเพื่อดึงผลลัพธ์ทีละแถว
while ($stmt_sh->fetch()) {
    $selected_hobby[] = $h_id;
}
// ******************************************************************

$stmt_sh->close();

// 2. ดึงข้อมูลจังหวัดและงานอดิเรกทั้งหมด (ส่วนนี้ใช้ query() ธรรมดา ไม่ต้องแก้ไข)
$province = $conn->query("SELECT * FROM province ORDER BY province_name_th ASC");
$hobby_data = $conn->query("SELECT * FROM hobby ORDER BY hobby_id ASC");


// 3. ตรวจสอบการส่งข้อมูลแบบ POST (การแก้ไข)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prefix = $_POST['prefix'];
    $fname_th = $_POST['first_name_th'];
    $lname_th = $_POST['last_name_th'];
    $fname_en = $_POST['first_name_en'];
    $lname_en = $_POST['last_name_en'];
    $province_id = $_POST['province_id'];
    $new_hobby = isset($_POST['hobby']) ? $_POST['hobby'] : [];
    
    $conn->begin_transaction();
    try {
        // 3.1. Update students table (ไม่มีการเปลี่ยนแปลง)
        $stmt_update = $conn->prepare("UPDATE student SET prefix=?, first_name_th=?, last_name_th=?, first_name_en=?, last_name_en=?, province_id=? WHERE student_id=?");
        $stmt_update->bind_param("sssssii", $prefix, $fname_th, $lname_th, $fname_en, $lname_en, $province_id, $student_id);
        $stmt_update->execute();
        $stmt_update->close();

        // 3.2. Update student_hobby table: ลบของเก่าทั้งหมด
        $conn->query("DELETE FROM student_hobby WHERE student_id = $student_id");
        
        // ***** ส่วนที่แก้ไข: การหา ID ถัดไปสำหรับตาราง student_hobby (Non-AUTO_INCREMENT) *****
        if (!empty($new_hobby)) {
            $sql_hobby = "INSERT INTO student_hobby (student_hobby_id, student_id, hobby_id) VALUES (?, ?, ?)";
            $stmt_hobby = $conn->prepare($sql_hobby);

            // หา ID สูงสุดในตาราง student_hobby เพื่อกำหนดค่าเริ่มต้น
            $max_sh_id_result = $conn->query("SELECT MAX(student_hobby_id) AS max_sh_id FROM student_hobby");
            $current_max_sh_id = $max_sh_id_result->fetch_assoc()['max_sh_id'];
            $next_sh_id = ($current_max_sh_id === NULL) ? 1 : $current_max_sh_id + 1;
            
            foreach ($new_hobby as $hobby_id) {
                $stmt_hobby->bind_param("iii", $next_sh_id, $student_id, $hobby_id);
                $stmt_hobby->execute();
                $next_sh_id++; // เพิ่ม ID สำหรับแถวถัดไป
            }
            $stmt_hobby->close();
        }

        $conn->commit();
        header("Location: view_student.php?id=$student_id"); 
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
    <title>แก้ไขข้อมูล: <?= $student['first_name_th'] ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>แก้ไขข้อมูลนักศึกษา (Edit Student: <?= $student_id ?>)</h2>
        <?php if ($error) echo '<p class="error">' . $error . '</p>'; ?>
        
        <form action="edit_student.php?id=<?= $student_id ?>" method="post" class="student-form">
            <fieldset>
                <legend>ข้อมูลส่วนตัว</legend>
                <div class="form-group">
                    <label for="prefix">คำนำหน้า:</label>
                    <select name="prefix" id="prefix" required>
                        <option value="นาย" <?= ($student['prefix'] == 'นาย') ? 'selected' : '' ?>>นาย</option>
                        <option value="นาง" <?= ($student['prefix'] == 'นาง') ? 'selected' : '' ?>>นาง</option>
                        <option value="นางสาว" <?= ($student['prefix'] == 'นางสาว') ? 'selected' : '' ?>>นางสาว</option>
                    </select>
                </div>
                <div class="form-group"><label>ชื่อ (ไทย):</label><input type="text" name="first_name_th" value="<?= $student['first_name_th'] ?>" required></div>
                <div class="form-group"><label>นามสกุล (ไทย):</label><input type="text" name="last_name_th" value="<?= $student['last_name_th'] ?>" required></div>
                <div class="form-group"><label>ชื่อ (อังกฤษ):</label><input type="text" name="first_name_en" value="<?= $student['first_name_en'] ?>" required></div>
                <div class="form-group"><label>นามสกุล (อังกฤษ):</label><input type="text" name="last_name_en" value="<?= $student['last_name_en'] ?>" required></div>

                <div class="form-group">
                    <label for="province_id">จังหวัด:</label>
                    <select name="province_id" id="province_id" required>
                        <option value="">เลือกจังหวัด</option>
                        <?php 
                        // ต้องรีเซ็ตพอยเตอร์ของ $province ก่อน เพราะอาจถูกอ่านไปแล้วในการเช็ค prepare
                        $province->data_seek(0); 
                        while($prov = $province->fetch_assoc()): 
                        ?>
                            <option value="<?= $prov['province_id'] ?>" <?= ($student['province_id'] == $prov['province_id']) ? 'selected' : '' ?>>
                                <?= $prov['province_name_th'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </fieldset>

            <fieldset>
                <legend>งานอดิเรกและความสนใจ</legend>
                <div class="checkbox-group">
                    <?php 
                    // ต้องรีเซ็ตพอยเตอร์ของ $hobby_data ก่อน
                    $hobby_data->data_seek(0);
                    while($hobby = $hobby_data->fetch_assoc()): 
                    ?>
                        <label>
                            <input type="checkbox" name="hobby[]" value="<?= $hobby['hobby_id'] ?>" 
                                <?= in_array($hobby['hobby_id'], $selected_hobby) ? 'checked' : '' ?>>
                            <?= $hobby['hobby_name_th'] ?>
                        </label>
                    <?php endwhile; ?>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn-primary">บันทึกการแก้ไข</button>
                <a href="view_student.php?id=<?= $student_id ?>" class="btn-cancel">ยกเลิก</a>
            </div>
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>
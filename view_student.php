<?php
require_once 'connect.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$student_id = $_GET['id'];

// 1. ดึงข้อมูลหลักนักศึกษา + จังหวัด (แก้ไขชื่อตารางตามที่เคยแนะนำไปก่อนหน้า)
$sql_student = "SELECT s.student_id, s.prefix, s.first_name_th, s.last_name_th, 
                       s.first_name_en, s.last_name_en, p.province_name_th, p.province_name_en 
                FROM student s 
                INNER JOIN province p ON s.province_id = p.province_id 
                WHERE s.student_id = ?";
                
$stmt_student = $conn->prepare($sql_student);
// ตรวจสอบ Prepare Error
if (!$stmt_student) {
    die("Prepare failed for student: (" . $conn->errno . ") " . $conn->error);
}

$stmt_student->bind_param("i", $student_id);
$stmt_student->execute();

// ***** ส่วนที่แก้ไข: เปลี่ยนมาใช้ bind_result() แทน get_result() *****
// ต้องระบุตัวแปรที่ผูกกับทุกคอลัมน์ที่เลือกใน SELECT
$stmt_student->bind_result(
    $s_id, $s_prefix, $s_fname_th, $s_lname_th, 
    $s_fname_en, $s_lname_en, $p_name_th, $p_name_en
);

// ดึงผลลัพธ์ (มีเพียงแถวเดียว)
if ($stmt_student->fetch()) {
    // สร้าง Array $student ขึ้นมาใหม่ เพื่อให้ใช้กับ HTML เดิมได้
    $student = [
        'student_id' => $s_id,
        'prefix' => $s_prefix,
        'first_name_th' => $s_fname_th,
        'last_name_th' => $s_lname_th,
        'first_name_en' => $s_fname_en,
        'last_name_en' => $s_lname_en,
        'province_name_th' => $p_name_th,
        'province_name_en' => $p_name_en
    ];
} else {
    $student = false;
}
$stmt_student->close();

if (!$student) {
    echo "ไม่พบข้อมูลนักศึกษา";
    $conn->close();
    exit();
}

// 2. ดึงข้อมูลงานอดิเรก
$sql_hobby = "SELECT h.hobby_name_th, h.hobby_name_en 
              FROM student_hobby sh 
              INNER JOIN hobby h ON sh.hobby_id = h.hobby_id /* **แก้ไข: hobby -> hobbies** */
              WHERE sh.student_id = ?";

$stmt_hobby = $conn->prepare($sql_hobby);
// ตรวจสอบ Prepare Error (แนะนำให้เพิ่มส่วนนี้)
if (!$stmt_hobby) {
    die("Prepare failed for hobby: (" . $conn->errno . ") " . $conn->error);
}

$stmt_hobby->bind_param("i", $student_id);
$stmt_hobby->execute();

// ***** ส่วนที่แก้ไข: ใช้ bind_result() แทน get_result() *****
// ผูกตัวแปรเข้ากับคอลัมน์ผลลัพธ์
$stmt_hobby->bind_result($h_name_th, $h_name_en); 

$hobby_th = [];
$hobby_en = [];

// วนลูปเพื่อดึงผลลัพธ์ทีละแถว
while ($stmt_hobby->fetch()) {
    $hobby_th[] = $h_name_th;
    $hobby_en[] = $h_name_en;
}
$stmt_hobby->close();
// *********************************************************
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายละเอียด: <?= $student['first_name_th'] . ' ' . $student['last_name_th'] ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>หน้ารายละเอียดรายบุคคล</h2>
        <div class="detail-card">
            <h3><?= $student['first_name_th'] . ' ' . $student['last_name_th'] ?></h3>
            <p>รหัสนักศึกษา: <?= $student['student_id'] ?></p>
            <div class="detail-group">
                <h4>Personal Information</h4>
                <p>ชื่อ - นามสกุล (ไทย): <?= $student['prefix'] . $student['first_name_th'] . ' ' . $student['last_name_th'] ?></p>
                <p>ชื่อ - นามสกุล (อังกฤษ): <?= $student['prefix'] . $student['first_name_en'] . ' ' . $student['last_name_en'] ?></p>
                <p>จังหวัด (ไทย): <?= $student['province_name_th'] ?></p>
                <p>จังหวัด (อังกฤษ): <?= $student['province_name_en'] ?></p>
            </div>
            <div class="detail-group">
                <h4>Additional Details</h4>
                <p>งานอดิเรก (ไทย): <?= empty($hobby_th) ? '-' : implode(', ', $hobby_th) ?></p>
                <p>งานอดิเรก (อังกฤษ): <?= empty($hobby_en) ? '-' : implode(', ', $hobby_en) ?></p>
            </div>
            <div class="actions">
                 <a href="edit_student.php?id=<?= $student['student_id'] ?>" class="btn-edit">แก้ไขข้อมูล</a>
                 <a href="index.php" class="btn-back">กลับหน้าหลัก</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
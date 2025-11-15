<?php
require_once 'connect.php';

// ดึงข้อมูลนักศึกษาทั้งหมด โดย JOIN กับตารางจังหวัด
$sql = "SELECT s.student_id, s.first_name_th, s.last_name_th, p.province_name_th 
        FROM student s 
        INNER JOIN province p ON s.province_id = p.province_id 
        ORDER BY s.student_id ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบจัดการนักศึกษา - หน้าหลัก</title>
    <link rel="stylesheet" href="style.css"> </head>
<body>
    <div class="container">
        <div class="header">
            <h2>ระบบบริหารจัดการนักศึกษา (Student Management System)</h2>
            <a href="add_student.php" class="btn-add">+ เพิ่มรายชื่อนักศึกษา</a>
        </div>
        <hr>
        <div class="student-list">
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    // ดึงงานอดิเรกมาแสดง (จะซับซ้อนกว่า แต่เพื่อความง่ายใน Index จะยังไม่แสดง)
                    // โค้ดที่ถูกต้องควรมีการ query ตาราง student_hobby เพิ่มเติม
                    
                    echo '<div class="student-card">';
                    echo '<h4>' . $row["first_name_th"] . ' ' . $row["last_name_th"] . '</h4>';
                    echo '<p>รหัส: ' . $row["student_id"] . '</p>';
                    echo '<p>จังหวัด: ' . $row["province_name_th"] . '</p>';
                    echo '<div class="actions">';
                    echo '<a href="view_student.php?id=' . $row["student_id"] . '" class="btn-detail">ดูรายละเอียด</a>';
                    echo '<a href="edit_student.php?id=' . $row["student_id"] . '" class="btn-edit">แก้ไข</a>';
                    // ใช้ JavaScript ในการยืนยันการลบ
                    echo '<a href="delete_student.php?id=' . $row["student_id"] . '" class="btn-delete" onclick="return confirm(\'คุณต้องการยืนยันการลบนักศึกษา ' . $row["first_name_th"] . ' ' . $row["last_name_th"] . ' ใช่หรือไม่?\')">ลบ</a>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p>ไม่พบรายชื่อนักศึกษา</p>';
            }
            $conn->close();
            ?>
        </div>
    </div>
</body>
</html>
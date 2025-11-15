<?php
require_once 'connect.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$student_id = $_GET['id'];

$conn->begin_transaction();
try {
    // 1. ลบงานอดิเรกที่เชื่อมโยงก่อน (Foreign Key constraint)
    $stmt_hobby = $conn->prepare("DELETE FROM student_hobby WHERE student_id = ?");
    $stmt_hobby->bind_param("i", $student_id);
    $stmt_hobby->execute();
    $stmt_hobby->close();

    // 2. ลบข้อมูลนักศึกษา
    $stmt_student = $conn->prepare("DELETE FROM student WHERE student_id = ?");
    $stmt_student->bind_param("i", $student_id);
    $stmt_student->execute();
    $stmt_student->close();

    $conn->commit();
    // ส่งกลับไปหน้าหลัก
    header("Location: index.php"); 
    exit();
    
} catch (Exception $e) {
    $conn->rollback();
    // ถ้ามีข้อผิดพลาด ควรแสดงข้อความหรือจัดการให้เหมาะสม
    echo "เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
}

$conn->close();
?>
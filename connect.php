<?php
session_start();
$host = "localhost";
$user = "u299560388_661037";
$pass = "VT3142Ql@";
$dbname = "u299560388_661037"; // เปลี่ยนตามจริง

// สร้างการเชื่อมต่อ
$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected success";
?>
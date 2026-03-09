<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Only logged in users
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$conn = get_db_connection();
$schoolId = (int)($_SESSION['school_id'] ?? 0);
if (!$schoolId) {
    // try to get from user directly
    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT school_id FROM users WHERE id=?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($row = $r->fetch_assoc()) {
        $schoolId = (int)$row['school_id'];
    }
    $stmt->close();
}

if (!$schoolId) die("No school context found.");

$type = $_GET['type'] ?? '';

function exportCSV($filename, $headers, $dataRows) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');
    // Add BOM to fix UTF-8 in Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, $headers);
    foreach ($dataRows as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

switch ($type) {
    case 'students':
        $headers = ['Admission Number', 'First Name', 'Last Name', 'Gender', 'DOB', 'Class', 'Guardian Name', 'Guardian Phone'];
        $data = [];
        $stmt = $conn->prepare("
            SELECT s.admission_number, s.first_name, s.last_name, s.gender, s.date_of_birth, c.name as class_name, p.full_name as guardian_name, p.phone as guardian_phone 
            FROM students s 
            LEFT JOIN classes c ON s.class_id = c.id 
            LEFT JOIN parents p ON s.parent_id = p.id 
            WHERE s.school_id = ? 
            ORDER BY c.name, s.first_name
        ");
        $stmt->bind_param('i', $schoolId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $data[] = [
                $row['admission_number'], $row['first_name'], $row['last_name'], 
                $row['gender'], $row['date_of_birth'], $row['class_name'], 
                $row['guardian_name'], $row['guardian_phone']
            ];
        }
        exportCSV('student_roster.csv', $headers, $data);
        break;

    case 'teachers':
        $headers = ['Full Name', 'Email', 'Phone', 'Date Joined'];
        $data = [];
        $stmt = $conn->prepare("SELECT full_name, email, phone, created_at FROM teachers WHERE school_id = ? ORDER BY full_name");
        $stmt->bind_param('i', $schoolId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $data[] = [$row['full_name'], $row['email'], $row['phone'], date('Y-m-d', strtotime($row['created_at']))];
        }
        exportCSV('staff_directory.csv', $headers, $data);
        break;

    case 'lesson_plans':
        $headers = ['Teacher', 'Class', 'Subject', 'Week Start', 'Topic', 'Status'];
        $data = [];
        if ($conn->query("SHOW TABLES LIKE 'lesson_plans'")->num_rows > 0) {
            $stmt = $conn->prepare("
                SELECT t.full_name as teacher, c.name as class_name, s.name as subject_name, lp.week_start, lp.topic, lp.status 
                FROM lesson_plans lp 
                LEFT JOIN teachers t ON lp.teacher_id = t.id 
                LEFT JOIN classes c ON lp.class_id = c.id 
                LEFT JOIN subjects s ON lp.subject_id = s.id 
                WHERE lp.school_id = ? 
                ORDER BY lp.week_start DESC, t.full_name
            ");
            $stmt->bind_param('i', $schoolId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $data[] = [$row['teacher'], $row['class_name'], $row['subject_name'], $row['week_start'], $row['topic'], $row['status']];
            }
        }
        exportCSV('lesson_plans_report.csv', $headers, $data);
        break;
        
    case 'attendance':
        $headers = ['Date', 'Student', 'Class', 'Status', 'Remarks'];
        $data = [];
        if ($conn->query("SHOW TABLES LIKE 'attendance'")->num_rows > 0) {
            $stmt = $conn->prepare("
                SELECT a.date, CONCAT(s.first_name, ' ', s.last_name) as student, c.name as class_name, a.status, a.remarks 
                FROM attendance a 
                JOIN students s ON a.student_id = s.id 
                JOIN classes c ON a.class_id = c.id 
                WHERE a.school_id = ? 
                ORDER BY a.date DESC, c.name, s.first_name
            ");
            $stmt->bind_param('i', $schoolId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $data[] = [$row['date'], $row['student'], $row['class_name'], $row['status'], $row['remarks']];
            }
        }
        exportCSV('attendance_report.csv', $headers, $data);
        break;

    case 'grades':
        $headers = ['Student', 'Class', 'Subject', 'Exam', 'Score', 'Max Score', 'Date Recorded'];
        $data = [];
        if ($conn->query("SHOW TABLES LIKE 'grades'")->num_rows > 0) {
            $stmt = $conn->prepare("
                SELECT CONCAT(s.first_name, ' ', s.last_name) as student, c.name as class_name, sub.name as subject_name, e.name as exam_name, g.score, g.max_score, g.created_at 
                FROM grades g 
                JOIN students s ON g.student_id = s.id 
                JOIN classes c ON g.class_id = c.id 
                JOIN subjects sub ON g.subject_id = sub.id 
                JOIN exam_types e ON g.exam_type_id = e.id 
                WHERE g.school_id = ? 
                ORDER BY c.name, s.first_name, sub.name
            ");
            $stmt->bind_param('i', $schoolId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $data[] = [$row['student'], $row['class_name'], $row['subject_name'], $row['exam_name'], $row['score'], $row['max_score'], $row['created_at']];
            }
        }
        exportCSV('grades_report.csv', $headers, $data);
        break;

    case 'fees':
        $headers = ['Title', 'Student', 'Class', 'Amount', 'Paid', 'Status', 'Due Date'];
        $data = [];
        if ($conn->query("SHOW TABLES LIKE 'invoices'")->num_rows > 0) {
            $stmt = $conn->prepare("
                SELECT i.title, CONCAT(s.first_name, ' ', s.last_name) as student, c.name as class_name, i.total_amount, i.paid_amount, i.status, i.due_date 
                FROM invoices i 
                JOIN students s ON i.student_id = s.id 
                LEFT JOIN classes c ON s.class_id = c.id
                WHERE i.school_id = ? 
                ORDER BY i.created_at DESC
            ");
            $stmt->bind_param('i', $schoolId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $data[] = [$row['title'], $row['student'], $row['class_name'], $row['total_amount'], $row['paid_amount'], $row['status'], $row['due_date']];
            }
        }
        exportCSV('fee_status_report.csv', $headers, $data);
        break;

    default:
        die("Invalid report type specified.");
}

<?php
// Shared parent/wards data for all parent portal pages.
// Expects $conn and $schoolId to be defined (from layout.php).

$parent      = null;
$wards       = [];
$attendanceSummary = [];
$gradesByStudent   = [];
$overallAttendance = ['present' => 0, 'late' => 0, 'absent' => 0, 'total' => 0];
$averageGradeByStudent = [];

$userId   = (int) ($_SESSION['user_id'] ?? 0);
$schoolId = $schoolId ?? current_school_id();

if ($userId && $schoolId && isset($conn)) {
    $stmt = $conn->prepare("
        SELECT p.*
        FROM parents p
        JOIN users u
          ON u.email = p.email
         AND u.school_id = p.school_id
        WHERE u.id = ?
          AND u.role = 'parent'
          AND u.school_id = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('ii', $userId, $schoolId);
        $stmt->execute();
        $res = $stmt->get_result();
        $parent = $res->fetch_assoc() ?: null;
        $stmt->close();
    }

    if ($parent) {
        $parentId = (int) $parent['id'];
        $stmt = $conn->prepare("
            SELECT s.*, c.name AS class_name, c.section AS class_section
            FROM students s
            LEFT JOIN classes c
              ON c.id = s.class_id
             AND c.school_id = s.school_id
            WHERE s.school_id = ?
              AND s.parent_id = ?
            ORDER BY s.first_name, s.last_name
        ");
        if ($stmt) {
            $stmt->bind_param('ii', $schoolId, $parentId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $wards[] = $row;
            }
            $stmt->close();
        }
    }
}

// Check for optional tables
$hasAttendanceTable = false;
$hasGradesTables    = false;
if (isset($conn)) {
    $res = $conn->query("SHOW TABLES LIKE 'attendance'");
    $hasAttendanceTable = $res && $res->num_rows > 0;

    $res = $conn->query("SHOW TABLES LIKE 'grades'");
    if ($res && $res->num_rows > 0) {
        $res2 = $conn->query("SHOW TABLES LIKE 'exam_types'");
        $hasGradesTables = $res2 && $res2->num_rows > 0;
    }
}

// Attendance summary for each ward (last 30 days)
if ($hasAttendanceTable && $wards) {
    $stmt = $conn->prepare("
        SELECT status, COUNT(*) AS c
        FROM attendance
        WHERE school_id = ?
          AND student_id = ?
          AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY status
    ");
    if ($stmt) {
        foreach ($wards as $w) {
            $sid = (int) $w['id'];
            $summary = ['present' => 0, 'late' => 0, 'absent' => 0, 'total' => 0];

            $stmt->bind_param('ii', $schoolId, $sid);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $status = $row['status'] ?? 'present';
                $count  = (int) ($row['c'] ?? 0);
                if (isset($summary[$status])) {
                    $summary[$status] += $count;
                }
                $summary['total'] += $count;
            }
            $attendanceSummary[$sid] = $summary;

            $overallAttendance['present'] += $summary['present'];
            $overallAttendance['late']    += $summary['late'];
            $overallAttendance['absent']  += $summary['absent'];
            $overallAttendance['total']   += $summary['total'];
        }
        $stmt->close();
    }
}

// Recent grades for each ward
if ($hasGradesTables && $wards) {
    $stmt = $conn->prepare("
        SELECT g.student_id,
               g.score,
               g.max_score,
               g.created_at,
               subj.name AS subject_name,
               et.name   AS exam_name,
               c.name    AS class_name,
               c.section AS class_section
        FROM grades g
        LEFT JOIN subjects subj
          ON subj.id = g.subject_id
         AND subj.school_id = g.school_id
        LEFT JOIN exam_types et
          ON et.id = g.exam_type_id
         AND et.school_id = g.school_id
        LEFT JOIN classes c
          ON c.id = g.class_id
         AND c.school_id = g.school_id
        WHERE g.school_id = ?
          AND g.student_id = ?
        ORDER BY g.created_at DESC
        LIMIT 10
    ");
    if ($stmt) {
        foreach ($wards as $w) {
            $sid = (int) $w['id'];
            $gradesByStudent[$sid] = [];

            $stmt->bind_param('ii', $schoolId, $sid);
            $stmt->execute();
            $res = $stmt->get_result();
            $sumPercent = 0.0;
            $count      = 0;
            while ($row = $res->fetch_assoc()) {
                $gradesByStudent[$sid][] = $row;
                if ($row['max_score'] > 0) {
                    $percent = ((float) $row['score'] / (float) $row['max_score']) * 100.0;
                    $sumPercent += $percent;
                    $count++;
                }
            }
            if ($count > 0) {
                $averageGradeByStudent[$sid] = $sumPercent / $count;
            }
        }
        $stmt->close();
    }
}

$totalWards = count($wards);


<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/layout.php';
?>

<!-- KPI cards focused on teacher workflow -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
    <div class="flex items-center gap-2.5 px-5 py-3.5 border-b border-slate-100">
        <i data-lucide="bar-chart-2" class="w-4 h-4 text-emerald-600"></i>
        <span class="text-sm font-semibold text-slate-800">Today at a glance</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-slate-100">
        <div class="px-5 py-5">
            <div class="text-xs text-slate-500 mb-2">Classes assigned</div>
            <div class="text-3xl font-bold text-emerald-600 mb-1">–</div>
            <div class="text-[11px] text-slate-400">Number of classes you handle</div>
        </div>
        <div class="px-5 py-5">
            <div class="text-xs text-slate-500 mb-2">Lessons today</div>
            <div class="text-3xl font-bold text-indigo-600 mb-1">–</div>
            <div class="text-[11px] text-slate-400">Periods scheduled for today</div>
        </div>
        <div class="px-5 py-5">
            <div class="text-xs text-slate-500 mb-2">Attendance marked</div>
            <div class="text-3xl font-bold text-orange-500 mb-1">–</div>
            <div class="text-[11px] text-slate-400">Classes with completed attendance</div>
        </div>
        <div class="px-5 py-5">
            <div class="text-xs text-slate-500 mb-2">Grading pending</div>
            <div class="text-3xl font-bold text-rose-500 mb-1">–</div>
            <div class="text-[11px] text-slate-400">Assignments/exams to grade</div>
        </div>
    </div>
</div>

<!-- Main teacher actions -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Teaching tools -->
    <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-800">Your teaching workspace</h2>
            <span class="text-[11px] text-slate-400">Quick links</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <a href="classes.php"
               class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">
                <i data-lucide="book-open" class="w-4 h-4 shrink-0"></i>
                <span>Classes assigned to me</span>
            </a>
            <a href="attendance.php"
               class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                <i data-lucide="calendar-check" class="w-4 h-4 shrink-0"></i>
                <span>Record student attendance</span>
            </a>
            <a href="grades.php"
               class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700 transition-colors">
                <i data-lucide="clipboard-list" class="w-4 h-4 shrink-0"></i>
                <span>Enter &amp; review grades</span>
            </a>
            <a href="lesson_notes.php"
               class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700 transition-colors">
                <i data-lucide="file-text" class="w-4 h-4 shrink-0"></i>
                <span>Prepare lesson notes</span>
            </a>
        </div>
    </div>

    <!-- Timetable & subjects quick view -->
    <div class="space-y-4">
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-800">Today&rsquo;s timetable</h2>
                <a href="timetable.php" class="text-[11px] text-emerald-600 hover:text-emerald-700 font-medium">View full</a>
            </div>
            <p class="text-[11px] text-slate-500">
                This widget will list today&rsquo;s periods, with class, subject and room for each slot.
            </p>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-800">Subjects you teach</h2>
                <a href="subjects.php" class="text-[11px] text-emerald-600 hover:text-emerald-700 font-medium">View all</a>
            </div>
            <p class="text-[11px] text-slate-500">
                This widget will summarize your subjects across classes (e.g. JSS1A Mathematics, JSS2B Basic Science).
            </p>
        </div>
    </div>
</div>

<!-- Reports & analytics overview -->
<div class="bg-white border border-slate-200 rounded-xl p-5">
    <div class="flex items-center justify-between mb-2">
        <h2 class="text-sm font-semibold text-slate-800">Reports &amp; analytics</h2>
        <a href="reports_analytics.php" class="text-[11px] text-slate-500 hover:text-slate-700">Open reports</a>
    </div>
    <p class="text-[11px] text-slate-500 mb-3">
        This section will hold charts for attendance trends, performance by class/subject, and at‑risk students,
        scoped only to the classes and subjects assigned to you.
    </p>
</div>

<?php require __DIR__ . '/footer.php'; ?>


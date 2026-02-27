<?php
$page_title = 'Subjects';
require_once __DIR__ . '/layout.php';
?>

<!-- Header & filters -->
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">Subjects you teach</h2>
            <p class="text-[11px] text-slate-500">
                View all your subjects across classes and streams, with shortcuts into lesson notes, grading and attendance.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <select class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>All levels</option>
                <option>JSS</option>
                <option>SSS</option>
            </select>
            <select class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>All subjects</option>
                <option>Mathematics</option>
                <option>English</option>
                <option>Basic Science</option>
                <option>ICT</option>
            </select>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500">
        <span>These are sample assignments – they’ll later be loaded from the database based on your timetable/subject allocation.</span>
        <span class="inline-flex items-center gap-2">
            <span class="inline-flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Core</span>
            </span>
            <span class="inline-flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                <span>Elective</span>
            </span>
            <span class="inline-flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                <span>Exam class</span>
            </span>
        </span>
    </div>
</div>

<!-- Subjects grid -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    <!-- Card 1 -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col gap-3">
        <div class="flex items-start justify-between gap-2">
            <div>
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-[10px] font-medium text-emerald-700">Mathematics</span>
                </div>
                <div class="mt-1 text-[11px] text-slate-500">
                    Level: <span class="font-medium text-slate-700">JSS1</span>
                    <span class="mx-1">•</span>
                    Stream: <span class="font-medium text-slate-700">A</span>
                </div>
            </div>
            <div class="text-right text-[10px] text-slate-400">
                <div>Exam class</div>
                <div class="mt-0.5 inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    <span>JSS3 link</span>
                </div>
            </div>
        </div>

        <div class="text-[11px] text-slate-500">
            You handle this subject for <span class="font-medium text-slate-700">JSS1 A</span>. Lessons appear on
            <span class="font-medium text-slate-700">Mon, Wed &amp; Thu</span>.
        </div>

        <div class="flex flex-wrap gap-2 mt-1">
            <a href="lesson_notes.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700">
                <i data-lucide="file-text" class="w-3 h-3"></i>
                <span>Lesson notes</span>
            </a>
            <a href="grades.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700">
                <i data-lucide="clipboard-list" class="w-3 h-3"></i>
                <span>Grades</span>
            </a>
            <a href="attendance.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">
                <i data-lucide="calendar-check" class="w-3 h-3"></i>
                <span>Attendance</span>
            </a>
        </div>
    </div>

    <!-- Card 2 -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col gap-3">
        <div class="flex items-start justify-between gap-2">
            <div>
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-[10px] font-medium text-emerald-700">English</span>
                </div>
                <div class="mt-1 text-[11px] text-slate-500">
                    Level: <span class="font-medium text-slate-700">JSS2</span>
                    <span class="mx-1">•</span>
                    Stream: <span class="font-medium text-slate-700">B</span>
                </div>
            </div>
            <div class="text-right text-[10px] text-slate-400">
                <div>Core subject</div>
            </div>
        </div>

        <div class="text-[11px] text-slate-500">
            You handle this subject for <span class="font-medium text-slate-700">JSS2 B</span>. Lessons appear on
            <span class="font-medium text-slate-700">Tue &amp; Fri</span>.
        </div>

        <div class="flex flex-wrap gap-2 mt-1">
            <a href="lesson_notes.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700">
                <i data-lucide="file-text" class="w-3 h-3"></i>
                <span>Lesson notes</span>
            </a>
            <a href="grades.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700">
                <i data-lucide="clipboard-list" class="w-3 h-3"></i>
                <span>Grades</span>
            </a>
            <a href="attendance.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">
                <i data-lucide="calendar-check" class="w-3 h-3"></i>
                <span>Attendance</span>
            </a>
        </div>
    </div>

    <!-- Card 3 -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col gap-3">
        <div class="flex items-start justify-between gap-2">
            <div>
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-sky-50 border border-sky-200">
                    <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                    <span class="text-[10px] font-medium text-sky-700">ICT</span>
                </div>
                <div class="mt-1 text-[11px] text-slate-500">
                    Level: <span class="font-medium text-slate-700">SS1</span>
                    <span class="mx-1">•</span>
                    Stream: <span class="font-medium text-slate-700">C</span>
                </div>
            </div>
            <div class="text-right text-[10px] text-slate-400">
                <div>Elective</div>
            </div>
        </div>

        <div class="text-[11px] text-slate-500">
            You handle this subject for <span class="font-medium text-slate-700">SS1 C</span>. Lessons appear on
            <span class="font-medium text-slate-700">Wed (double) &amp; Thu</span>.
        </div>

        <div class="flex flex-wrap gap-2 mt-1">
            <a href="lesson_notes.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700">
                <i data-lucide="file-text" class="w-3 h-3"></i>
                <span>Lesson notes</span>
            </a>
            <a href="grades.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700">
                <i data-lucide="clipboard-list" class="w-3 h-3"></i>
                <span>Grades</span>
            </a>
            <a href="attendance.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">
                <i data-lucide="calendar-check" class="w-3 h-3"></i>
                <span>Attendance</span>
            </a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>


<?php
$page_title = 'Classes';
require_once __DIR__ . '/layout.php';
?>

<!-- Header & filters -->
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">Classes assigned to you</h2>
            <p class="text-[11px] text-slate-500">
                View all classes and streams you handle, with shortcuts into attendance, grading and lesson notes.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <select class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>All levels</option>
                <option>JSS1</option>
                <option>JSS2</option>
                <option>JSS3</option>
            </select>
            <select class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>All streams</option>
                <option>A</option>
                <option>B</option>
                <option>C</option>
            </select>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500">
        <span>These are sample classes – they’ll later be loaded from the database based on your timetable/assignments.</span>
        <span class="inline-flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Core subject
            <span class="w-2 h-2 rounded-full bg-sky-500 ml-3"></span> Elective
        </span>
    </div>
</div>

<!-- Classes list -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    <!-- Card 1 -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col gap-3">
        <div class="flex items-start justify-between gap-2">
            <div>
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-[10px] font-medium text-emerald-700">JSS1 A</span>
                </div>
                <div class="mt-1 text-[11px] text-slate-500">
                    Homeroom: <span class="font-medium text-slate-700">Form Teacher</span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-[10px] text-slate-400">Students</div>
                <div class="text-base font-bold text-slate-800">–</div>
            </div>
        </div>

        <div class="flex items-center justify-between text-[11px] text-slate-500">
            <span>Subjects you teach: <span class="font-medium text-slate-700">Mathematics, Basic Science</span></span>
        </div>

        <div class="flex flex-wrap gap-2 mt-1">
            <a href="attendance.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">
                <i data-lucide="calendar-check" class="w-3 h-3"></i>
                <span>Attendance</span>
            </a>
            <a href="grades.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700">
                <i data-lucide="clipboard-list" class="w-3 h-3"></i>
                <span>Grades</span>
            </a>
            <a href="lesson_notes.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700">
                <i data-lucide="file-text" class="w-3 h-3"></i>
                <span>Lesson notes</span>
            </a>
        </div>
    </div>

    <!-- Card 2 -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col gap-3">
        <div class="flex items-start justify-between gap-2">
            <div>
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-50 border border-emerald-200">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-[10px] font-medium text-emerald-700">JSS2 B</span>
                </div>
                <div class="mt-1 text-[11px] text-slate-500">
                    Homeroom: <span class="font-medium text-slate-700">Not form teacher</span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-[10px] text-slate-400">Students</div>
                <div class="text-base font-bold text-slate-800">–</div>
            </div>
        </div>

        <div class="flex items-center justify-between text-[11px] text-slate-500">
            <span>Subjects you teach: <span class="font-medium text-slate-700">English</span></span>
        </div>

        <div class="flex flex-wrap gap-2 mt-1">
            <a href="attendance.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">
                <i data-lucide="calendar-check" class="w-3 h-3"></i>
                <span>Attendance</span>
            </a>
            <a href="grades.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700">
                <i data-lucide="clipboard-list" class="w-3 h-3"></i>
                <span>Grades</span>
            </a>
            <a href="lesson_notes.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700">
                <i data-lucide="file-text" class="w-3 h-3"></i>
                <span>Lesson notes</span>
            </a>
        </div>
    </div>

    <!-- Card 3 -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col gap-3">
        <div class="flex items-start justify-between gap-2">
            <div>
                <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-sky-50 border border-sky-200">
                    <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                    <span class="text-[10px] font-medium text-sky-700">SS1 C</span>
                </div>
                <div class="mt-1 text-[11px] text-slate-500">
                    Homeroom: <span class="font-medium text-slate-700">Elective only</span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-[10px] text-slate-400">Students</div>
                <div class="text-base font-bold text-slate-800">–</div>
            </div>
        </div>

        <div class="flex items-center justify-between text-[11px] text-slate-500">
            <span>Subjects you teach: <span class="font-medium text-slate-700">ICT</span></span>
        </div>

        <div class="flex flex-wrap gap-2 mt-1">
            <a href="attendance.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">
                <i data-lucide="calendar-check" class="w-3 h-3"></i>
                <span>Attendance</span>
            </a>
            <a href="grades.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-700">
                <i data-lucide="clipboard-list" class="w-3 h-3"></i>
                <span>Grades</span>
            </a>
            <a href="lesson_notes.php" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700">
                <i data-lucide="file-text" class="w-3 h-3"></i>
                <span>Lesson notes</span>
            </a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
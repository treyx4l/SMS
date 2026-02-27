<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/layout.php';
?>

<!-- Top: Today at a glance -->
<div class="bg-white border border-slate-200 rounded-xl overflow-hidden mb-4">
    <div class="flex items-center justify-between gap-2.5 px-5 py-3.5 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
            <i data-lucide="bar-chart-2" class="w-4 h-4 text-emerald-600"></i>
            <span class="text-sm font-semibold text-slate-800">Today at a glance</span>
        </div>
        <span class="text-[11px] text-slate-400">All numbers below are sample placeholders</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-slate-100">
        <div class="px-5 py-4">
            <div class="text-xs text-slate-500 mb-1.5">Classes assigned</div>
            <div class="text-2xl md:text-3xl font-bold text-emerald-600 mb-0.5">3</div>
            <div class="text-[11px] text-slate-400">Total homeroom + subject classes</div>
        </div>
        <div class="px-5 py-4">
            <div class="text-xs text-slate-500 mb-1.5">Lessons today</div>
            <div class="text-2xl md:text-3xl font-bold text-indigo-600 mb-0.5">5</div>
            <div class="text-[11px] text-slate-400">Periods on today’s timetable</div>
        </div>
        <div class="px-5 py-4">
            <div class="text-xs text-slate-500 mb-1.5">Attendance marked</div>
            <div class="text-2xl md:text-3xl font-bold text-amber-500 mb-0.5">2/5</div>
            <div class="text-[11px] text-slate-400">Classes with completed attendance</div>
        </div>
        <div class="px-5 py-4">
            <div class="text-xs text-slate-500 mb-1.5">Grading pending</div>
            <div class="text-2xl md:text-3xl font-bold text-rose-500 mb-0.5">7</div>
            <div class="text-[11px] text-slate-400">Assignments / tests to record</div>
        </div>
    </div>
</div>

<!-- Main content grid -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <!-- Teaching workspace & open items -->
    <div class="xl:col-span-2 space-y-4">
        <!-- Teaching workspace quick actions -->
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-slate-800">Your teaching workspace</h2>
                <span class="text-[11px] text-slate-400">Jump into the most common tasks</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                <a href="classes.php"
                   class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">
                    <i data-lucide="book-open" class="w-4 h-4 shrink-0"></i>
                    <div class="flex flex-col">
                        <span>Classes assigned to me</span>
                        <span class="text-[10px] text-slate-400">See all classes, streams &amp; shortcuts</span>
                    </div>
                </a>
                <a href="timetable.php"
                   class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700 transition-colors">
                    <i data-lucide="calendar" class="w-4 h-4 shrink-0"></i>
                    <div class="flex flex-col">
                        <span>View my timetable</span>
                        <span class="text-[10px] text-slate-400">See periods for the week</span>
                    </div>
                </a>
                <a href="attendance.php"
                   class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                    <i data-lucide="calendar-check" class="w-4 h-4 shrink-0"></i>
                    <div class="flex flex-col">
                        <span>Record student attendance</span>
                        <span class="text-[10px] text-slate-400">Mark present / late / absent</span>
                    </div>
                </a>
                <a href="subjects.php"
                   class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800 transition-colors">
                    <i data-lucide="layers" class="w-4 h-4 shrink-0"></i>
                    <div class="flex flex-col">
                        <span>Subjects I teach</span>
                        <span class="text-[10px] text-slate-400">Per level, stream &amp; core/elective</span>
                    </div>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-[11px]">
                <a href="grades.php"
                   class="flex items-center justify-between px-3 py-2 rounded-lg border border-slate-200 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-800">
                    <div class="flex flex-col">
                        <span class="font-medium text-slate-700">Enter / review grades</span>
                        <span class="text-[10px] text-slate-400">Assignments, tests &amp; exams</span>
                    </div>
                    <i data-lucide="clipboard-list" class="w-3 h-3 shrink-0"></i>
                </a>
                <a href="lesson_notes.php"
                   class="flex items-center justify-between px-3 py-2 rounded-lg border border-slate-200 hover:border-amber-300 hover:bg-amber-50 hover:text-amber-800">
                    <div class="flex flex-col">
                        <span class="font-medium text-slate-700">Prepare lesson notes</span>
                        <span class="text-[10px] text-slate-400">Draft, submit &amp; track status</span>
                    </div>
                    <i data-lucide="file-text" class="w-3 h-3 shrink-0"></i>
                </a>
                <a href="analytics.php"
                   class="flex items-center justify-between px-3 py-2 rounded-lg border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800">
                    <div class="flex flex-col">
                        <span class="font-medium text-slate-700">View analytics</span>
                        <span class="text-[10px] text-slate-400">Performance &amp; attendance trends</span>
                    </div>
                    <i data-lucide="activity" class="w-3 h-3 shrink-0"></i>
                </a>
            </div>
        </div>

        <!-- Open items (sample only) -->
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-800">Open items (sample)</h2>
                <span class="text-[11px] text-slate-400">Later, this will be loaded from real data</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-[11px]">
                <div>
                    <h3 class="text-[11px] font-semibold text-slate-700 mb-2">Attendance to complete</h3>
                    <ul class="space-y-1.5">
                        <li class="flex items-center justify-between px-3 py-1.5 rounded-lg border border-slate-100">
                            <div>
                                <div class="font-medium text-slate-700">JSS1 A &mdash; Mathematics</div>
                                <div class="text-[10px] text-slate-400">Today &middot; 1st period</div>
                            </div>
                            <a href="attendance.php" class="text-[10px] text-emerald-600 hover:text-emerald-700 font-semibold">
                                MARK
                            </a>
                        </li>
                        <li class="flex items-center justify-between px-3 py-1.5 rounded-lg border border-slate-100">
                            <div>
                                <div class="font-medium text-slate-700">JSS2 B &mdash; English</div>
                                <div class="text-[10px] text-slate-400">Yesterday &middot; Missing</div>
                            </div>
                            <a href="attendance.php" class="text-[10px] text-emerald-600 hover:text-emerald-700 font-semibold">
                                CATCH UP
                            </a>
                        </li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-[11px] font-semibold text-slate-700 mb-2">Grading / notes</h3>
                    <ul class="space-y-1.5">
                        <li class="flex items-center justify-between px-3 py-1.5 rounded-lg border border-slate-100">
                            <div>
                                <div class="font-medium text-slate-700">Assignment &mdash; Fractions</div>
                                <div class="text-[10px] text-slate-400">JSS1 A &middot; 24 scripts pending</div>
                            </div>
                            <a href="grades.php" class="text-[10px] text-violet-600 hover:text-violet-700 font-semibold">
                                GRADE
                            </a>
                        </li>
                        <li class="flex items-center justify-between px-3 py-1.5 rounded-lg border border-slate-100">
                            <div>
                                <div class="font-medium text-slate-700">Lesson note &mdash; Integers</div>
                                <div class="text-[10px] text-slate-400">JSS1 A &middot; Draft not submitted</div>
                            </div>
                            <a href="lesson_notes.php" class="text-[10px] text-amber-600 hover:text-amber-700 font-semibold">
                                UPDATE
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Right column: timetable, subjects, reports -->
    <div class="space-y-4">
        <!-- Today’s timetable sample -->
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-800">Today&rsquo;s timetable (sample)</h2>
                <a href="timetable.php" class="text-[11px] text-emerald-600 hover:text-emerald-700 font-medium">View full</a>
            </div>
            <div class="space-y-2 text-[11px]">
                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2">
                    <div>
                        <div class="font-semibold text-slate-800">08:00 – 08:40</div>
                        <div class="text-[10px] text-slate-500">Mathematics &middot; JSS1 A &middot; Rm 3</div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px]">
                        Current
                    </span>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2">
                    <div>
                        <div class="font-semibold text-slate-800">08:40 – 09:20</div>
                        <div class="text-[10px] text-slate-500">English &middot; JSS2 B &middot; Rm 5</div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-[10px]">
                        Next
                    </span>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2">
                    <div>
                        <div class="font-semibold text-slate-800">10:20 – 11:00</div>
                        <div class="text-[10px] text-slate-500">ICT &middot; SS1 C &middot; Lab 2</div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-50 text-slate-600 border border-slate-100 text-[10px]">
                        Later
                    </span>
                </div>
            </div>
        </div>

        <!-- Subjects quick summary -->
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-800">Subjects you teach</h2>
                <a href="subjects.php" class="text-[11px] text-emerald-600 hover:text-emerald-700 font-medium">View all</a>
            </div>
            <div class="space-y-1.5 text-[11px]">
                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-1.5">
                    <div>
                        <div class="font-medium text-slate-800">Mathematics</div>
                        <div class="text-[10px] text-slate-500">JSS1 A &middot; Core</div>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px]">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Core
                    </span>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-1.5">
                    <div>
                        <div class="font-medium text-slate-800">English</div>
                        <div class="text-[10px] text-slate-500">JSS2 B &middot; Core</div>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px]">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Core
                    </span>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-1.5">
                    <div>
                        <div class="font-medium text-slate-800">ICT</div>
                        <div class="text-[10px] text-slate-500">SS1 C &middot; Elective</div>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 border border-sky-200 text-[10px]">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                        Elective
                    </span>
                </div>
            </div>
        </div>

        <!-- Reports & analytics overview -->
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-sm font-semibold text-slate-800">Reports &amp; analytics</h2>
                <div class="flex items-center gap-3">
                    <a href="reports.php" class="text-[11px] text-emerald-600 hover:text-emerald-700 font-medium">Open reports</a>
                    <a href="analytics.php" class="text-[11px] text-slate-500 hover:text-slate-700">View analytics</a>
                </div>
            </div>
            <p class="text-[11px] text-slate-500 mb-3">
                This section will later hold charts for attendance trends, performance by class/subject, and at‑risk students,
                scoped only to the classes and subjects assigned to you.
            </p>
            <div class="grid grid-cols-3 gap-2 text-center text-[11px]">
                <div class="border border-slate-100 rounded-lg px-2 py-2">
                    <div class="text-[10px] text-slate-500 mb-1">Attendance (sample)</div>
                    <div class="text-lg font-bold text-emerald-600">92%</div>
                </div>
                <div class="border border-slate-100 rounded-lg px-2 py-2">
                    <div class="text-[10px] text-slate-500 mb-1">Pass rate (sample)</div>
                    <div class="text-lg font-bold text-sky-600">78%</div>
                </div>
                <div class="border border-slate-100 rounded-lg px-2 py-2">
                    <div class="text-[10px] text-slate-500 mb-1">At‑risk students</div>
                    <div class="text-lg font-bold text-rose-500">5</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>


<?php
$page_title = 'Analytics';
require_once __DIR__ . '/layout.php';
?>

<!-- Header & filters -->
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">Analytics</h2>
            <p class="text-[11px] text-slate-500">
                High-level insights for your classes and subjects, combining attendance and performance.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <select class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>Metric focus</option>
                <option>Attendance</option>
                <option>Performance</option>
                <option>Combined</option>
            </select>
            <select class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>Time range</option>
                <option>This week</option>
                <option>This month</option>
                <option>This term</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Class</label>
            <select class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>All classes</option>
                <option>JSS1 A</option>
                <option>JSS2 B</option>
                <option>SS1 C</option>
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Subject</label>
            <select class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>All subjects</option>
                <option>Mathematics</option>
                <option>English</option>
                <option>Basic Science</option>
                <option>ICT</option>
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Term</label>
            <select class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>All terms</option>
                <option>1st Term</option>
                <option>2nd Term</option>
                <option>3rd Term</option>
            </select>
        </div>

        <div class="flex items-end justify-end gap-2">
            <button class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-slate-300 hover:bg-slate-50">
                Reset
            </button>
            <button class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">
                Refresh insights
            </button>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3 text-[11px] text-slate-500">
        <span class="font-medium text-slate-600">Note:</span>
        <span>This is a UI-only analytics skeleton – later, selections here will query real attendance and grades data.</span>
    </div>
</div>

<!-- Main layout: overview + details -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Overview & charts (mocked) -->
    <div class="lg:col-span-2 space-y-4">
        <!-- High-level overview -->
        <div class="bg-white border border-slate-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-800">Class overview (sample)</h2>
                <span class="text-[11px] text-slate-400">Attendance &amp; performance at a glance</span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-center text-[11px]">
                <div class="border border-slate-100 rounded-lg px-2 py-2">
                    <div class="text-[10px] text-slate-500 mb-1">Avg. attendance</div>
                    <div class="text-lg font-bold text-emerald-600">92%</div>
                    <div class="text-[10px] text-slate-400">All classes</div>
                </div>
                <div class="border border-slate-100 rounded-lg px-2 py-2">
                    <div class="text-[10px] text-slate-500 mb-1">Avg. score</div>
                    <div class="text-lg font-bold text-sky-600">74%</div>
                    <div class="text-[10px] text-slate-400">Selected subject(s)</div>
                </div>
                <div class="border border-slate-100 rounded-lg px-2 py-2">
                    <div class="text-[10px] text-slate-500 mb-1">High-performing</div>
                    <div class="text-lg font-bold text-emerald-600">8</div>
                    <div class="text-[10px] text-slate-400">Students &gt;= 80%</div>
                </div>
                <div class="border border-slate-100 rounded-lg px-2 py-2">
                    <div class="text-[10px] text-slate-500 mb-1">At-risk (combined)</div>
                    <div class="text-lg font-bold text-rose-500">5</div>
                    <div class="text-[10px] text-slate-400">&lt; 70% attendance or score</div>
                </div>
            </div>
        </div>

        <!-- Attendance & performance "charts" (table-based mock) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white border border-slate-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-semibold text-slate-800">Attendance trends (sample)</h3>
                    <span class="text-[10px] text-slate-400">Per week</span>
                </div>
                <p class="text-[11px] text-slate-500 mb-2">
                    This block will later be replaced by a line / bar chart showing attendance percentages across the selected period.
                </p>
                <table class="w-full text-[11px] text-left">
                    <thead class="border-b border-slate-100 text-slate-500">
                    <tr>
                        <th class="py-1.5 pr-2 font-semibold text-[10px]">Week</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">JSS1 A</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">JSS2 B</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">SS1 C</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="border-b border-slate-50">
                        <td class="py-1.5 pr-2 text-[10px] text-slate-500">Week 1</td>
                        <td class="py-1.5 pr-2 text-center text-emerald-600 font-semibold">94%</td>
                        <td class="py-1.5 pr-2 text-center text-emerald-600 font-semibold">91%</td>
                        <td class="py-1.5 pr-2 text-center text-emerald-600 font-semibold">89%</td>
                    </tr>
                    <tr class="border-b border-slate-50">
                        <td class="py-1.5 pr-2 text-[10px] text-slate-500">Week 2</td>
                        <td class="py-1.5 pr-2 text-center text-emerald-600 font-semibold">96%</td>
                        <td class="py-1.5 pr-2 text-center text-emerald-600 font-semibold">92%</td>
                        <td class="py-1.5 pr-2 text-center text-amber-600 font-semibold">84%</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 pr-2 text-[10px] text-slate-500">Week 3</td>
                        <td class="py-1.5 pr-2 text-center text-emerald-600 font-semibold">93%</td>
                        <td class="py-1.5 pr-2 text-center text-amber-600 font-semibold">82%</td>
                        <td class="py-1.5 pr-2 text-center text-rose-500 font-semibold">72%</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-semibold text-slate-800">Performance insights (sample)</h3>
                    <span class="text-[10px] text-slate-400">Recent assessment</span>
                </div>
                <p class="text-[11px] text-slate-500 mb-2">
                    This block will later display charts for score distribution, averages and grade breakdowns.
                </p>
                <table class="w-full text-[11px] text-left">
                    <thead class="border-b border-slate-100 text-slate-500">
                    <tr>
                        <th class="py-1.5 pr-2 font-semibold text-[10px]">Range</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">Count</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">Share</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="border-b border-slate-50">
                        <td class="py-1.5 pr-2">80 &ndash; 100 (A)</td>
                        <td class="py-1.5 pr-2 text-center">9</td>
                        <td class="py-1.5 pr-2 text-center text-emerald-600 font-semibold">28%</td>
                    </tr>
                    <tr class="border-b border-slate-50">
                        <td class="py-1.5 pr-2">60 &ndash; 79 (B)</td>
                        <td class="py-1.5 pr-2 text-center">14</td>
                        <td class="py-1.5 pr-2 text-center text-sky-600 font-semibold">44%</td>
                    </tr>
                    <tr class="border-b border-slate-50">
                        <td class="py-1.5 pr-2">50 &ndash; 59 (C)</td>
                        <td class="py-1.5 pr-2 text-center">5</td>
                        <td class="py-1.5 pr-2 text-center text-amber-600 font-semibold">16%</td>
                    </tr>
                    <tr>
                        <td class="py-1.5 pr-2">&lt; 50 (D/E/F)</td>
                        <td class="py-1.5 pr-2 text-center">4</td>
                        <td class="py-1.5 pr-2 text-center text-rose-500 font-semibold">12%</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar: at-risk & shortcuts -->
    <div class="space-y-4">
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-2">At-risk students (sample)</h3>
            <p class="text-[11px] text-slate-500 mb-2">
                Later, this list will be generated based on low attendance and/or low performance, scoped to your classes.
            </p>
            <div class="space-y-1.5 text-[11px]">
                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-1.5">
                    <div>
                        <div class="font-medium text-slate-800">Adeyemi T.</div>
                        <div class="text-[10px] text-slate-500">JSS1 A &middot; Attendance: 68% &middot; Avg: 55%</div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-200 text-[10px]">
                        High risk
                    </span>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-1.5">
                    <div>
                        <div class="font-medium text-slate-800">Bisi O.</div>
                        <div class="text-[10px] text-slate-500">JSS2 B &middot; Attendance: 82% &middot; Avg: 48%</div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-50 text-amber-600 border border-amber-200 text-[10px]">
                        Academic risk
                    </span>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-1.5">
                    <div>
                        <div class="font-medium text-slate-800">Chidi K.</div>
                        <div class="text-[10px] text-slate-500">SS1 C &middot; Attendance: 72% &middot; Avg: 62%</div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px]">
                        Monitor
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-2">Shortcuts</h3>
            <p class="text-[11px] text-slate-500 mb-2">
                Use these links to jump into the underlying data for more detail.
            </p>
            <div class="space-y-1.5 text-[11px]">
                <a href="attendance.php" class="flex items-center justify-between px-3 py-1.5 rounded-lg border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-800">
                    <span>Open attendance workspace</span>
                    <i data-lucide="calendar-check" class="w-3 h-3"></i>
                </a>
                <a href="grades.php" class="flex items-center justify-between px-3 py-1.5 rounded-lg border border-slate-200 hover:border-violet-300 hover:bg-violet-50 hover:text-violet-800">
                    <span>Open grading workspace</span>
                    <i data-lucide="clipboard-list" class="w-3 h-3"></i>
                </a>
                <a href="reports.php" class="flex items-center justify-between px-3 py-1.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-800">
                    <span>Generate detailed report</span>
                    <i data-lucide="bar-chart-2" class="w-3 h-3"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>


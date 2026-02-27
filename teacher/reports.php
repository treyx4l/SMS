<?php
$page_title = 'Reports';
require_once __DIR__ . '/layout.php';
?>

<!-- Header & filters -->
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">Reports</h2>
            <p class="text-[11px] text-slate-500">
                Generate printable and exportable summaries for attendance, grading and class performance.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <select class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>Report type</option>
                <option>Attendance summary</option>
                <option>Grade sheet</option>
                <option>End-of-term result</option>
            </select>
            <select class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>Class</option>
                <option>JSS1 A</option>
                <option>JSS1 B</option>
                <option>JSS2 B</option>
            </select>
            <select class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>Subject (optional)</option>
                <option>All subjects</option>
                <option>Mathematics</option>
                <option>English</option>
                <option>Basic Science</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">From date</label>
            <input type="date" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" />
        </div>
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">To date</label>
            <input type="date" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" />
        </div>
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Format</label>
            <select class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>On-screen preview</option>
                <option>PDF (print-ready)</option>
                <option>Excel / CSV</option>
            </select>
        </div>
        <div class="flex items-end justify-end gap-2">
            <button class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-slate-300 hover:bg-slate-50">
                Clear
            </button>
            <button class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">
                Generate report
            </button>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3 text-[11px] text-slate-500">
        <span class="font-medium text-slate-600">Note:</span>
        <span>This is a front-end skeleton – later, your selections will query the database and build the report automatically.</span>
    </div>
</div>

<!-- Main layout: preview + saved reports -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Report preview -->
    <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl flex flex-col">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-800">Report preview (sample)</span>
                <p class="text-[11px] text-slate-400">
                    This area will show a preview of the selected report type before you download or print it.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button class="inline-flex items-center justify-center px-2.5 py-1.5 rounded-lg border border-slate-200 text-[10px] text-slate-700 hover:border-slate-300 hover:bg-slate-50">
                    <i data-lucide="printer" class="w-3 h-3 mr-1"></i>
                    Print
                </button>
                <button class="inline-flex items-center justify-center px-2.5 py-1.5 rounded-lg border border-slate-200 text-[10px] text-slate-700 hover:border-slate-300 hover:bg-slate-50">
                    <i data-lucide="download" class="w-3 h-3 mr-1"></i>
                    Download
                </button>
            </div>
        </div>

        <div class="p-4 space-y-4 text-[11px] text-slate-700 overflow-y-auto">
            <!-- Sample: Attendance summary layout -->
            <div class="border border-slate-100 rounded-lg p-3">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <div class="text-[11px] font-semibold text-slate-800">Attendance summary &mdash; JSS1 A</div>
                        <div class="text-[10px] text-slate-400">01 Sep 2024 &ndash; 30 Sep 2024 &middot; Subject: All</div>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-50 text-slate-600 border border-slate-100 text-[10px]">
                        <i data-lucide="calendar-check" class="w-3 h-3"></i>
                        Sample layout
                    </span>
                </div>
                <div class="grid grid-cols-4 gap-2 text-center mb-2">
                    <div class="border border-slate-100 rounded-lg px-2 py-2">
                        <div class="text-[10px] text-slate-500 mb-1">Total students</div>
                        <div class="text-lg font-bold text-slate-800">32</div>
                    </div>
                    <div class="border border-slate-100 rounded-lg px-2 py-2">
                        <div class="text-[10px] text-slate-500 mb-1">Avg. attendance</div>
                        <div class="text-lg font-bold text-emerald-600">93%</div>
                    </div>
                    <div class="border border-slate-100 rounded-lg px-2 py-2">
                        <div class="text-[10px] text-slate-500 mb-1">Highest</div>
                        <div class="text-lg font-bold text-sky-600">100%</div>
                    </div>
                    <div class="border border-slate-100 rounded-lg px-2 py-2">
                        <div class="text-[10px] text-slate-500 mb-1">Lowest</div>
                        <div class="text-lg font-bold text-rose-500">65%</div>
                    </div>
                </div>
                <table class="w-full text-left text-[11px] mt-2">
                    <thead class="border-b border-slate-100 text-slate-500">
                    <tr>
                        <th class="py-1.5 pr-2 font-semibold text-[10px]">#</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px]">Student</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">Present</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">Absent</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">Late</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">Attendance %</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="border-b border-slate-50">
                        <td class="py-1.5 pr-2 text-[10px] text-slate-400">1</td>
                        <td class="py-1.5 pr-2">Jane Doe</td>
                        <td class="py-1.5 pr-2 text-center">18</td>
                        <td class="py-1.5 pr-2 text-center">0</td>
                        <td class="py-1.5 pr-2 text-center">1</td>
                        <td class="py-1.5 pr-2 text-center font-semibold text-emerald-600">95%</td>
                    </tr>
                    <tr class="border-b border-slate-50">
                        <td class="py-1.5 pr-2 text-[10px] text-slate-400">2</td>
                        <td class="py-1.5 pr-2">John Smith</td>
                        <td class="py-1.5 pr-2 text-center">17</td>
                        <td class="py-1.5 pr-2 text-center">1</td>
                        <td class="py-1.5 pr-2 text-center">1</td>
                        <td class="py-1.5 pr-2 text-center font-semibold text-emerald-600">90%</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Sample: Grade sheet layout -->
            <div class="border border-slate-100 rounded-lg p-3">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <div class="text-[11px] font-semibold text-slate-800">Grade sheet &mdash; JSS1 A Mathematics</div>
                        <div class="text-[10px] text-slate-400">2nd Term &middot; CA (40) + Exam (60)</div>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-50 text-slate-600 border border-slate-100 text-[10px]">
                        <i data-lucide="clipboard-list" class="w-3 h-3"></i>
                        Sample layout
                    </span>
                </div>
                <table class="w-full text-left text-[11px]">
                    <thead class="border-b border-slate-100 text-slate-500">
                    <tr>
                        <th class="py-1.5 pr-2 font-semibold text-[10px]">#</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px]">Student</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">CA (40)</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">Exam (60)</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">Total (100)</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">Grade</th>
                        <th class="py-1.5 pr-2 font-semibold text-[10px] text-center">Position</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="border-b border-slate-50">
                        <td class="py-1.5 pr-2 text-[10px] text-slate-400">1</td>
                        <td class="py-1.5 pr-2">Jane Doe</td>
                        <td class="py-1.5 pr-2 text-center">34</td>
                        <td class="py-1.5 pr-2 text-center">52</td>
                        <td class="py-1.5 pr-2 text-center font-semibold text-slate-800">86</td>
                        <td class="py-1.5 pr-2 text-center font-semibold text-emerald-700">A</td>
                        <td class="py-1.5 pr-2 text-center text-slate-500">1st</td>
                    </tr>
                    <tr class="border-b border-slate-50">
                        <td class="py-1.5 pr-2 text-[10px] text-slate-400">2</td>
                        <td class="py-1.5 pr-2">John Smith</td>
                        <td class="py-1.5 pr-2 text-center">30</td>
                        <td class="py-1.5 pr-2 text-center">44</td>
                        <td class="py-1.5 pr-2 text-center font-semibold text-slate-800">74</td>
                        <td class="py-1.5 pr-2 text-center font-semibold text-amber-600">B</td>
                        <td class="py-1.5 pr-2 text-center text-slate-500">4th</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar: saved reports & tips -->
    <div class="space-y-4">
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-2">Recently generated (sample)</h3>
            <p class="text-[11px] text-slate-500 mb-2">
                When implemented, this list will show your most recent report exports.
            </p>
            <div class="space-y-1.5 text-[11px]">
                <button class="w-full flex items-center justify-between px-3 py-1.5 rounded-lg border border-slate-200 text-left text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">
                    <span>
                        JSS1 A &mdash; Attendance &mdash; Sep 2024
                        <span class="block text-[10px] text-slate-400">PDF &middot; 2 mins ago</span>
                    </span>
                    <i data-lucide="arrow-down-circle" class="w-3 h-3"></i>
                </button>
                <button class="w-full flex items-center justify-between px-3 py-1.5 rounded-lg border border-slate-200 text-left text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">
                    <span>
                        JSS2 B &mdash; Mathematics &mdash; Grade sheet
                        <span class="block text-[10px] text-slate-400">Excel &middot; Yesterday</span>
                    </span>
                    <i data-lucide="arrow-down-circle" class="w-3 h-3"></i>
                </button>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-2">Tips &amp; best practices</h3>
            <p class="text-[11px] text-slate-500 mb-2">
                A few ideas for making the most of your reports:
            </p>
            <ul class="text-[11px] text-slate-500 space-y-1">
                <li><span class="font-semibold text-slate-700">Attendance reports</span>: Use them to spot patterns in lateness or repeated absence.</li>
                <li><span class="font-semibold text-slate-700">Grade sheets</span>: Share with heads of department before result finalization.</li>
                <li><span class="font-semibold text-slate-700">End-of-term summaries</span>: Attach to report cards or parent meetings.</li>
            </ul>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>


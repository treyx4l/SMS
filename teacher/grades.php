<?php
$page_title = 'Grades';
require_once __DIR__ . '/layout.php';
?>

<!-- Filters / selection -->
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">Grading workspace</h2>
            <p class="text-[11px] text-slate-500">
                Select class, subject, term and assessment setup, then enter or adjust scores for each student.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <select class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>Session (placeholder)</option>
                <option>2024 / 2025</option>
                <option>2025 / 2026</option>
            </select>
            <select class="border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>Term</option>
                <option>1st Term</option>
                <option>2nd Term</option>
                <option>3rd Term</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Class</label>
            <select class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>Select class</option>
                <option>JSS1 A</option>
                <option>JSS1 B</option>
                <option>JSS2 A</option>
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Subject</label>
            <select class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>Select subject</option>
                <option>Mathematics</option>
                <option>English</option>
                <option>Basic Science</option>
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Assessment template</label>
            <select class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>Default: CA (40) + Exam (60)</option>
                <option>CA1 + CA2 + Exam</option>
                <option>Single test + Exam</option>
            </select>
        </div>

        <div class="flex items-end">
            <button class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700 w-full md:w-auto">
                Load grade book
            </button>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3 text-[11px] text-slate-500">
        <span class="font-medium text-slate-600">Note:</span>
        <span>This is a front-end skeleton – later, these filters will load real students and scores from the database.</span>
    </div>
</div>

<!-- Main grade book & summary -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Grade book grid -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-800">Grade book</span>
            <span class="text-[11px] text-slate-400">Weights &amp; calculations are illustrative placeholders</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left text-slate-700">
                <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">#</th>
                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Student name</th>
                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500 text-center">CA1 (10)</th>
                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500 text-center">CA2 (10)</th>
                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500 text-center">Project (20)</th>
                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500 text-center">Exam (60)</th>
                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500 text-center">Total (100)</th>
                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500 text-center">Grade</th>
                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500 text-center">Position</th>
                </tr>
                </thead>
                <tbody>
                <!-- Example row 1 -->
                <tr class="border-b border-slate-100">
                    <td class="px-4 py-2 text-[11px] text-slate-400">1</td>
                    <td class="px-4 py-2 text-[11px]">Jane Doe</td>
                    <td class="px-2 py-2">
                        <input type="number" min="0" max="10" class="w-16 border border-slate-200 rounded-lg px-2 py-1 text-[11px] text-center focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" value="8">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" min="0" max="10" class="w-16 border border-slate-200 rounded-lg px-2 py-1 text-[11px] text-center focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" value="9">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" min="0" max="20" class="w-16 border border-slate-200 rounded-lg px-2 py-1 text-[11px] text-center focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" value="18">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" min="0" max="60" class="w-16 border border-slate-200 rounded-lg px-2 py-1 text-[11px] text-center focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" value="52">
                    </td>
                    <td class="px-2 py-2 text-[11px] font-semibold text-slate-800 text-center">87</td>
                    <td class="px-2 py-2 text-[11px] font-semibold text-emerald-700 text-center">A</td>
                    <td class="px-2 py-2 text-[11px] text-slate-500 text-center">1st</td>
                </tr>

                <!-- Example row 2 -->
                <tr class="border-b border-slate-100">
                    <td class="px-4 py-2 text-[11px] text-slate-400">2</td>
                    <td class="px-4 py-2 text-[11px]">John Smith</td>
                    <td class="px-2 py-2">
                        <input type="number" min="0" max="10" class="w-16 border border-slate-200 rounded-lg px-2 py-1 text-[11px] text-center focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" value="7">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" min="0" max="10" class="w-16 border border-slate-200 rounded-lg px-2 py-1 text-[11px] text-center focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" value="6">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" min="0" max="20" class="w-16 border border-slate-200 rounded-lg px-2 py-1 text-[11px] text-center focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" value="15">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" min="0" max="60" class="w-16 border border-slate-200 rounded-lg px-2 py-1 text-[11px] text-center focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" value="46">
                    </td>
                    <td class="px-2 py-2 text-[11px] font-semibold text-slate-800 text-center">74</td>
                    <td class="px-2 py-2 text-[11px] font-semibold text-amber-600 text-center">B</td>
                    <td class="px-2 py-2 text-[11px] text-slate-500 text-center">2nd</td>
                </tr>

                <!-- More rows will be rendered dynamically later from the database -->
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-[11px] text-slate-500">Scores are not yet persisted – this is a UI-only prototype.</span>
            <div class="flex items-center gap-2">
                <button class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-slate-300 hover:bg-slate-50">
                    Export (CSV / Excel)
                </button>
                <button class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">
                    Save grades
                </button>
            </div>
        </div>
    </div>

    <!-- Summary / configuration -->
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-3">Class summary</h3>
            <div class="grid grid-cols-3 gap-2 text-center mb-3">
                <div class="border border-slate-100 rounded-lg px-2 py-2">
                    <div class="text-[10px] text-slate-500 mb-1">Class average</div>
                    <div class="text-lg font-bold text-emerald-600">–</div>
                </div>
                <div class="border border-slate-100 rounded-lg px-2 py-2">
                    <div class="text-[10px] text-slate-500 mb-1">Highest</div>
                    <div class="text-lg font-bold text-sky-600">–</div>
                </div>
                <div class="border border-slate-100 rounded-lg px-2 py-2">
                    <div class="text-[10px] text-slate-500 mb-1">Lowest</div>
                    <div class="text-lg font-bold text-rose-500">–</div>
                </div>
            </div>
            <p class="text-[11px] text-slate-500">
                Once calculations are wired to the backend, this panel will automatically update as you adjust scores.
            </p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-2">Assessment breakdown</h3>
            <p class="text-[11px] text-slate-500 mb-2">
                Typical 40 / 60 weighting with continuous assessment and exams. These will later be configurable at school or subject level.
            </p>
            <ul class="text-[11px] text-slate-500 space-y-1">
                <li><span class="font-semibold text-slate-700">CA1 (10 marks)</span> – short test / quiz.</li>
                <li><span class="font-semibold text-slate-700">CA2 (10 marks)</span> – assignment or second test.</li>
                <li><span class="font-semibold text-slate-700">Project (20 marks)</span> – project, practical or continuous work.</li>
                <li><span class="font-semibold text-slate-700">Exam (60 marks)</span> – end of term examination.</li>
            </ul>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-2">Grading scale (example)</h3>
            <ul class="text-[11px] text-slate-500 space-y-1">
                <li><span class="font-semibold text-emerald-700">A</span>: 70 – 100</li>
                <li><span class="font-semibold text-amber-600">B</span>: 60 – 69</li>
                <li><span class="font-semibold text-sky-600">C</span>: 50 – 59</li>
                <li><span class="font-semibold text-rose-600">D</span>: 45 – 49</li>
                <li><span class="font-semibold text-slate-600">E / F</span>: 0 – 44</li>
            </ul>
            <p class="mt-2 text-[11px] text-slate-400">
                You’ll later be able to customize this scale per school or curriculum.
            </p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>


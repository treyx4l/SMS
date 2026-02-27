<?php
$page_title = 'Attendance';
require_once __DIR__ . '/layout.php';
?>

<!-- Filters / selection -->
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">Student attendance</h2>
            <p class="text-[11px] text-slate-500">
                Choose class, subject and date, then mark each student as present, late or absent.
            </p>
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
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Date</label>
            <input type="date" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" />
        </div>

        <div class="flex items-end">
            <button class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700 w-full md:w-auto">
                Load students
            </button>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3 text-[11px] text-slate-500">
        <span class="font-medium text-slate-600">Legend:</span>
        <span class="inline-flex items-center gap-1">
            <span class="w-3 h-3 rounded-full bg-emerald-500"></span> Present
        </span>
        <span class="inline-flex items-center gap-1">
            <span class="w-3 h-3 rounded-full bg-amber-400"></span> Late
        </span>
        <span class="inline-flex items-center gap-1">
            <span class="w-3 h-3 rounded-full bg-rose-500"></span> Absent
        </span>
    </div>
</div>

<!-- Main attendance grid & summary -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Attendance grid -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-800">Daily attendance grid</span>
            <span class="text-[11px] text-slate-400">Keyboard-friendly marking coming soon</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left text-slate-700">
                <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">#</th>
                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Student name</th>
                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Status</th>
                    <th class="px-4 py-2 font-semibold text-[11px] text-slate-500">Comment</th>
                </tr>
                </thead>
                <tbody>
                <tr class="border-b border-slate-100">
                    <td class="px-4 py-2 text-[11px] text-slate-400">1</td>
                    <td class="px-4 py-2 text-[11px]">Jane Doe</td>
                    <td class="px-4 py-2">
                        <div class="inline-flex gap-1">
                            <button class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] border border-emerald-200">P</button>
                            <button class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[10px] border border-amber-200">L</button>
                            <button class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 text-[10px] border border-rose-200">A</button>
                        </div>
                    </td>
                    <td class="px-4 py-2">
                        <input type="text" class="w-full border border-slate-200 rounded-lg px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Optional note" />
                    </td>
                </tr>
                <tr class="border-b border-slate-100">
                    <td class="px-4 py-2 text-[11px] text-slate-400">2</td>
                    <td class="px-4 py-2 text-[11px]">John Smith</td>
                    <td class="px-4 py-2">
                        <div class="inline-flex gap-1">
                            <button class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] border border-emerald-200">P</button>
                            <button class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[10px] border border-amber-200">L</button>
                            <button class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 text-[10px] border border-rose-200">A</button>
                        </div>
                    </td>
                    <td class="px-4 py-2">
                        <input type="text" class="w-full border border-slate-200 rounded-lg px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Optional note" />
                    </td>
                </tr>
                <!-- More rows will be rendered dynamically from the database later -->
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-[11px] text-slate-500">Changes are not yet saved to the database – this is a UI skeleton.</span>
            <button class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">
                Save attendance
            </button>
        </div>
    </div>

    <!-- Summary / history shortcuts -->
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-3">Today’s summary</h3>
            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="border border-slate-100 rounded-lg px-2 py-2">
                    <div class="text-[10px] text-slate-500 mb-1">Present</div>
                    <div class="text-lg font-bold text-emerald-600">–</div>
                </div>
                <div class="border border-slate-100 rounded-lg px-2 py-2">
                    <div class="text-[10px] text-slate-500 mb-1">Late</div>
                    <div class="text-lg font-bold text-amber-500">–</div>
                </div>
                <div class="border border-slate-100 rounded-lg px-2 py-2">
                    <div class="text-[10px] text-slate-500 mb-1">Absent</div>
                    <div class="text-lg font-bold text-rose-500">–</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-2">History shortcuts</h3>
            <p class="text-[11px] text-slate-500 mb-3">
                Quickly jump to attendance history for a student or class.
            </p>
            <div class="space-y-2">
                <button class="w-full inline-flex items-center justify-between px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">
                    <span>View class history</span>
                    <i data-lucide="arrow-right" class="w-3 h-3"></i>
                </button>
                <button class="w-full inline-flex items-center justify-between px-3 py-1.5 rounded-lg border border-slate-200 text-xs text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">
                    <span>View student history</span>
                    <i data-lucide="arrow-right" class="w-3 h-3"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
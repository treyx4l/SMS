<?php
$page_title = 'Timetable';
require_once __DIR__ . '/layout.php';
?>

<!-- Filters / selection -->
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">View timetable</h2>
            <p class="text-[11px] text-slate-500">
                See your teaching periods across the week, filtered to only the classes and subjects you handle.
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
            <label class="block text-[11px] font-medium text-slate-600 mb-1">View</label>
            <select class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>Weekly timetable</option>
                <option>Today only</option>
                <option>Specific day</option>
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Class filter (optional)</label>
            <select class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>All classes I teach</option>
                <option>JSS1 A</option>
                <option>JSS2 B</option>
                <option>SS1 C</option>
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Subject filter (optional)</label>
            <select class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>All subjects I teach</option>
                <option>Mathematics</option>
                <option>English</option>
                <option>Basic Science</option>
                <option>ICT</option>
            </select>
        </div>

        <div class="flex items-end justify-end gap-2">
            <button class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-slate-300 hover:bg-slate-50">
                Print / export
            </button>
            <button class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">
                Refresh view
            </button>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3 text-[11px] text-slate-500">
        <span class="font-medium text-slate-600">Note:</span>
        <span>This is a front-end skeleton – later, the grid below will be populated from the central school timetable.</span>
    </div>
</div>

<!-- Main layout: timetable + sidebar -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Timetable grid -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-800">Weekly timetable</span>
            <span class="text-[11px] text-slate-400">Current lesson is highlighted when viewing live.</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left text-slate-700">
                <thead class="bg-slate-50 border-b border-slate-100">
                <tr>
                    <th class="px-3 py-2 font-semibold text-[11px] text-slate-500">Time</th>
                    <th class="px-3 py-2 font-semibold text-[11px] text-slate-500 text-center">Monday</th>
                    <th class="px-3 py-2 font-semibold text-[11px] text-slate-500 text-center">Tuesday</th>
                    <th class="px-3 py-2 font-semibold text-[11px] text-slate-500 text-center">Wednesday</th>
                    <th class="px-3 py-2 font-semibold text-[11px] text-slate-500 text-center">Thursday</th>
                    <th class="px-3 py-2 font-semibold text-[11px] text-slate-500 text-center">Friday</th>
                </tr>
                </thead>
                <tbody>
                <!-- Row 1 -->
                <tr class="border-b border-slate-100">
                    <td class="px-3 py-2 text-[11px] text-slate-500">08:00 – 08:40</td>
                    <td class="px-3 py-2">
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-[10px]">
                            <div class="font-semibold text-emerald-800">Mathematics</div>
                            <div class="text-[10px] text-slate-500">JSS1 A • Rm 3</div>
                        </div>
                    </td>
                    <td class="px-3 py-2">
                        <div class="rounded-lg border border-sky-200 bg-sky-50 px-2 py-1 text-[10px]">
                            <div class="font-semibold text-sky-800">English</div>
                            <div class="text-[10px] text-slate-500">JSS2 B • Rm 5</div>
                        </div>
                    </td>
                    <td class="px-3 py-2"></td>
                    <td class="px-3 py-2">
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-[10px]">
                            <div class="font-semibold text-emerald-800">Mathematics</div>
                            <div class="text-[10px] text-slate-500">JSS1 A • Rm 3</div>
                        </div>
                    </td>
                    <td class="px-3 py-2"></td>
                </tr>

                <!-- Row 2 -->
                <tr class="border-b border-slate-100">
                    <td class="px-3 py-2 text-[11px] text-slate-500">08:40 – 09:20</td>
                    <td class="px-3 py-2"></td>
                    <td class="px-3 py-2">
                        <div class="rounded-lg border border-violet-200 bg-violet-50 px-2 py-1 text-[10px]">
                            <div class="font-semibold text-violet-800">Basic Science</div>
                            <div class="text-[10px] text-slate-500">JSS1 B • Lab 1</div>
                        </div>
                    </td>
                    <td class="px-3 py-2"></td>
                    <td class="px-3 py-2">
                        <div class="rounded-lg border border-sky-200 bg-sky-50 px-2 py-1 text-[10px]">
                            <div class="font-semibold text-sky-800">English</div>
                            <div class="text-[10px] text-slate-500">JSS1 A • Rm 2</div>
                        </div>
                    </td>
                    <td class="px-3 py-2"></td>
                </tr>

                <!-- Row 3 -->
                <tr class="border-b border-slate-100">
                    <td class="px-3 py-2 text-[11px] text-slate-500">09:20 – 10:00</td>
                    <td class="px-3 py-2"></td>
                    <td class="px-3 py-2"></td>
                    <td class="px-3 py-2">
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-2 py-1 text-[10px]">
                            <div class="font-semibold text-amber-800">ICT</div>
                            <div class="text-[10px] text-slate-500">SS1 C • Lab 2</div>
                        </div>
                    </td>
                    <td class="px-3 py-2"></td>
                    <td class="px-3 py-2">
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1 text-[10px]">
                            <div class="font-semibold text-emerald-800">Mathematics</div>
                            <div class="text-[10px] text-slate-500">JSS2 B • Rm 4</div>
                        </div>
                    </td>
                </tr>

                <!-- Break row -->
                <tr class="bg-slate-50 border-y border-slate-100">
                    <td class="px-3 py-2 text-[11px] text-slate-500">10:00 – 10:20</td>
                    <td colspan="5" class="px-3 py-2 text-[11px] text-slate-500">
                        Short break
                    </td>
                </tr>

                <!-- Additional rows would be generated from the timetable later -->
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-[11px] text-slate-500">Entries are examples only – they will be replaced with your real timetable.</span>
            <button class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-slate-900 text-white text-xs font-medium hover:bg-slate-800">
                Open printable view
            </button>
        </div>
    </div>

    <!-- Sidebar: today + legend -->
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-2">Today’s schedule (sample)</h3>
            <p class="text-[11px] text-slate-500 mb-3">
                At a glance view of your periods for the selected day.
            </p>
            <div class="space-y-2 text-[11px]">
                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2">
                    <div>
                        <div class="font-semibold text-slate-800">08:00 – 08:40</div>
                        <div class="text-[10px] text-slate-500">Mathematics • JSS1 A • Rm 3</div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px]">
                        Upcoming
                    </span>
                </div>
                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2">
                    <div>
                        <div class="font-semibold text-slate-800">08:40 – 09:20</div>
                        <div class="text-[10px] text-slate-500">English • JSS2 B • Rm 5</div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-[10px]">
                        Later
                    </span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-2">Legend &amp; tips</h3>
            <p class="text-[11px] text-slate-500 mb-2">
                Colours and labels used in the timetable grid.
            </p>
            <ul class="text-[11px] text-slate-500 space-y-1 mb-3">
                <li><span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-1"></span><span class="font-semibold text-slate-700">Core subjects</span> (e.g. Mathematics, English).</li>
                <li><span class="inline-block w-2 h-2 rounded-full bg-sky-500 mr-1"></span><span class="font-semibold text-slate-700">Language / reading periods</span>.</li>
                <li><span class="inline-block w-2 h-2 rounded-full bg-violet-500 mr-1"></span><span class="font-semibold text-slate-700">Science / lab periods</span>.</li>
                <li><span class="inline-block w-2 h-2 rounded-full bg-amber-500 mr-1"></span><span class="font-semibold text-slate-700">Practical / ICT periods</span>.</li>
            </ul>
            <p class="text-[11px] text-slate-400">
                Later, we can add conflict warnings (overlapping periods) and quick links into attendance and grading for each slot.
            </p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>


<?php
$page_title = 'Lesson Notes';
require_once __DIR__ . '/layout.php';
?>

<!-- Filters / selection -->
<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">Lesson notes workspace</h2>
            <p class="text-[11px] text-slate-500">
                Select class, subject, term and week, then draft or review your lesson notes.
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
            <label class="block text-[11px] font-medium text-slate-600 mb-1">Week</label>
            <select class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                <option>Select week</option>
                <option>Week 1</option>
                <option>Week 2</option>
                <option>Week 3</option>
                <option>Week 4</option>
            </select>
        </div>

        <div class="flex items-end">
            <button class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700 w-full md:w-auto">
                Load / create note
            </button>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-3 text-[11px] text-slate-500">
        <span class="font-medium text-slate-600">Note:</span>
        <span>This is a front-end skeleton – later, these filters will load and save notes from the database.</span>
    </div>
</div>

<!-- Main layout: editor + sidebar -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Editor panel -->
    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 flex flex-col">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <div>
                <span class="text-xs font-semibold text-slate-800">Lesson note editor</span>
                <p class="text-[11px] text-slate-400">
                    Capture objectives, activities and evaluation in a structured way.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <select class="border border-slate-200 rounded-lg px-2 py-1 text-[10px] text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                    <option>Status: Draft</option>
                    <option>Status: Submitted</option>
                    <option>Status: Approved</option>
                </select>
            </div>
        </div>

        <div class="p-4 space-y-3 overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">Topic</label>
                    <input type="text" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" placeholder="e.g. Integers and Number Lines" />
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">Sub-topic</label>
                    <input type="text" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" placeholder="e.g. Comparing integers" />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">Period</label>
                    <input type="text" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" placeholder="e.g. Double period, 80 minutes" />
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">Date</label>
                    <input type="date" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" />
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-600 mb-1">Lesson objectives</label>
                <textarea rows="3" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" placeholder="By the end of the lesson, students should be able to..."></textarea>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-600 mb-1">Instructional materials</label>
                <textarea rows="2" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" placeholder="e.g. Number line chart, marker, projector..."></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">Introduction</label>
                    <textarea rows="4" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Starter activity / review of previous knowledge..."></textarea>
                </div>
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">Lesson development / activities</label>
                    <textarea rows="4" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Step-by-step teacher and student activities..."></textarea>
                </div>
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">Evaluation &amp; assignment</label>
                    <textarea rows="4" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Checking understanding and follow-up work..."></textarea>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-600 mb-1">Remarks / reflection</label>
                <textarea rows="2" class="w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[11px] text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500" placeholder="What went well? What to adjust next time?"></textarea>
            </div>
        </div>

        <div class="px-4 py-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-[11px] text-slate-500">Changes are not yet saved – this is a UI-only prototype.</span>
            <div class="flex items-center gap-2">
                <button class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg border border-slate-200 text-[11px] text-slate-700 hover:border-slate-300 hover:bg-slate-50">
                    Save as draft
                </button>
                <button class="inline-flex items-center justify-center px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700">
                    Submit for approval
                </button>
            </div>
        </div>
    </div>

    <!-- Sidebar: list & templates -->
    <div class="space-y-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-2">This week’s notes</h3>
            <p class="text-[11px] text-slate-500 mb-3">
                Quick overview of notes for the selected class and subject.
            </p>
            <div class="space-y-1 text-[11px]">
                <button class="w-full flex items-center justify-between px-3 py-1.5 rounded-lg border border-slate-200 text-left text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">
                    <span>
                        Week 1 – Integers and Number Lines
                        <span class="block text-[10px] text-slate-400">Status: Approved</span>
                    </span>
                    <span class="text-[10px] text-emerald-700 font-semibold">VIEW</span>
                </button>
                <button class="w-full flex items-center justify-between px-3 py-1.5 rounded-lg border border-slate-200 text-left text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700">
                    <span>
                        Week 2 – Addition and Subtraction of Integers
                        <span class="block text-[10px] text-slate-400">Status: Draft</span>
                    </span>
                    <span class="text-[10px] text-amber-600 font-semibold">EDIT</span>
                </button>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="text-xs font-semibold text-slate-800 mb-2">Templates &amp; guidance</h3>
            <p class="text-[11px] text-slate-500 mb-2">
                Use a simple, repeatable structure across your lesson notes.
            </p>
            <ul class="text-[11px] text-slate-500 space-y-1">
                <li><span class="font-semibold text-slate-700">3-part lesson</span>: Introduction, development, evaluation.</li>
                <li><span class="font-semibold text-slate-700">Objectives</span>: Use measurable verbs (define, solve, compare...).</li>
                <li><span class="font-semibold text-slate-700">Activities</span>: Mix teacher explanation, group work and practice.</li>
                <li><span class="font-semibold text-slate-700">Reflection</span>: Brief note after the lesson to guide improvements.</li>
            </ul>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>


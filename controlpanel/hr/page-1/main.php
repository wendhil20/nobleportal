<?php
//main.php — HR 201 File: Employee List
include ROOT_PATH . "/network/connect.php";
include ROOT_PATH . "/controlpanel/auth/role/auth_guard.php";

requireAccess('hr', 'head');

$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare(
        "SELECT id, first_name, last_name, username, created_at
         FROM nobleuserlist
         WHERE first_name LIKE ? OR last_name LIKE ? OR username LIKE ?
         ORDER BY last_name, first_name"
    );
    $stmt->bind_param("sss", $like, $like, $like);
} else {
    $stmt = $conn->prepare(
        "SELECT id, first_name, last_name, username, created_at
         FROM nobleuserlist
         ORDER BY last_name, first_name"
    );
}
$stmt->execute();
$employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$hasInfo = [];
$res = $conn->query("SELECT DISTINCT user_id FROM nobleuser_employee_information");
while ($row = $res->fetch_assoc()) {
    $hasInfo[(int) $row['user_id']] = true;
}

function initials(string $first, string $last): string
{
    $f = mb_substr(trim($first), 0, 1);
    $l = mb_substr(trim($last), 0, 1);
    return mb_strtoupper($f . $l);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee 201 Files</title>
    <?php include ROOT_PATH . '/link/top.php'; ?>
    <style>
        .thin-scroll {
            scrollbar-width: thin;
            scrollbar-color: #C6CBD1 transparent;
        }
        .thin-scroll::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .thin-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .thin-scroll::-webkit-scrollbar-thumb {
            background-color: #C6CBD1;
            border-radius: 999px;
        }
        .thin-scroll::-webkit-scrollbar-thumb:hover {
            background-color: #9AA2AA;
        }
    </style>
</head>

<body class="bg-[#F5F6F7] font-['Inter']">

    <?php include ROOT_PATH . "/controlpanel/navigation/top.php"; ?>

    <div id="mainContent" class="transition-all duration-300 ease-in-out md:pl-64 pt-6 pb-24 md:pb-10 px-4 md:px-8">
        <div class="max-w-5xl mx-auto">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
                <div>
                    <p class="font-['Barlow_Condensed'] font-semibold text-[13px] tracking-[0.16em] uppercase text-[#A9822C] mb-1">
                        Human Resources
                    </p>
                    <h1 class="font-['Barlow_Condensed'] font-bold text-[28px] uppercase text-[#0B2540] leading-none">
                        Employee 201 Files
                    </h1>
                    <p id="employeeCount" class="text-[13px] text-[#6B7785] mt-1.5">
                        <?= count($employees) ?> employee<?= count($employees) === 1 ? '' : 's' ?>
                    </p>
                </div>

                <!-- Search -->
                <div class="relative w-full sm:w-80">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[#9AA2AA]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="employeeSearchInput" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Search by name or username"
                        autocomplete="off"
                        class="w-full bg-white border border-[#D8DBDE] rounded-lg pl-9.5 pr-9 py-2.5 text-[14px] text-[#1B2733] placeholder:text-[#9AA2AA] outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors shadow-sm">
                    <svg id="employeeSearchSpinner" class="hidden absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[#A9822C] animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </div>
            </div>

            <!-- Desktop table (scrollable) -->
            <div class="hidden md:block bg-white border border-black/5 rounded-xl overflow-hidden shadow-sm">
                <div class="max-h-[65vh] overflow-y-auto thin-scroll">
                    <table class="w-full text-sm border-collapse">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-[#F9FAFB] text-left text-[11px] font-semibold tracking-[0.08em] uppercase text-[#6B7785] border-b border-[#E8EAEC]">
                                <th class="px-5 py-3.5">Employee</th>
                                <th class="px-5 py-3.5">Username</th>
                                <th class="px-5 py-3.5">201 File Status</th>
                                <th class="px-5 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="employeeTableBody">
                            <?php if (empty($employees)): ?>
                                <tr id="employeeEmptyRow">
                                    <td colspan="4" class="px-5 py-14 text-center">
                                        <div class="flex flex-col items-center gap-2 text-[#9AA2AA]">
                                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                            </svg>
                                            <p class="text-[14px] font-medium">No employees found</p>
                                            <p class="text-[13px]">Try a different name or username.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($employees as $emp): ?>
                                <tr class="border-t border-[#E8EAEC] hover:bg-[#F9FAFB] transition-colors">
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 shrink-0 rounded-full bg-[#0B2540]/5 text-[#0B2540] text-[12px] font-semibold flex items-center justify-center">
                                                <?= htmlspecialchars(initials($emp['first_name'], $emp['last_name'])) ?>
                                            </div>
                                            <span class="font-medium text-[#1B2733]">
                                                <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-[#6B7785]">
                                        <?= htmlspecialchars($emp['username']) ?>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <?php if (!empty($hasInfo[(int) $emp['id']])): ?>
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-green-700 bg-green-50 border border-green-200 rounded-full px-2.5 py-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> On file
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#9AA2AA] bg-[#F5F6F7] border border-[#E8EAEC] rounded-full px-2.5 py-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#C6CBD1]"></span> Not submitted
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="<?= BASE_URL ?>/view-information?id=<?= (int) $emp['id'] ?>&tab=employment"
                                                class="text-[12.5px] font-semibold text-[#0B2540] border border-[#D8DBDE] rounded-md px-2.5 py-1.5 hover:bg-[#0B2540] hover:text-white hover:border-[#0B2540] transition-colors">
                                                <?= !empty($hasInfo[(int) $emp['id']]) ? 'Employment Details' : 'Add Details' ?>
                                            </a>
                                            <a href="<?= BASE_URL ?>/hr-employees?id=<?= (int) $emp['id'] ?>"
                                                class="text-[12.5px] font-semibold text-[#0B2540] border border-[#D8DBDE] rounded-md px-2.5 py-1.5 hover:bg-[#0B2540] hover:text-white hover:border-[#0B2540] transition-colors">
                                                201 File
                                            </a>
                                            <a href="<?= BASE_URL ?>/hr-orientation?id=<?= (int) $emp['id'] ?>"
                                                class="text-[12.5px] font-semibold text-[#0B2540] border border-[#D8DBDE] rounded-md px-2.5 py-1.5 hover:bg-[#0B2540] hover:text-white hover:border-[#0B2540] transition-colors">
                                                Orientation
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile cards (scrollable) -->
            <div id="employeeCardList" class="md:hidden flex flex-col gap-3 max-h-[70vh] overflow-y-auto pr-1 thin-scroll">
                <?php if (empty($employees)): ?>
                    <div class="bg-white border border-black/5 rounded-xl px-5 py-10 text-center text-[#9AA2AA] shadow-sm">
                        No employees found.
                    </div>
                <?php endif; ?>

                <?php foreach ($employees as $emp): ?>
                    <div class="bg-white border border-black/5 rounded-xl p-4 shadow-sm">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-9 h-9 shrink-0 rounded-full bg-[#0B2540]/5 text-[#0B2540] text-[13px] font-semibold flex items-center justify-center">
                                <?= htmlspecialchars(initials($emp['first_name'], $emp['last_name'])) ?>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-[#1B2733] truncate">
                                    <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                </p>
                                <p class="text-[13px] text-[#6B7785] truncate"><?= htmlspecialchars($emp['username']) ?></p>
                            </div>
                            <div class="ml-auto shrink-0">
                                <?php if (!empty($hasInfo[(int) $emp['id']])): ?>
                                    <span class="text-[11px] font-semibold text-green-700 bg-green-50 border border-green-200 rounded-full px-2.5 py-1">On file</span>
                                <?php else: ?>
                                    <span class="text-[11px] font-semibold text-[#9AA2AA] bg-[#F5F6F7] border border-[#E8EAEC] rounded-full px-2.5 py-1">Not submitted</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="<?= BASE_URL ?>/view-information?id=<?= (int) $emp['id'] ?>&tab=employment"
                                class="text-[12.5px] font-semibold text-[#0B2540] border border-[#D8DBDE] rounded-md px-2.5 py-1.5">
                                <?= !empty($hasInfo[(int) $emp['id']]) ? 'Employment Details' : 'Add Details' ?>
                            </a>
                            <a href="<?= BASE_URL ?>/hr-employees?id=<?= (int) $emp['id'] ?>"
                                class="text-[12.5px] font-semibold text-[#0B2540] border border-[#D8DBDE] rounded-md px-2.5 py-1.5">
                                201 File
                            </a>
                            <a href="<?= BASE_URL ?>/hr-orientation?id=<?= (int) $emp['id'] ?>"
                                class="text-[12.5px] font-semibold text-[#0B2540] border border-[#D8DBDE] rounded-md px-2.5 py-1.5">
                                Orientation
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <script>
        (function () {
            const input = document.getElementById('employeeSearchInput');
            const tbody = document.getElementById('employeeTableBody');
            const cardList = document.getElementById('employeeCardList');
            const countLabel = document.getElementById('employeeCount');
            const spinner = document.getElementById('employeeSearchSpinner');
            const baseUrl = <?= json_encode(BASE_URL) ?>;
            let debounceTimer = null;
            let activeController = null;

            function escapeHtml(str) {
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            function initials(name) {
                return name.split(' ').filter(Boolean).map(p => p[0]).join('').slice(0, 2).toUpperCase();
            }

            function emptyState() {
                return `<div class="flex flex-col items-center gap-2 text-[#9AA2AA]">
                            <p class="text-[14px] font-medium">No employees found</p>
                            <p class="text-[13px]">Try a different name or username.</p>
                        </div>`;
            }

            function statusBadge(onFile) {
                return onFile
                    ? `<span class="inline-flex items-center gap-1 text-[11px] font-semibold text-green-700 bg-green-50 border border-green-200 rounded-full px-2.5 py-1"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> On file</span>`
                    : `<span class="inline-flex items-center gap-1 text-[11px] font-semibold text-[#9AA2AA] bg-[#F5F6F7] border border-[#E8EAEC] rounded-full px-2.5 py-1"><span class="w-1.5 h-1.5 rounded-full bg-[#C6CBD1]"></span> Not submitted</span>`;
            }

            function actionButtons(emp, forCard) {
                const employmentUrl = emp.employment_url || `${emp.view_url}&tab=employment`;
                const employmentLabel = emp.on_file ? 'Employment Details' : 'Add Details';
                const btnClass = forCard
                    ? 'text-[12.5px] font-semibold text-[#0B2540] border border-[#D8DBDE] rounded-md px-2.5 py-1.5'
                    : 'text-[12.5px] font-semibold text-[#0B2540] border border-[#D8DBDE] rounded-md px-2.5 py-1.5 hover:bg-[#0B2540] hover:text-white hover:border-[#0B2540] transition-colors';
                return `
                    <a href="${employmentUrl}" class="${btnClass}">${employmentLabel}</a>
                    <a href="${emp.view_url}" class="${btnClass}">201 File</a>
                    <a href="${baseUrl}/hr-orientation?id=${emp.id}" class="${btnClass}">Orientation</a>`;
            }

            function renderRows(employees) {
                if (!employees.length) {
                    tbody.innerHTML = `<tr><td colspan="4" class="px-5 py-14 text-center">${emptyState()}</td></tr>`;
                    cardList.innerHTML = `<div class="bg-white border border-black/5 rounded-xl px-5 py-10 text-center text-[#9AA2AA] shadow-sm">No employees found.</div>`;
                    return;
                }

                tbody.innerHTML = employees.map(emp => `
                    <tr class="border-t border-[#E8EAEC] hover:bg-[#F9FAFB] transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 shrink-0 rounded-full bg-[#0B2540]/5 text-[#0B2540] text-[12px] font-semibold flex items-center justify-center">
                                    ${escapeHtml(initials(emp.name))}
                                </div>
                                <span class="font-medium text-[#1B2733]">${escapeHtml(emp.name)}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-[#6B7785]">${escapeHtml(emp.username)}</td>
                        <td class="px-5 py-3.5">${statusBadge(emp.on_file)}</td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center justify-end gap-2">${actionButtons(emp, false)}</div>
                        </td>
                    </tr>`).join('');

                cardList.innerHTML = employees.map(emp => `
                    <div class="bg-white border border-black/5 rounded-xl p-4 shadow-sm">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-9 h-9 shrink-0 rounded-full bg-[#0B2540]/5 text-[#0B2540] text-[13px] font-semibold flex items-center justify-center">
                                ${escapeHtml(initials(emp.name))}
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-[#1B2733] truncate">${escapeHtml(emp.name)}</p>
                                <p class="text-[13px] text-[#6B7785] truncate">${escapeHtml(emp.username)}</p>
                            </div>
                            <div class="ml-auto shrink-0">${statusBadge(emp.on_file)}</div>
                        </div>
                        <div class="flex flex-wrap gap-2">${actionButtons(emp, true)}</div>
                    </div>`).join('');
            }

            async function fetchEmployees(query) {
                if (activeController) activeController.abort();
                activeController = new AbortController();

                spinner.classList.remove('hidden');
                try {
                    const res = await fetch(`${baseUrl}/employee_search?q=${encodeURIComponent(query)}`, {
                        signal: activeController.signal,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) throw new Error('Request failed');
                    const data = await res.json();
                    renderRows(data.employees);
                    countLabel.textContent = `${data.employees.length} employee${data.employees.length === 1 ? '' : 's'}`;

                    // Keep URL shareable/bookmarkable without full reload
                    const newUrl = query
                        ? `${window.location.pathname}?q=${encodeURIComponent(query)}`
                        : window.location.pathname;
                    window.history.replaceState({}, '', newUrl);
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        console.error('Employee search failed:', err);
                    }
                } finally {
                    spinner.classList.add('hidden');
                }
            }

            input.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => fetchEmployees(input.value.trim()), 300);
            });

            // Auto-refresh "On file" statuses every 15s without user typing
            setInterval(() => fetchEmployees(input.value.trim()), 15000);
        })();
    </script>

</body>

</html>
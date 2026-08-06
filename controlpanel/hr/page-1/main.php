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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee 201 Files</title>
    <?php include ROOT_PATH . '/link/top.php'; ?>
</head>

<body class="bg-[#F5F6F7] font-['Inter']">

    <?php include ROOT_PATH . "/controlpanel/navigation/top.php"; ?>

    <div id="mainContent" class="transition-all duration-300 ease-in-out md:pl-64 pt-6 pb-24 md:pb-10 px-4 md:px-8">
        <div class="max-w-4xl mx-auto">

            <p class="font-['Barlow_Condensed'] font-semibold text-[13px] tracking-[0.16em] uppercase text-[#A9822C] mb-1">
                Human Resources
            </p>
            <h1 class="font-['Barlow_Condensed'] font-bold text-[26px] uppercase text-[#0B2540] mb-6">
                Employee 201 Files
            </h1>

            <div class="mb-5 relative w-full sm:w-80">
                <input type="text" id="employeeSearchInput" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search by name or username"
                    autocomplete="off"
                    class="w-full bg-white border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[15px] outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors">
                <span id="employeeSearchSpinner" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-[#9AA2AA] text-xs">…</span>
            </div>

            <div class="bg-white border border-black/5 rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-[#F5F6F7] text-left text-[11px] font-semibold tracking-[0.08em] uppercase text-[#6B7785]">
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Username</th>
                            <th class="px-4 py-3">201 File Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody id="employeeTableBody">
                        <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-[#9AA2AA]">No employees found.</td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($employees as $emp): ?>
                            <tr class="border-t border-[#E8EAEC] hover:bg-[#F5F6F7]/60">
                                <td class="px-4 py-3 font-medium text-[#1B2733]">
                                    <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                </td>
                                <td class="px-4 py-3 text-[#6B7785]">
                                    <?= htmlspecialchars($emp['username']) ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if (!empty($hasInfo[(int) $emp['id']])): ?>
                                        <span class="text-[11px] font-semibold text-green-700 bg-green-50 border border-green-200 rounded-full px-2.5 py-1">On file</span>
                                    <?php else: ?>
                                        <span class="text-[11px] font-semibold text-[#9AA2AA] bg-[#F5F6F7] border border-[#E8EAEC] rounded-full px-2.5 py-1">Not submitted</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="<?= BASE_URL ?>/hr-employees?id=<?= (int) $emp['id'] ?>"
                                        class="text-[#0B2540] font-semibold text-[13px] hover:underline">
                                        View 201 File →
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script>
        (function () {
            const input = document.getElementById('employeeSearchInput');
            const tbody = document.getElementById('employeeTableBody');
            const spinner = document.getElementById('employeeSearchSpinner');
            const baseUrl = <?= json_encode(BASE_URL) ?>;
            let debounceTimer = null;
            let activeController = null;

            function escapeHtml(str) {
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            function renderRows(employees) {
                if (!employees.length) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-[#9AA2AA]">No employees found.</td>
                        </tr>`;
                    return;
                }

                tbody.innerHTML = employees.map(emp => {
                    const statusBadge = emp.on_file
                        ? `<span class="text-[11px] font-semibold text-green-700 bg-green-50 border border-green-200 rounded-full px-2.5 py-1">On file</span>`
                        : `<span class="text-[11px] font-semibold text-[#9AA2AA] bg-[#F5F6F7] border border-[#E8EAEC] rounded-full px-2.5 py-1">Not submitted</span>`;

                    return `
                        <tr class="border-t border-[#E8EAEC] hover:bg-[#F5F6F7]/60">
                            <td class="px-4 py-3 font-medium text-[#1B2733]">${escapeHtml(emp.name)}</td>
                            <td class="px-4 py-3 text-[#6B7785]">${escapeHtml(emp.username)}</td>
                            <td class="px-4 py-3">${statusBadge}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="${emp.view_url}" class="text-[#0B2540] font-semibold text-[13px] hover:underline">
                                    View 201 File →
                                </a>
                            </td>
                        </tr>`;
                }).join('');
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

            // Optional: auto-refresh "On file" statuses every 15s without user typing
            setInterval(() => fetchEmployees(input.value.trim()), 15000);
        })();
    </script>

</body>

</html>
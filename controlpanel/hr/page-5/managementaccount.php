<?php
//managementaccount.php
include ROOT_PATH . "/network/connect.php";
include ROOT_PATH . "/controlpanel/auth/role/auth_guard.php";

requireAccess('hr', 'head');

// Fetch all employee accounts for listing
$accounts = [];
$listStmt = $conn->prepare("SELECT id, first_name, last_name, username FROM nobleuserlist ORDER BY last_name ASC, first_name ASC");
$listStmt->execute();
$result = $listStmt->get_result();
while ($row = $result->fetch_assoc()) {
    $accounts[] = $row;
}
$listStmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Account Management</title>
    <?php include ROOT_PATH . '/link/top.php'; ?>
    <style>
        /* ==== SCROLLABLE TABLE CUSTOM SCROLLBAR ==== */
        .table-scroll {
            max-height: 60vh;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(0, 0, 0, 0.15) transparent;
        }

        .table-scroll::-webkit-scrollbar {
            width: 8px;
        }

        .table-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .table-scroll::-webkit-scrollbar-thumb {
            background-color: rgba(0, 0, 0, 0.15);
            border-radius: 9999px;
        }

        .table-scroll::-webkit-scrollbar-thumb:hover {
            background-color: rgba(0, 0, 0, 0.28);
        }

        /* Keep header pinned while the body scrolls */
        .table-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 10;
        }
    </style>
</head>

<body class="bg-[#F5F6F7] font-['Inter']">

    <?php include ROOT_PATH . "/controlpanel/navigation/top.php"; ?>

    <div id="mainContent" class="transition-all duration-300 ease-in-out md:pl-64 pt-6 pb-24 md:pb-10 px-4 md:px-8">

        <div class="max-w-5xl mx-auto">

            <div class="flex items-center justify-between mb-5">
                <div>
                    <h4 class="text-lg font-semibold text-black/90">Employee Account Management</h4>
                    <p class="text-xs text-black/40">Noblehome Construction · HR Control Panel</p>
                </div>
            </div>

            <div id="pageAlertBox" class="hidden mb-4 px-4 py-2.5 rounded-lg border text-sm"></div>

            <!-- ==== FILTER BAR ==== -->
            <div class="bg-white border border-black/5 rounded-xl shadow-md p-4 mb-4">
                <div class="flex flex-col md:flex-row gap-3 md:items-end">

                    <div class="flex-1">
                        <label class="block text-xs font-medium text-black/50 mb-1.5">Search</label>
                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-black/30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" id="filterSearch" placeholder="Search by name or username..."
                                class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-black/10 text-sm text-black/90
                                       focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black/20 transition-all">
                        </div>
                    </div>

                    <div class="w-full md:w-48">
                        <label class="block text-xs font-medium text-black/50 mb-1.5">Sort by</label>
                        <select id="filterSort"
                            class="w-full px-3 py-2.5 rounded-lg border border-black/10 text-sm text-black/90 bg-white
                                   focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black/20 transition-all">
                            <option value="last_asc">Last Name (A-Z)</option>
                            <option value="last_desc">Last Name (Z-A)</option>
                            <option value="first_asc">First Name (A-Z)</option>
                            <option value="first_desc">First Name (Z-A)</option>
                            <option value="username_asc">Username (A-Z)</option>
                            <option value="username_desc">Username (Z-A)</option>
                        </select>
                    </div>

                    <div>
                        <button type="button" id="clearFilterBtn"
                            class="w-full md:w-auto px-4 py-2.5 rounded-lg border border-black/10 text-sm font-medium text-black/60 hover:bg-black/5 transition-colors">
                            Clear
                        </button>
                    </div>

                </div>
                <p id="filterCount" class="text-xs text-black/30 mt-2.5"></p>
            </div>

            <div class="bg-white border border-black/5 rounded-xl shadow-md overflow-hidden">
                <div class="table-scroll overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b border-black/5 text-left text-xs uppercase tracking-wide text-black/40">
                                <th class="px-5 py-3 font-medium bg-gray-50">Name</th>
                                <th class="px-5 py-3 font-medium bg-gray-50">Username</th>
                                <th class="px-5 py-3 font-medium text-right bg-gray-50">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="accountsTableBody" class="divide-y divide-black/5">
                            <?php if (empty($accounts)): ?>
                                <tr id="emptyRow">
                                    <td colspan="3" class="px-5 py-6 text-center text-black/40">No employee accounts found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($accounts as $acc): ?>
                                    <tr data-row-id="<?= (int)$acc['id'] ?>"
                                        data-first="<?= htmlspecialchars(strtolower($acc['first_name']), ENT_QUOTES) ?>"
                                        data-last="<?= htmlspecialchars(strtolower($acc['last_name']), ENT_QUOTES) ?>"
                                        data-username="<?= htmlspecialchars(strtolower($acc['username']), ENT_QUOTES) ?>"
                                        class="hover:bg-black/[0.02] transition-colors">
                                        <td class="px-5 py-3 text-black/80 row-name">
                                            <?= htmlspecialchars($acc['first_name'] . ' ' . $acc['last_name']) ?>
                                        </td>
                                        <td class="px-5 py-3 text-black/60 font-mono text-xs row-username"><?= htmlspecialchars($acc['username']) ?></td>
                                        <td class="px-5 py-3 text-right">
                                            <button type="button"
                                                class="editBtn inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-black/10 text-xs font-medium text-black/70 hover:bg-black/5 transition-colors"
                                                data-id="<?= (int)$acc['id'] ?>"
                                                data-first="<?= htmlspecialchars($acc['first_name'], ENT_QUOTES) ?>"
                                                data-last="<?= htmlspecialchars($acc['last_name'], ENT_QUOTES) ?>"
                                                data-username="<?= htmlspecialchars($acc['username'], ENT_QUOTES) ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                </svg>
                                                Edit
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div id="noResultsRow" class="hidden px-5 py-6 text-center text-black/40 text-sm">No matching accounts found.</div>
                </div>
            </div>

        </div>

    </div>

    <!-- ==== EDIT MODAL ==== -->
    <div id="editModalOverlay" class="hidden fixed inset-0 bg-black/40 z-40 flex items-center justify-center px-4">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6 relative">

            <button type="button" id="closeEditModal" class="absolute top-4 right-4 text-black/30 hover:text-black/60 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            <h4 class="text-base font-semibold text-black/90 mb-1">Edit Employee Account</h4>
            <p class="text-xs text-black/40 mb-5">Update the name, username, and optionally the password for this account.</p>

            <div id="modalAlertBox" class="hidden mb-4 px-4 py-2.5 rounded-lg border text-sm"></div>

            <form id="editForm" class="space-y-4">
                <input type="hidden" name="id" id="edit_id">

                <div>
                    <label class="block text-sm font-medium text-black/70 mb-1.5">First Name</label>
                    <input type="text" name="first_name" id="edit_first_name" required
                        class="w-full px-3 py-2.5 rounded-lg border border-black/10 text-sm text-black/90
                               focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black/20 transition-all">
                </div>

                <div>
                    <label class="block text-sm font-medium text-black/70 mb-1.5">Last Name</label>
                    <input type="text" name="last_name" id="edit_last_name" required
                        class="w-full px-3 py-2.5 rounded-lg border border-black/10 text-sm text-black/90
                               focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black/20 transition-all">
                </div>

                <div>
                    <label class="block text-sm font-medium text-black/70 mb-1.5">Username</label>
                    <input type="text" name="username" id="edit_username" required
                        class="w-full px-3 py-2.5 rounded-lg border border-black/10 text-sm text-black/90 font-mono
                               focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black/20 transition-all">
                    <p class="mt-1 text-xs text-black/40">Auto-suggested based on name, editable.</p>
                </div>

                <div class="pt-1 border-t border-black/5">
                    <label class="block text-sm font-medium text-black/70 mb-1.5 mt-3">
                        New Password <span class="text-black/30 font-normal">(optional)</span>
                    </label>
                    <input type="password" name="password" id="edit_password" autocomplete="new-password"
                        placeholder="Leave blank to keep current password"
                        class="w-full px-3 py-2.5 rounded-lg border border-black/10 text-sm text-black/90
                               focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black/20 transition-all">

                    <label class="block text-sm font-medium text-black/70 mb-1.5 mt-3">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="edit_confirm_password" autocomplete="new-password"
                        placeholder="Re-enter new password"
                        class="w-full px-3 py-2.5 rounded-lg border border-black/10 text-sm text-black/90
                               focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black/20 transition-all">

                    <p class="text-[11px] text-black/30 mt-1.5">Minimum 8 characters if changing the password.</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" id="cancelEditBtn"
                        class="px-4 py-2 rounded-lg text-sm font-medium text-black/60 hover:bg-black/5 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" id="saveEditBtn"
                        class="px-4 py-2 rounded-lg bg-black/90 hover:bg-black text-white text-sm font-medium
                               transition-all shadow-sm hover:shadow-md">
                        Save Changes
                    </button>
                </div>
            </form>

        </div>
    </div>

    <script>
        const editModalOverlay = document.getElementById('editModalOverlay');
        const editForm = document.getElementById('editForm');
        const modalAlertBox = document.getElementById('modalAlertBox');
        const pageAlertBox = document.getElementById('pageAlertBox');
        const saveEditBtn = document.getElementById('saveEditBtn');

        const editIdInput = document.getElementById('edit_id');
        const editFirstNameInput = document.getElementById('edit_first_name');
        const editLastNameInput = document.getElementById('edit_last_name');
        const editUsernameInput = document.getElementById('edit_username');
        const editPasswordInput = document.getElementById('edit_password');
        const editConfirmPasswordInput = document.getElementById('edit_confirm_password');

        // ==== AUTO-GENERATE USERNAME (EDIT MODAL) ====
        function generateEditUsername() {
            const first = editFirstNameInput.value.trim();
            const last  = editLastNameInput.value.trim();

            if (first && last) {
                const initial = first.charAt(0).toUpperCase();
                const lastClean = last.replace(/\s+/g, '').toUpperCase();
                editUsernameInput.value = 'NHCC' + initial + lastClean;
            }
        }

        editFirstNameInput.addEventListener('input', generateEditUsername);
        editLastNameInput.addEventListener('input', generateEditUsername);

        // ==== FILTER & SORT ====
        const filterSearch = document.getElementById('filterSearch');
        const filterSort = document.getElementById('filterSort');
        const clearFilterBtn = document.getElementById('clearFilterBtn');
        const filterCount = document.getElementById('filterCount');
        const noResultsRow = document.getElementById('noResultsRow');
        const accountsTableBody = document.getElementById('accountsTableBody');

        function getDataRows() {
            return Array.from(accountsTableBody.querySelectorAll('tr[data-row-id]'));
        }

        function applyFilterAndSort() {
            const rows = getDataRows();
            const query = filterSearch.value.trim().toLowerCase();
            const sortVal = filterSort.value;

            let visibleCount = 0;

            rows.forEach(row => {
                const first = row.dataset.first || '';
                const last = row.dataset.last || '';
                const username = row.dataset.username || '';
                const fullName = `${first} ${last}`;

                const matches = query === '' ||
                    fullName.includes(query) ||
                    username.includes(query);

                row.classList.toggle('hidden', !matches);
                if (matches) visibleCount++;
            });

            // Sort visible + hidden rows together so hidden state is preserved after reorder
            const sortedRows = rows.slice().sort((a, b) => {
                let key;
                let dir = 1;

                switch (sortVal) {
                    case 'last_desc': key = 'last'; dir = -1; break;
                    case 'first_asc': key = 'first'; dir = 1; break;
                    case 'first_desc': key = 'first'; dir = -1; break;
                    case 'username_asc': key = 'username'; dir = 1; break;
                    case 'username_desc': key = 'username'; dir = -1; break;
                    default: key = 'last'; dir = 1; break; // last_asc
                }

                const aVal = a.dataset[key] || '';
                const bVal = b.dataset[key] || '';
                return aVal.localeCompare(bVal) * dir;
            });

            sortedRows.forEach(row => accountsTableBody.appendChild(row));

            // Empty-state handling
            if (rows.length === 0) {
                noResultsRow.classList.add('hidden');
            } else if (visibleCount === 0) {
                noResultsRow.classList.remove('hidden');
            } else {
                noResultsRow.classList.add('hidden');
            }

            filterCount.textContent = query === ''
                ? `Showing ${visibleCount} of ${rows.length} account${rows.length === 1 ? '' : 's'}`
                : `Showing ${visibleCount} of ${rows.length} account${rows.length === 1 ? '' : 's'} matching "${filterSearch.value.trim()}"`;
        }

        filterSearch.addEventListener('input', applyFilterAndSort);
        filterSort.addEventListener('change', applyFilterAndSort);
        clearFilterBtn.addEventListener('click', function () {
            filterSearch.value = '';
            filterSort.value = 'last_asc';
            applyFilterAndSort();
        });

        // Run once on load so counts/sort are correct from the start
        applyFilterAndSort();

        function showAlert(box, message, isSuccess) {
            box.textContent = message;
            box.classList.remove('hidden', 'bg-red-50', 'border-red-200', 'text-red-600', 'bg-green-50', 'border-green-200', 'text-green-600');
            box.classList.add(isSuccess ? 'bg-green-50' : 'bg-red-50', isSuccess ? 'border-green-200' : 'border-red-200', isSuccess ? 'text-green-600' : 'text-red-600');
        }

        function openEditModal(id, first, last, username) {
            editIdInput.value = id;
            editFirstNameInput.value = first;
            editLastNameInput.value = last;
            editUsernameInput.value = username;
            editPasswordInput.value = '';
            editConfirmPasswordInput.value = '';
            modalAlertBox.classList.add('hidden');
            editModalOverlay.classList.remove('hidden');
        }

        function closeEditModal() {
            editModalOverlay.classList.add('hidden');
            editForm.reset();
        }

        document.getElementById('accountsTableBody').addEventListener('click', function (e) {
            const btn = e.target.closest('.editBtn');
            if (!btn) return;
            openEditModal(btn.dataset.id, btn.dataset.first, btn.dataset.last, btn.dataset.username);
        });

        document.getElementById('closeEditModal').addEventListener('click', closeEditModal);
        document.getElementById('cancelEditBtn').addEventListener('click', closeEditModal);
        editModalOverlay.addEventListener('click', function (e) {
            if (e.target === editModalOverlay) closeEditModal();
        });

        editForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            // Client-side password checks (server re-validates too)
            const pw = editPasswordInput.value;
            const confirmPw = editConfirmPasswordInput.value;

            if (pw !== '' || confirmPw !== '') {
                if (pw.length < 8) {
                    showAlert(modalAlertBox, 'Password must be at least 8 characters.', false);
                    return;
                }
                if (pw !== confirmPw) {
                    showAlert(modalAlertBox, 'Passwords do not match.', false);
                    return;
                }
            }

            saveEditBtn.disabled = true;
            saveEditBtn.textContent = 'Saving...';

            try {
                const formData = new FormData(editForm);
                const res = await fetch('<?= BASE_URL ?>/accountupdate-process', {
                    method: 'POST',
                    body: formData
                });

                const data = await res.json();

                if (data.success) {
                    // Update the row in the table without a full page reload
                    const row = document.querySelector(`tr[data-row-id="${editIdInput.value}"]`);
                    if (row) {
                        row.querySelector('.row-name').textContent = `${editFirstNameInput.value} ${editLastNameInput.value}`;
                        row.querySelector('.row-username').textContent = editUsernameInput.value;
                        // Keep the row's filter/sort dataset in sync
                        row.dataset.first = editFirstNameInput.value.toLowerCase();
                        row.dataset.last = editLastNameInput.value.toLowerCase();
                        row.dataset.username = editUsernameInput.value.toLowerCase();
                    }
                    // Keep the edit button's data attributes in sync
                    const btn = row ? row.querySelector('.editBtn') : null;
                    if (btn) {
                        btn.dataset.first = editFirstNameInput.value;
                        btn.dataset.last = editLastNameInput.value;
                        btn.dataset.username = editUsernameInput.value;
                    }

                    closeEditModal();
                    showAlert(pageAlertBox, data.message, true);
                    setTimeout(() => pageAlertBox.classList.add('hidden'), 4000);
                    applyFilterAndSort();
                } else {
                    showAlert(modalAlertBox, data.message, false);
                }
            } catch (err) {
                showAlert(modalAlertBox, 'Error connecting to the server.', false);
            } finally {
                saveEditBtn.disabled = false;
                saveEditBtn.textContent = 'Save Changes';
            }
        });
    </script>

</body>

</html>
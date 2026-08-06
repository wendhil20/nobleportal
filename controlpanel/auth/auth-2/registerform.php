<?php
include ROOT_PATH . '/network/connect.php';
include ROOT_PATH . "/controlpanel/auth/role/auth_guard.php";

requireAccess('hr', 'head');


$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $position = trim($_POST['position']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($name === '' || $email === '' || $role === '' || $password === '') {
        $message = "Please fill in all required fields.";
        $msgType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address.";
        $msgType = 'error';
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $msgType = 'error';
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
        $msgType = 'error';
    } else {
        $check = $conn->prepare("SELECT id FROM nobleadminlist WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Email is already registered.";
            $msgType = 'error';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO nobleadminlist (name, email, role, password, position) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $role, $hashedPassword, $position);

            if ($stmt->execute()) {
                $message = "Registration successful!";
                $msgType = 'success';
            } else {
                $message = "Something went wrong. Please try again.";
                $msgType = 'error';
            }
            $stmt->close();
        }
        $check->close();
    }
}

$roles = $conn->query("SELECT role_name FROM noble_roles ORDER BY role_name ASC");
$positions = $conn->query("SELECT position_name FROM noble_positions ORDER BY position_name ASC");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <?php include ROOT_PATH . '/link/top.php'; ?>
    <?php include ROOT_PATH . '/controlpanel/navigation/top.php'; ?>
</head>

<body>

    <body class="bg-slate-100">

        <div id="mainContent" class="min-h-screen flex items-center justify-center p-4">
            <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8 relative">

                <!-- Settings gear button -->
                <button onclick="openSettings()"
                    class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 hover:rotate-45 transition-all duration-300"
                    title="Manage Roles & Positions">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>

                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-slate-800">Admin Registration</h2>
                    <p class="text-sm text-slate-500 mt-1">Create a new administrator account</p>
                </div>

                <?php if ($message): ?>
                    <div
                        class="mb-4 px-4 py-3 rounded-lg text-sm text-center
            <?= $msgType === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Full Name *</label>
                        <input type="text" name="name" required
                            value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
                            placeholder="Juan Dela Cruz"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-transparent transition">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email *</label>
                        <input type="email" name="email" required
                            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                            placeholder="juan@company.com"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-transparent transition">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Role *</label>
                            <select name="role" id="roleSelect" required
                                class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-transparent transition">
                                <option value="">Select</option>
                                <?php while ($r = $roles->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($r['role_name']) ?>">
                                        <?= htmlspecialchars($r['role_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Position</label>
                            <select name="position" id="positionSelect"
                                class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-transparent transition">
                                <option value="">Select</option>
                                <?php while ($p = $positions->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($p['position_name']) ?>">
                                        <?= htmlspecialchars($p['position_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Password *</label>
                        <input type="password" name="password" required placeholder="••••••••"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-transparent transition">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Confirm Password *</label>
                        <input type="password" name="confirm_password" required placeholder="••••••••"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-transparent transition">
                    </div>

                    <button type="submit" name="register"
                        class="w-full bg-slate-800 hover:bg-slate-900 active:scale-[0.98] text-white font-medium py-2.5 rounded-lg transition-all shadow-sm">
                        Register
                    </button>
                </form>
            </div>

            <!-- ===================== SETTINGS MODAL ===================== -->
            <div id="settingsModal"
                class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
                <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl p-6 relative max-h-[85vh] flex flex-col">
                    <button onclick="closeSettings()"
                        class="absolute top-4 right-4 text-slate-400 hover:text-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <h3 class="text-lg font-bold text-slate-800 mb-4">Manage Roles & Positions</h3>

                    <!-- Tabs -->
                    <div class="flex border-b border-slate-200 mb-4">
                        <button onclick="switchTab('roles')" id="tabBtnRoles"
                            class="px-4 py-2 text-sm font-medium border-b-2 border-slate-800 text-slate-800 transition">Roles</button>
                        <button onclick="switchTab('positions')" id="tabBtnPositions"
                            class="px-4 py-2 text-sm font-medium border-b-2 border-transparent text-slate-400 transition">Positions</button>
                    </div>

                    <div id="settingsMsg" class="hidden mb-3 px-3 py-2 rounded-lg text-sm"></div>

                    <!-- Roles Tab -->
                    <div id="tabRoles" class="flex-1 overflow-y-auto">
                        <form onsubmit="addItem(event,'role')" class="flex gap-2 mb-4">
                            <input type="text" id="newRoleName" placeholder="New role name..." required
                                class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-slate-800">
                            <button type="submit"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 rounded-lg transition">Add</button>
                        </form>
                        <ul id="roleList" class="space-y-2 text-sm"></ul>
                    </div>

                    <!-- Positions Tab -->
                    <div id="tabPositions" class="flex-1 overflow-y-auto hidden">
                        <form onsubmit="addItem(event,'position')" class="flex gap-2 mb-4">
                            <input type="text" id="newPositionName" placeholder="New position name..." required
                                class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-slate-800">
                            <button type="submit"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 rounded-lg transition">Add</button>
                        </form>
                        <ul id="positionList" class="space-y-2 text-sm"></ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- JS section - walang binago dito, pareho pa rin -->

        <script>
            const HANDLER = '<?= BASE_URL ?>/handler';

            function openSettings() {
                document.getElementById('settingsModal').classList.remove('hidden');
                document.getElementById('settingsModal').classList.add('flex');
                loadList('role');
                loadList('position');
            }
            function closeSettings() {
                document.getElementById('settingsModal').classList.add('hidden');
                document.getElementById('settingsModal').classList.remove('flex');
                refreshDropdowns();
            }
            function switchTab(tab) {
                const isRoles = tab === 'roles';
                document.getElementById('tabRoles').classList.toggle('hidden', !isRoles);
                document.getElementById('tabPositions').classList.toggle('hidden', isRoles);
                document.getElementById('tabBtnRoles').classList.toggle('border-slate-800', isRoles);
                document.getElementById('tabBtnRoles').classList.toggle('text-slate-800', isRoles);
                document.getElementById('tabBtnRoles').classList.toggle('border-transparent', !isRoles);
                document.getElementById('tabBtnRoles').classList.toggle('text-slate-400', !isRoles);
                document.getElementById('tabBtnPositions').classList.toggle('border-slate-800', !isRoles);
                document.getElementById('tabBtnPositions').classList.toggle('text-slate-800', !isRoles);
                document.getElementById('tabBtnPositions').classList.toggle('border-transparent', isRoles);
                document.getElementById('tabBtnPositions').classList.toggle('text-slate-400', isRoles);
            }

            function showMsg(text, ok) {
                const el = document.getElementById('settingsMsg');
                el.textContent = text;
                el.className = 'mb-3 px-3 py-2 rounded-lg text-sm ' + (ok ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200');
                el.classList.remove('hidden');
                setTimeout(() => el.classList.add('hidden'), 2500);
            }

            async function loadList(type) {
                const res = await fetch(`${HANDLER}?action=list_${type}s`);
                const json = await res.json();
                const listEl = document.getElementById(type + 'List');
                listEl.innerHTML = '';
                const field = type + '_name';

                json.data.forEach(item => {
                    const li = document.createElement('li');
                    li.className = 'flex items-center justify-between bg-slate-50 border border-slate-200 rounded-lg px-3 py-2';
                    li.innerHTML = `
            <span class="editable-text flex-1" data-id="${item.id}">${item[field]}</span>
            <div class="flex gap-2 ml-2">
                <button class="text-blue-600 hover:text-blue-800 text-xs font-medium" onclick="startEdit('${type}', ${item.id}, this)">Edit</button>
                <button class="text-red-600 hover:text-red-800 text-xs font-medium" onclick="deleteItem('${type}', ${item.id})">Delete</button>
            </div>`;
                    listEl.appendChild(li);
                });
            }

            async function addItem(e, type) {
                e.preventDefault();
                const input = document.getElementById('new' + capitalize(type) + 'Name');
                const value = input.value.trim();
                if (!value) return;

                const body = new URLSearchParams({ action: `add_${type}`, [`${type}_name`]: value });
                const res = await fetch(HANDLER, { method: 'POST', body });
                const json = await res.json();
                showMsg(json.message, json.success);
                if (json.success) {
                    input.value = '';
                    loadList(type);
                }
            }

            async function deleteItem(type, id) {
                if (!confirm(`Delete this ${type}?`)) return;
                const body = new URLSearchParams({ action: `delete_${type}`, id });
                const res = await fetch(HANDLER, { method: 'POST', body });
                const json = await res.json();
                showMsg(json.message, json.success);
                if (json.success) loadList(type);
            }

            function startEdit(type, id, btn) {
                const li = btn.closest('li');
                const span = li.querySelector('.editable-text');
                const currentVal = span.textContent;
                span.outerHTML = `<input type="text" class="flex-1 px-2 py-1 border border-slate-300 rounded text-sm" id="editInput-${type}-${id}" value="${currentVal}">`;
                btn.parentElement.innerHTML = `
        <button class="text-emerald-600 hover:text-emerald-800 text-xs font-medium" onclick="saveEdit('${type}', ${id})">Save</button>
        <button class="text-slate-500 hover:text-slate-700 text-xs font-medium" onclick="loadList('${type}')">Cancel</button>`;
            }

            async function saveEdit(type, id) {
                const input = document.getElementById(`editInput-${type}-${id}`);
                const value = input.value.trim();
                if (!value) return;
                const body = new URLSearchParams({ action: `edit_${type}`, id, [`${type}_name`]: value });
                const res = await fetch(HANDLER, { method: 'POST', body });
                const json = await res.json();
                showMsg(json.message, json.success);
                loadList(type);
            }

            async function refreshDropdowns() {
                const roleRes = await fetch(`${HANDLER}?action=list_roles`);
                const roleJson = await roleRes.json();
                const roleSelect = document.getElementById('roleSelect');
                const currentRole = roleSelect.value;
                roleSelect.innerHTML = '<option value="">-- Select Role --</option>' +
                    roleJson.data.map(r => `<option value="${r.role_name}">${r.role_name}</option>`).join('');
                roleSelect.value = currentRole;

                const posRes = await fetch(`${HANDLER}?action=list_positions`);
                const posJson = await posRes.json();
                const posSelect = document.getElementById('positionSelect');
                const currentPos = posSelect.value;
                posSelect.innerHTML = '<option value="">-- Select Position --</option>' +
                    posJson.data.map(p => `<option value="${p.position_name}">${p.position_name}</option>`).join('');
                posSelect.value = currentPos;
            }

            function capitalize(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
        </script>

    </body>

</html>
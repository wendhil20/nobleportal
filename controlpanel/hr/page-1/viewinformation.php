<?php
//viewinformation.php
include ROOT_PATH . "/network/connect.php";
include ROOT_PATH . "/controlpanel/auth/role/auth_guard.php";

requireAccess('hr', 'head');

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: " . BASE_URL . "/hr-employees");
    exit;
}

// Fetch employee
$stmt = $conn->prepare("SELECT id, first_name, last_name, username, created_at FROM nobleuserlist WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$employee) {
    header("Location: " . BASE_URL . "/hr-employees");
    exit;
}

// Fetch existing employment info (if any)
$stmt = $conn->prepare(
    "SELECT ei.*, d.name AS department_name
     FROM nobleuser_employment_details ei
     LEFT JOIN departments d ON d.id = ei.department_id
     WHERE ei.user_id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch departments for the select
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$employmentTypes = [
    'trainee'        => 'Trainee',
    'probationary'   => 'Probationary',
    'regular'        => 'Regular',
    'project_based'  => 'Project Based / Contractual',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee 201 File</title>
    <?php include ROOT_PATH . '/link/top.php'; ?>
</head>

<body class="bg-[#EDEAE1] font-['Inter']">

    <?php include ROOT_PATH . "/controlpanel/navigation/top.php"; ?>

    <div id="mainContent" class="transition-all duration-300 ease-in-out md:pl-64 pt-6 pb-24 md:pb-10 px-4 md:px-8">
        <div class="max-w-3xl mx-auto">

               <a href="<?= BASE_URL ?>/hrpage-1" class="inline-flex items-center gap-1.5 text-[12.5px] text-[#6B6350] hover:text-[#0B2540] font-medium mb-4 tracking-[0.02em]">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                RETURN TO EMPLOYEE REGISTRY
            </a>

            <p class="font-['Barlow_Condensed'] font-semibold text-[13px] tracking-[0.16em] uppercase text-[#A9822C] mb-1">
                Human Resources
            </p>

            <h1 class="font-['Barlow_Condensed'] font-bold text-[15px] uppercase text-[#0B2540] mb-6">
                <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?>
            </h1>

            <!-- Employment Information Card -->
            <div class="bg-white border border-black/5 rounded-xl overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-[#E8EAEC]">
                    <h2 class="font-['Barlow_Condensed'] font-bold text-[18px] uppercase text-[#0B2540]">
                        Employment Details
                    </h2>
                    <button id="openEmploymentModalBtn"
                        class="bg-[#0B2540] text-white text-[13px] font-semibold px-4 py-2 rounded-md hover:bg-[#0B2540]/90 transition-colors">
                        <?= $info ? 'Edit Details' : 'Add Details' ?>
                    </button>
                </div>

                <div id="employmentDisplay" class="p-5">
                    <?php if ($info): ?>
                        <div class="grid sm:grid-cols-2 gap-5">
                            <div class="flex items-start gap-4 sm:col-span-2">
                                <?php if (!empty($info['picture'])): ?>
                                    <img src="<?= BASE_URL . '/' . htmlspecialchars($info['picture']) ?>"
                                        alt="Employee photo"
                                        class="w-20 h-20 rounded-lg object-cover border border-[#E8EAEC]">
                                <?php else: ?>
                                    <div class="w-20 h-20 rounded-lg bg-[#F5F6F7] border border-[#E8EAEC] flex items-center justify-center text-[#9AA2AA] text-[11px]">
                                        No photo
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold tracking-[0.08em] uppercase text-[#9AA2AA] mb-1">Department</p>
                                <p class="text-[15px] text-[#1B2733] font-medium"><?= htmlspecialchars($info['department_name'] ?? '—') ?></p>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold tracking-[0.08em] uppercase text-[#9AA2AA] mb-1">Employment Type</p>
                                <p class="text-[15px] text-[#1B2733] font-medium">
                                    <?= htmlspecialchars($employmentTypes[$info['employment_type']] ?? $info['employment_type']) ?>
                                </p>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold tracking-[0.08em] uppercase text-[#9AA2AA] mb-1">Salary</p>
                                <p class="text-[15px] text-[#1B2733] font-medium"><?= $info['salary'] !== null ? '₱' . number_format((float) $info['salary'], 2) : '—' ?></p>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold tracking-[0.08em] uppercase text-[#9AA2AA] mb-1">Daily Rate</p>
                                <p class="text-[15px] text-[#1B2733] font-medium"><?= $info['daily_rate'] !== null ? '₱' . number_format((float) $info['daily_rate'], 2) : '—' ?></p>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold tracking-[0.08em] uppercase text-[#9AA2AA] mb-1">Allowance</p>
                                <p class="text-[15px] text-[#1B2733] font-medium"><?= $info['allowance'] !== null ? '₱' . number_format((float) $info['allowance'], 2) : '—' ?></p>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold tracking-[0.08em] uppercase text-[#9AA2AA] mb-1">Email</p>
                                <p class="text-[15px] text-[#1B2733] font-medium"><?= htmlspecialchars($info['email'] ?: '—') ?></p>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold tracking-[0.08em] uppercase text-[#9AA2AA] mb-1">Contact Number</p>
                                <p class="text-[15px] text-[#1B2733] font-medium"><?= htmlspecialchars($info['contact_number'] ?: '—') ?></p>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold tracking-[0.08em] uppercase text-[#9AA2AA] mb-1">Emergency Contact</p>
                                <p class="text-[15px] text-[#1B2733] font-medium"><?= htmlspecialchars($info['emergency_contact_name'] ?: '—') ?></p>
                                <p class="text-[13px] text-[#6B7785]"><?= htmlspecialchars($info['emergency_contact_number'] ?: '') ?></p>
                            </div>

                            <div class="sm:col-span-2">
                                <p class="text-[11px] font-semibold tracking-[0.08em] uppercase text-[#9AA2AA] mb-1">Present Address</p>
                                <p class="text-[15px] text-[#1B2733] font-medium"><?= nl2br(htmlspecialchars($info['present_address'] ?: '—')) ?></p>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold tracking-[0.08em] uppercase text-[#9AA2AA] mb-1">SSS</p>
                                <p class="text-[15px] text-[#1B2733] font-medium"><?= htmlspecialchars($info['sss_number'] ?: '—') ?></p>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold tracking-[0.08em] uppercase text-[#9AA2AA] mb-1">PhilHealth</p>
                                <p class="text-[15px] text-[#1B2733] font-medium"><?= htmlspecialchars($info['philhealth_number'] ?: '—') ?></p>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold tracking-[0.08em] uppercase text-[#9AA2AA] mb-1">Pag-IBIG</p>
                                <p class="text-[15px] text-[#1B2733] font-medium"><?= htmlspecialchars($info['pagibig_number'] ?: '—') ?></p>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold tracking-[0.08em] uppercase text-[#9AA2AA] mb-1">TIN</p>
                                <p class="text-[15px] text-[#1B2733] font-medium"><?= htmlspecialchars($info['tin_number'] ?: '—') ?></p>
                            </div>

                            <div>
                                <p class="text-[11px] font-semibold tracking-[0.08em] uppercase text-[#9AA2AA] mb-1">Contract</p>
                                <?php if (!empty($info['contract_file'])): ?>
                                    <a href="<?= BASE_URL . '/' . htmlspecialchars($info['contract_file']) ?>" target="_blank"
                                        class="text-[13px] font-semibold text-[#0B2540] hover:underline">View PDF →</a>
                                <?php else: ?>
                                    <p class="text-[13px] text-[#9AA2AA]">No contract uploaded</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-[#9AA2AA] text-[14px]">No employment details on file yet.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- ===================== Employment Details Modal ===================== -->
    <div id="employmentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
        <div id="employmentModalOverlay" class="absolute inset-0 bg-black/40"></div>

        <div class="relative bg-white w-full max-w-lg rounded-xl shadow-xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-5 py-4 border-b border-[#E8EAEC] sticky top-0 bg-white">
                <h3 class="font-['Barlow_Condensed'] font-bold text-[18px] uppercase text-[#0B2540]">
                    Employment Details
                </h3>
                <button id="closeEmploymentModalBtn" class="text-[#9AA2AA] hover:text-[#1B2733] text-xl leading-none">&times;</button>
            </div>

            <form id="employmentForm" class="p-5 space-y-5" enctype="multipart/form-data">
                <input type="hidden" name="user_id" value="<?= (int) $employee['id'] ?>">

                <!-- Department -->
                <div>
                    <label class="block text-[13px] font-semibold text-[#1B2733] mb-1.5">Department</label>
                    <div class="flex items-center gap-2">
                        <select name="department_id" id="departmentSelect"
                            class="flex-1 bg-white border border-[#D8DBDE] rounded-md px-3 py-2.5 text-[14px] outline-none focus:border-[#0B2540]">
                            <option value="">Select department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= (int) $dept['id'] ?>" <?= (isset($info['department_id']) && (int) $info['department_id'] === (int) $dept['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" id="openDepartmentModalBtn"
                            title="Manage departments"
                            class="shrink-0 w-[42px] h-[42px] flex items-center justify-center border border-[#D8DBDE] rounded-md text-[#6B7785] hover:text-[#0B2540] hover:border-[#0B2540] transition-colors">
                            <!-- gear icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Employment Type -->
                <div>
                    <label class="block text-[13px] font-semibold text-[#1B2733] mb-2">Employment Type</label>
                    <div class="grid grid-cols-2 gap-2.5">
                        <?php foreach ($employmentTypes as $value => $label): ?>
                            <label class="flex items-center gap-2 border border-[#D8DBDE] rounded-md px-3 py-2.5 cursor-pointer has-[:checked]:border-[#0B2540] has-[:checked]:bg-[#0B2540]/5">
                                <input type="radio" name="employment_type" value="<?= $value ?>"
                                    class="accent-[#0B2540]"
                                    <?= (isset($info['employment_type']) && $info['employment_type'] === $value) ? 'checked' : '' ?> required>
                                <span class="text-[13px] text-[#1B2733]"><?= $label ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Compensation -->
                <div>
                    <label class="block text-[13px] font-semibold text-[#1B2733] mb-2">Compensation</label>
                    <div class="grid grid-cols-3 gap-2.5">
                        <div>
                            <label class="block text-[11px] text-[#6B7785] mb-1">Salary</label>
                            <input type="number" step="0.01" min="0" name="salary" placeholder="e.g. 25000"
                                value="<?= htmlspecialchars($info['salary'] ?? '') ?>"
                                class="w-full bg-white border border-[#D8DBDE] rounded-md px-3 py-2.5 text-[14px] outline-none focus:border-[#0B2540]">
                        </div>
                        <div>
                            <label class="block text-[11px] text-[#6B7785] mb-1">Daily Rate</label>
                            <input type="number" step="0.01" min="0" name="daily_rate" placeholder="e.g. 610"
                                value="<?= htmlspecialchars($info['daily_rate'] ?? '') ?>"
                                class="w-full bg-white border border-[#D8DBDE] rounded-md px-3 py-2.5 text-[14px] outline-none focus:border-[#0B2540]">
                        </div>
                        <div>
                            <label class="block text-[11px] text-[#6B7785] mb-1">Allowance</label>
                            <input type="number" step="0.01" min="0" name="allowance" placeholder="e.g. 1500"
                                value="<?= htmlspecialchars($info['allowance'] ?? '') ?>"
                                class="w-full bg-white border border-[#D8DBDE] rounded-md px-3 py-2.5 text-[14px] outline-none focus:border-[#0B2540]">
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div>
                    <label class="block text-[13px] font-semibold text-[#1B2733] mb-2">Contact Information</label>
                    <div class="grid sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] text-[#6B7785] mb-1">Email Address</label>
                            <input type="email" name="email" placeholder="juan.delacruz@email.com"
                                value="<?= htmlspecialchars($info['email'] ?? '') ?>"
                                class="w-full bg-white border border-[#D8DBDE] rounded-md px-3 py-2.5 text-[14px] outline-none focus:border-[#0B2540]">
                        </div>
                        <div>
                            <label class="block text-[11px] text-[#6B7785] mb-1">Contact Number</label>
                            <input type="text" name="contact_number" placeholder="09XX XXX XXXX"
                                value="<?= htmlspecialchars($info['contact_number'] ?? '') ?>"
                                class="w-full bg-white border border-[#D8DBDE] rounded-md px-3 py-2.5 text-[14px] outline-none focus:border-[#0B2540]">
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div>
                    <label class="block text-[13px] font-semibold text-[#1B2733] mb-2">Emergency Contact</label>
                    <div class="grid sm:grid-cols-2 gap-2.5 mb-2.5">
                        <div>
                            <label class="block text-[11px] text-[#6B7785] mb-1">Name</label>
                            <input type="text" name="emergency_contact_name" placeholder="Full name"
                                value="<?= htmlspecialchars($info['emergency_contact_name'] ?? '') ?>"
                                class="w-full bg-white border border-[#D8DBDE] rounded-md px-3 py-2.5 text-[14px] outline-none focus:border-[#0B2540]">
                        </div>
                        <div>
                            <label class="block text-[11px] text-[#6B7785] mb-1">Contact No.</label>
                            <input type="text" name="emergency_contact_number" placeholder="09XX XXX XXXX"
                                value="<?= htmlspecialchars($info['emergency_contact_number'] ?? '') ?>"
                                class="w-full bg-white border border-[#D8DBDE] rounded-md px-3 py-2.5 text-[14px] outline-none focus:border-[#0B2540]">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] text-[#6B7785] mb-1">Present / Complete Address</label>
                        <textarea name="present_address" rows="2" placeholder="House/Unit No., Street, Barangay, City, Province"
                            class="w-full bg-white border border-[#D8DBDE] rounded-md px-3 py-2.5 text-[14px] outline-none focus:border-[#0B2540]"><?= htmlspecialchars($info['present_address'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Government IDs -->
                <div>
                    <label class="block text-[13px] font-semibold text-[#1B2733] mb-2">Government IDs / Numbers</label>
                    <div class="grid sm:grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[11px] text-[#6B7785] mb-1">SSS Number</label>
                            <input type="text" name="sss_number" placeholder="XX-XXXXXXX-X"
                                value="<?= htmlspecialchars($info['sss_number'] ?? '') ?>"
                                class="w-full bg-white border border-[#D8DBDE] rounded-md px-3 py-2.5 text-[14px] outline-none focus:border-[#0B2540]">
                        </div>
                        <div>
                            <label class="block text-[11px] text-[#6B7785] mb-1">PhilHealth Number</label>
                            <input type="text" name="philhealth_number" placeholder="XX-XXXXXXXXX-X"
                                value="<?= htmlspecialchars($info['philhealth_number'] ?? '') ?>"
                                class="w-full bg-white border border-[#D8DBDE] rounded-md px-3 py-2.5 text-[14px] outline-none focus:border-[#0B2540]">
                        </div>
                        <div>
                            <label class="block text-[11px] text-[#6B7785] mb-1">Pag-IBIG Number</label>
                            <input type="text" name="pagibig_number" placeholder="XXXX-XXXX-XXXX"
                                value="<?= htmlspecialchars($info['pagibig_number'] ?? '') ?>"
                                class="w-full bg-white border border-[#D8DBDE] rounded-md px-3 py-2.5 text-[14px] outline-none focus:border-[#0B2540]">
                        </div>
                        <div>
                            <label class="block text-[11px] text-[#6B7785] mb-1">TIN</label>
                            <input type="text" name="tin_number" placeholder="XXX-XXX-XXX-XXX"
                                value="<?= htmlspecialchars($info['tin_number'] ?? '') ?>"
                                class="w-full bg-white border border-[#D8DBDE] rounded-md px-3 py-2.5 text-[14px] outline-none focus:border-[#0B2540]">
                        </div>
                    </div>
                </div>

                <!-- Contract Upload (PDF) -->
                <div>
                    <label class="block text-[13px] font-semibold text-[#1B2733] mb-1.5">Contract (PDF)</label>
                    <input type="file" name="contract_file" accept="application/pdf"
                        class="w-full text-[13px] text-[#6B7785] file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-[#F5F6F7] file:text-[#0B2540] file:font-semibold file:text-[13px] border border-[#D8DBDE] rounded-md">
                    <?php if (!empty($info['contract_file'])): ?>
                        <p class="text-[12px] text-[#9AA2AA] mt-1">Current: <a href="<?= BASE_URL . '/' . htmlspecialchars($info['contract_file']) ?>" target="_blank" class="underline">view uploaded PDF</a> — uploading a new one will replace it.</p>
                    <?php endif; ?>
                </div>

                <!-- Picture Upload (auto-converted to webp) -->
                <div>
                    <label class="block text-[13px] font-semibold text-[#1B2733] mb-1.5">Photo</label>
                    <div class="flex items-center gap-3">
                        <img id="picturePreview"
                            src="<?= !empty($info['picture']) ? BASE_URL . '/' . htmlspecialchars($info['picture']) : '' ?>"
                            class="w-16 h-16 rounded-lg object-cover border border-[#E8EAEC] <?= empty($info['picture']) ? 'hidden' : '' ?>">
                        <input type="file" name="picture" id="pictureInput" accept="image/jpeg,image/png,image/webp"
                            class="flex-1 text-[13px] text-[#6B7785] file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-[#F5F6F7] file:text-[#0B2540] file:font-semibold file:text-[13px] border border-[#D8DBDE] rounded-md">
                    </div>
                    <p class="text-[12px] text-[#9AA2AA] mt-1">JPG or PNG — will be converted to WebP automatically.</p>
                </div>

                <div id="employmentFormError" class="hidden text-[13px] text-red-600"></div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" id="cancelEmploymentBtn"
                        class="px-4 py-2.5 rounded-md text-[13px] font-semibold text-[#6B7785] hover:bg-[#F5F6F7]">
                        Cancel
                    </button>
                    <button type="submit" id="saveEmploymentBtn"
                        class="px-5 py-2.5 rounded-md text-[13px] font-semibold text-white bg-[#0B2540] hover:bg-[#0B2540]/90">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== Manage Departments Modal ===================== -->
    <div id="departmentModal" class="hidden fixed inset-0 z-[60] flex items-center justify-center px-4">
        <div id="departmentModalOverlay" class="absolute inset-0 bg-black/40"></div>

        <div class="relative bg-white w-full max-w-md rounded-xl shadow-xl max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between px-5 py-4 border-b border-[#E8EAEC] sticky top-0 bg-white">
                <h3 class="font-['Barlow_Condensed'] font-bold text-[18px] uppercase text-[#0B2540]">
                    Manage Departments
                </h3>
                <button id="closeDepartmentModalBtn" class="text-[#9AA2AA] hover:text-[#1B2733] text-xl leading-none">&times;</button>
            </div>

            <div class="p-5 space-y-4">
                <form id="departmentAddForm" class="flex gap-2">
                    <input type="text" id="departmentNameInput" placeholder="New department name" required
                        class="flex-1 bg-white border border-[#D8DBDE] rounded-md px-3 py-2 text-[14px] outline-none focus:border-[#0B2540]">
                    <button type="submit" class="px-3.5 py-2 rounded-md text-[13px] font-semibold text-white bg-[#0B2540] hover:bg-[#0B2540]/90">Add</button>
                </form>

                <div id="departmentListError" class="hidden text-[13px] text-red-600"></div>
                <ul id="departmentList" class="divide-y divide-[#E8EAEC] border border-[#E8EAEC] rounded-md"></ul>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const baseUrl = <?= json_encode(BASE_URL) ?>;
            const userId = <?= (int) $employee['id'] ?>;

            // ---------- Employment modal open/close ----------
            const employmentModal = document.getElementById('employmentModal');
            document.getElementById('openEmploymentModalBtn').addEventListener('click', () => employmentModal.classList.remove('hidden'));
            document.getElementById('closeEmploymentModalBtn').addEventListener('click', () => employmentModal.classList.add('hidden'));
            document.getElementById('cancelEmploymentBtn').addEventListener('click', () => employmentModal.classList.add('hidden'));
            document.getElementById('employmentModalOverlay').addEventListener('click', () => employmentModal.classList.add('hidden'));

            // ---------- Picture live preview ----------
            const pictureInput = document.getElementById('pictureInput');
            const picturePreview = document.getElementById('picturePreview');
            pictureInput.addEventListener('change', () => {
                const file = pictureInput.files[0];
                if (!file) return;
                picturePreview.src = URL.createObjectURL(file);
                picturePreview.classList.remove('hidden');
            });

            // ---------- Employment form submit ----------
            const employmentForm = document.getElementById('employmentForm');
            const employmentFormError = document.getElementById('employmentFormError');

            employmentForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                employmentFormError.classList.add('hidden');

                const saveBtn = document.getElementById('saveEmploymentBtn');
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving...';

                try {
                    const formData = new FormData(employmentForm);
                    const res = await fetch(`${baseUrl}/save-employment`, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();

                    if (!data.success) {
                        throw new Error(data.message || 'Failed to save employment details.');
                    }

                    // Reload to reflect saved data (simplest, avoids duplicating render logic)
                    window.location.reload();
                } catch (err) {
                    employmentFormError.textContent = err.message;
                    employmentFormError.classList.remove('hidden');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save';
                }
            });

            // ---------- Department modal ----------
            const departmentModal = document.getElementById('departmentModal');
            const departmentList = document.getElementById('departmentList');
            const departmentListError = document.getElementById('departmentListError');
            const departmentSelect = document.getElementById('departmentSelect');
            const departmentAddForm = document.getElementById('departmentAddForm');
            const departmentNameInput = document.getElementById('departmentNameInput');

            document.getElementById('openDepartmentModalBtn').addEventListener('click', () => {
                departmentModal.classList.remove('hidden');
                loadDepartments();
            });
            document.getElementById('closeDepartmentModalBtn').addEventListener('click', () => departmentModal.classList.add('hidden'));
            document.getElementById('departmentModalOverlay').addEventListener('click', () => departmentModal.classList.add('hidden'));

            async function loadDepartments() {
                departmentListError.classList.add('hidden');
                try {
                    const res = await fetch(`${baseUrl}/departmentcrud?action=list`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.message || 'Failed to load departments.');
                    renderDepartments(data.departments);
                } catch (err) {
                    departmentListError.textContent = err.message;
                    departmentListError.classList.remove('hidden');
                }
            }

            function renderDepartments(departments) {
                if (!departments.length) {
                    departmentList.innerHTML = `<li class="px-3 py-3 text-[13px] text-[#9AA2AA]">No departments yet.</li>`;
                    return;
                }
                // Each row has a "view" state (name + Edit/Delete) and an "edit" state
                // (text input + Save/Cancel) that gets toggled in place — no browser prompt().
                departmentList.innerHTML = departments.map(d => `
                    <li class="px-3 py-2.5" data-id="${d.id}" data-name="${escapeHtml(d.name)}">
                        <div class="dept-view flex items-center justify-between gap-2">
                            <span class="text-[14px] text-[#1B2733] dept-name flex-1">${escapeHtml(d.name)}</span>
                            <button type="button" class="edit-dept text-[12px] font-semibold text-[#0B2540] hover:underline">Edit</button>
                            <button type="button" class="delete-dept text-[12px] font-semibold text-red-600 hover:underline">Delete</button>
                        </div>
                        <div class="dept-edit hidden flex items-center gap-2">
                            <input type="text" class="dept-edit-input flex-1 bg-white border border-[#D8DBDE] rounded-md px-2.5 py-1.5 text-[13px] outline-none focus:border-[#0B2540]" value="${escapeHtml(d.name)}">
                            <button type="button" class="save-dept text-[12px] font-semibold text-[#0B2540] hover:underline">Save</button>
                            <button type="button" class="cancel-dept text-[12px] font-semibold text-[#6B7785] hover:underline">Cancel</button>
                        </div>
                    </li>
                `).join('');

                // Sync main dropdown too
                const currentVal = departmentSelect.value;
                departmentSelect.innerHTML = '<option value="">Select department</option>' +
                    departments.map(d => `<option value="${d.id}">${escapeHtml(d.name)}</option>`).join('');
                departmentSelect.value = currentVal;
            }

            function escapeHtml(str) {
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            departmentAddForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const name = departmentNameInput.value.trim();
                if (!name) return;
                try {
                    const res = await fetch(`${baseUrl}/departmentcrud`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ action: 'add', name })
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.message || 'Failed to add department.');
                    departmentNameInput.value = '';
                    loadDepartments();
                } catch (err) {
                    departmentListError.textContent = err.message;
                    departmentListError.classList.remove('hidden');
                }
            });

            departmentList.addEventListener('click', async (e) => {
                const li = e.target.closest('li[data-id]');
                if (!li) return;
                const id = li.dataset.id;

                // Delete
                if (e.target.classList.contains('delete-dept')) {
                    if (!confirm('Delete this department?')) return;
                    try {
                        const res = await fetch(`${baseUrl}/departmentcrud`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({ action: 'delete', id })
                        });
                        const data = await res.json();
                        if (!data.success) throw new Error(data.message || 'Failed to delete department.');
                        loadDepartments();
                    } catch (err) {
                        departmentListError.textContent = err.message;
                        departmentListError.classList.remove('hidden');
                    }
                    return;
                }

                // Switch row into inline-edit mode (replaces the old prompt() dialog)
                if (e.target.classList.contains('edit-dept')) {
                    li.querySelector('.dept-view').classList.add('hidden');
                    const editRow = li.querySelector('.dept-edit');
                    editRow.classList.remove('hidden');
                    const input = li.querySelector('.dept-edit-input');
                    input.focus();
                    input.select();
                    return;
                }

                // Cancel inline edit
                if (e.target.classList.contains('cancel-dept')) {
                    li.querySelector('.dept-edit').classList.add('hidden');
                    li.querySelector('.dept-view').classList.remove('hidden');
                    li.querySelector('.dept-edit-input').value = li.dataset.name;
                    return;
                }

                // Save inline edit
                if (e.target.classList.contains('save-dept')) {
                    const input = li.querySelector('.dept-edit-input');
                    const newName = input.value.trim();
                    const currentName = li.dataset.name;
                    if (!newName || newName === currentName) {
                        li.querySelector('.dept-edit').classList.add('hidden');
                        li.querySelector('.dept-view').classList.remove('hidden');
                        return;
                    }
                    try {
                        const res = await fetch(`${baseUrl}/departmentcrud`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({ action: 'edit', id, name: newName })
                        });
                        const data = await res.json();
                        if (!data.success) throw new Error(data.message || 'Failed to update department.');
                        loadDepartments();
                    } catch (err) {
                        departmentListError.textContent = err.message;
                        departmentListError.classList.remove('hidden');
                    }
                    return;
                }
            });

            // Allow Enter/Escape inside the inline edit input
            departmentList.addEventListener('keydown', (e) => {
                if (!e.target.classList.contains('dept-edit-input')) return;
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.target.closest('li').querySelector('.save-dept').click();
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    e.target.closest('li').querySelector('.cancel-dept').click();
                }
            });
        })();
    </script>

</body>

</html>
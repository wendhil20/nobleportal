<?php
//employmee-main.php
include ROOT_PATH . "/network/connect.php";

$targetUserId = $_SESSION['user_id'] ?? 0;

if ($targetUserId <= 0) {
    header("Location: " . BASE_URL . "/login");
    exit;
}

// Fetch employee basic info
$stmt = $conn->prepare("SELECT id, first_name, last_name, username, created_at FROM nobleuserlist WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $targetUserId);
$stmt->execute();
$targetUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$targetUser) {
    header("Location: " . BASE_URL . "/login");
    exit;
}

// Fetch employment details (department, employment type, compensation, contact info, contract, picture)
$stmt = $conn->prepare(
    "SELECT ei.*, d.name AS department_name
     FROM nobleuser_employment_details ei
     LEFT JOIN departments d ON d.id = ei.department_id
     WHERE ei.user_id = ?"
);
$stmt->bind_param("i", $targetUserId);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Kunin ang tunay na reference number (hal. NHCC-HR2026-0001) mula sa
// 201 File record — gaya ng ginagawa sa main.php — para magtugma ang
// Ref No. sa dalawang page. Placeholder na lang kapag wala pang
// naisumiteng 201 File.
$stmt = $conn->prepare("SELECT reference_number FROM nobleuser_employee_information WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $targetUserId);
$stmt->execute();
$refRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

$employmentTypes = [
    'trainee'        => 'Trainee',
    'probationary'   => 'Probationary',
    'regular'        => 'Regular',
    'project_based'  => 'Project Based / Contractual',
];

// Color mapping per employment type (badge/pill style)
$employmentTypeColors = [
    'trainee'        => 'bg-[#FEF3C7] text-[#92400E] border-[#FDE68A]', // amber
    'probationary'   => 'bg-[#FFE4E6] text-[#9F1239] border-[#FECDD3]', // rose
    'regular'        => 'bg-[#D1FAE5] text-[#065F46] border-[#A7F3D0]', // green
    'project_based'  => 'bg-[#DBEAFE] text-[#1E40AF] border-[#BFDBFE]', // blue
];

$currentType = $info['employment_type'] ?? '';
$typeColorClass = $employmentTypeColors[$currentType] ?? 'bg-[#F3F4F6] text-[#374151] border-[#E5E7EB]';

$fileNo = $refRow['reference_number'] ?? ('HR-201-' . str_pad($targetUser['id'], 5, '0', STR_PAD_LEFT));

function formatPeso($value) {
    return $value !== null ? '₱' . number_format((float) $value, 2) : '—';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Personal Information</title>
    <?php include ROOT_PATH . "/link/top.php"; ?>
</head>

<body class="bg-[#EDEAE1] font-['Inter']">

    <?php include ROOT_PATH . "/ui/navigation/top.php"; ?>

    <!-- ==== PAGE CONTENT ==== -->
    <main class="md:pl-64 pt-6 pb-24 md:pb-10 px-4 md:px-8">
        <div class="max-w-2xl mx-auto">

            <div class="bg-[#FCFBF8] border border-[#D9D4C6] rounded-sm shadow-[0_1px_2px_rgba(0,0,0,0.04)]">

                <!-- Letterhead -->
                <div class="border-b-2 border-[#0B2540] px-6 md:px-8 pt-7 pb-5">
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div>
                            <p class="text-[10.5px] font-semibold tracking-[0.24em] uppercase text-[#A9822C] mb-1.5">
                                Employee 201 File
                            </p>
                            <h1 class="font-serif font-normal text-[26px] md:text-[28px] text-[#0B2540] leading-tight">
                                Employment Details
                            </h1>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-[10px] tracking-[0.1em] uppercase text-[#8B8371]">File No.</p>
                            <p class="text-[13px] font-semibold text-[#241F14] font-mono">Ref: <?= htmlspecialchars($fileNo) ?></p>
                        </div>
                    </div>
                </div>

                <div class="px-6 md:px-8 pt-6 pb-7">

                    <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] mb-6">
                        <?= htmlspecialchars(trim($targetUser['first_name'] . ' ' . $targetUser['last_name'])) ?>
                    </p>

                    <?php if ($info): ?>

                        <div class="flex flex-col gap-6">

                            <!-- SECTION: Photo -->
                            <div class="flex items-start gap-4">
                                <?php if (!empty($info['picture'])): ?>
                                    <img src="<?= BASE_URL . '/' . htmlspecialchars($info['picture']) ?>"
                                        alt="Employee photo"
                                        id="employeePhotoTrigger"
                                        class="w-20 h-20 rounded-sm object-cover border border-[#D9D4C6] cursor-pointer hover:opacity-90 transition-opacity">
                                <?php else: ?>
                                    <div class="w-20 h-20 rounded-sm bg-[#F5F3EC] border border-[#D9D4C6] flex items-center justify-center text-[#9AA2AA] text-[11px] text-center">
                                        No photo
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- SECTION: Employment Info -->
                            <div>
                                <p class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C] mb-3 pb-2 border-b-2 border-[#0B2540]">
                                    I. Employment Information
                                </p>
                                <div class="flex flex-col">
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Department</p>
                                        <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($info['department_name'] ?? '—') ?></p>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8] last:border-b-0">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Employment Type</p>
                                        <p class="text-[13.5px] leading-snug">
                                            <span class="inline-block px-2.5 py-0.5 rounded-full border text-[12px] font-semibold <?= $typeColorClass ?>">
                                                <?= htmlspecialchars($employmentTypes[$currentType] ?? $currentType) ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION: Compensation (hidden by default, toggle to reveal) -->
                            <div>
                                <div class="flex items-center justify-between mb-3 pb-2 border-b-2 border-[#0B2540]">
                                    <p class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C]">
                                        II. Compensation
                                    </p>
                                    <button type="button" id="toggleSalaryBtn"
                                        class="inline-flex items-center gap-1.5 text-[11.5px] font-semibold text-[#0B2540] hover:text-[#A9822C] transition-colors">
                                        <svg id="salaryEyeIcon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                        <span id="salaryToggleLabel">Show</span>
                                    </button>
                                </div>
                                <div class="flex flex-col">
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Salary</p>
                                        <p class="salary-value text-[13.5px] text-[#241F14] leading-snug tracking-wider" data-value="<?= htmlspecialchars(formatPeso($info['salary'] ?? null)) ?>">••••••••</p>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Daily Rate</p>
                                        <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars(formatPeso($info['daily_rate'] ?? null)) ?></p>
                                    </div>

                                    <!-- Allowance breakdown -->
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Load</p>
                                        <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars(formatPeso($info['allowance_load'] ?? null)) ?></p>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Transportation</p>
                                        <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars(formatPeso($info['allowance_transportation'] ?? null)) ?></p>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Meal</p>
                                        <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars(formatPeso($info['allowance_meal'] ?? null)) ?></p>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0"><?= !empty($info['allowance_others_label']) ? htmlspecialchars($info['allowance_others_label']) : 'Others' ?></p>
                                        <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars(formatPeso($info['allowance_others_amount'] ?? null)) ?></p>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8] last:border-b-0">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Allowance</p>
                                        <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars(formatPeso($info['allowance'] ?? null)) ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION: Contact Information -->
                            <div>
                                <p class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C] mb-3 pb-2 border-b-2 border-[#0B2540]">
                                    III. Contact Information
                                </p>
                                <div class="flex flex-col">
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Email Address</p>
                                        <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($info['email'] ?: '—') ?></p>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8] last:border-b-0">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Contact Number</p>
                                        <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($info['contact_number'] ?: '—') ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION: Emergency Contact -->
                            <div>
                                <p class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C] mb-3 pb-2 border-b-2 border-[#0B2540]">
                                    IV. Emergency Contact
                                </p>
                                <div class="flex flex-col">
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Name</p>
                                        <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($info['emergency_contact_name'] ?: '—') ?></p>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Contact No.</p>
                                        <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($info['emergency_contact_number'] ?: '—') ?></p>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8] last:border-b-0">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Present Address</p>
                                        <p class="text-[13.5px] text-[#241F14] leading-snug"><?= nl2br(htmlspecialchars($info['present_address'] ?: '—')) ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION: Government IDs -->
                            <div>
                                <p class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C] mb-3 pb-2 border-b-2 border-[#0B2540]">
                                    V. Government IDs / Numbers
                                </p>
                                <div class="flex flex-col">
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">SSS</p>
                                        <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($info['sss_number'] ?: '—') ?></p>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">PhilHealth</p>
                                        <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($info['philhealth_number'] ?: '—') ?></p>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Pag-IBIG</p>
                                        <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($info['pagibig_number'] ?: '—') ?></p>
                                    </div>
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8] last:border-b-0">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">TIN</p>
                                        <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($info['tin_number'] ?: '—') ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- SECTION: Contract -->
                            <div>
                                <p class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C] mb-3 pb-2 border-b-2 border-[#0B2540]">
                                    VI. Contract
                                </p>
                                <?php if (!empty($info['contract_file'])): ?>
                                    <a href="<?= BASE_URL . '/' . htmlspecialchars($info['contract_file']) ?>" target="_blank"
                                        class="text-[13.5px] font-semibold text-[#0B2540] hover:text-[#A9822C] underline underline-offset-2">
                                        View Contract PDF →
                                    </a>
                                <?php else: ?>
                                    <p class="text-[13.5px] text-[#9AA2AA]">No contract uploaded yet.</p>
                                <?php endif; ?>
                            </div>

                        </div>

                    <?php else: ?>
                        <p class="text-sm text-[#6B6350]">
                            Your employment details have not been set up yet. Please coordinate with the Human Resources
                            Department.
                        </p>
                    <?php endif; ?>

                </div>

                <!-- Footer -->
                <div class="border-t border-[#D9D4C6] px-6 md:px-8 py-3.5 flex items-center justify-between flex-wrap gap-2">
                    <p class="text-[10px] text-[#B7AF9C] tracking-[0.04em]">This record is maintained by the Human Resources Department.</p>
                    <p class="text-[10px] text-[#B7AF9C] font-mono"><?= htmlspecialchars($fileNo) ?></p>
                </div>

            </div>

        </div>
    </main>

    <!-- ===================== Photo Lightbox ===================== -->
    <div id="photoLightbox" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
        <div id="photoLightboxOverlay" class="absolute inset-0 bg-black/70"></div>
        <div class="relative max-w-lg w-full">
            <button type="button" id="closePhotoLightboxBtn"
                class="absolute -top-10 right-0 text-white/80 hover:text-white text-2xl leading-none">&times;</button>
            <img id="photoLightboxImg" src="" alt="Employee photo"
                class="w-full max-h-[80vh] object-contain rounded-sm border border-white/10">
        </div>
    </div>

    <script>
        (function () {
            const toggleBtn = document.getElementById('toggleSalaryBtn');
            if (!toggleBtn) return;

            const eyeIcon = document.getElementById('salaryEyeIcon');
            const label = document.getElementById('salaryToggleLabel');
            const values = document.querySelectorAll('.salary-value');
            let revealed = false;

            const eyeOpenPath = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" />';
            const eyeClosedPath = '<path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a20.3 20.3 0 0 1 4.22-5.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a20.29 20.29 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24" /><line x1="1" y1="1" x2="23" y2="23" />';

            toggleBtn.addEventListener('click', function () {
                revealed = !revealed;

                values.forEach(function (el) {
                    el.textContent = revealed ? el.dataset.value : '••••••••';
                });

                eyeIcon.innerHTML = revealed ? eyeClosedPath : eyeOpenPath;
                label.textContent = revealed ? 'Hide' : 'Show';
            });
        })();
    </script>

    <script>
        (function () {
            const trigger = document.getElementById('employeePhotoTrigger');
            if (!trigger) return;

            const lightbox = document.getElementById('photoLightbox');
            const lightboxImg = document.getElementById('photoLightboxImg');
            const closeBtn = document.getElementById('closePhotoLightboxBtn');
            const overlay = document.getElementById('photoLightboxOverlay');

            function openLightbox() {
                lightboxImg.src = trigger.src;
                lightbox.classList.remove('hidden');
            }
            function closeLightbox() {
                lightbox.classList.add('hidden');
                lightboxImg.src = '';
            }

            trigger.addEventListener('click', openLightbox);
            closeBtn.addEventListener('click', closeLightbox);
            overlay.addEventListener('click', closeLightbox);
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeLightbox();
            });
        })();
    </script>

</body>

</html>
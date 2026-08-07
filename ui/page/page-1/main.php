<?php
//main.php
include ROOT_PATH . "/network/connect.php";

$targetUserId = $_SESSION['user_id'] ?? 0;

if ($targetUserId <= 0) {
    header("Location: " . BASE_URL . "/login");
    exit;
}

$stmt = $conn->prepare("SELECT id, first_name, last_name, username FROM nobleuserlist WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $targetUserId);
$stmt->execute();
$targetUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$targetUser) {
    header("Location: " . BASE_URL . "/login");
    exit;
}

$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

function old($key, $old, $default = '')
{
    return htmlspecialchars($old[$key] ?? $default);
}

// ==== Document types config (Step 3) ====
$documentTypes = require ROOT_PATH . "/ui/page/page-1/backend/document_types.php";

// Kunin kung MARRIED ba (kung meron nang naka-save na info) + review status
$stmt = $conn->prepare("SELECT marital_status, status, review_notes, first_name, middle_name, last_name, extension_name, birthdate, age, gender, birthplace, present_address, religion, citizenship FROM nobleuser_employee_information WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $targetUserId);
$stmt->execute();
$existingInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

// I-lock ang form (ipakita ang read-only view) kapag PENDING o APPROVED na ang review.
// REJECTED lang dapat pabalik sa editable form para makapag-resubmit ang employee.
$isLocked = !empty($existingInfo['status']) && in_array($existingInfo['status'], ['PENDING', 'APPROVED'], true);

$currentMarital = $old['marital_status'] ?? ($existingInfo['marital_status'] ?? '');

// Kunin ang mga na-upload na documents (kasama id at mime_type para sa view/download)
$uploadedDocs = [];
$stmt = $conn->prepare("SELECT id, document_type, original_filename, mime_type FROM nobleuser_employee_documents WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $targetUserId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $uploadedDocs[$row['document_type']][] = $row;
}
$stmt->close();

$fileNo = 'HR-201-' . str_pad($targetUser['id'], 5, '0', STR_PAD_LEFT);
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
                                Personal Information
                            </h1>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-[10px] tracking-[0.1em] uppercase text-[#8B8371]">File No.</p>
                            <p class="text-[13px] font-semibold text-[#241F14] font-mono">Ref: <?= htmlspecialchars($fileNo) ?></p>
                        </div>
                    </div>
                </div>

                <div class="px-6 md:px-8 pt-6 pb-7">

                    <?php if (!$isLocked): ?>
                        <!-- Stepper -->
                        <div class="flex items-center mb-8">
                            <div class="flex items-center gap-2.5" data-step-indicator="1">
                                <div
                                    class="step-circle w-8 h-8 rounded-sm bg-[#0B2540] text-white flex items-center justify-center text-xs font-serif font-semibold shrink-0">
                                    1</div>
                                <span class="step-label text-sm font-semibold text-[#0B2540] hidden sm:inline tracking-[0.02em]">Basic Info</span>
                            </div>
                            <div class="flex-1 h-[1px] bg-[#D8DBDE] mx-3" data-step-line="1"></div>
                            <div class="flex items-center gap-2.5" data-step-indicator="2">
                                <div
                                    class="step-circle w-8 h-8 rounded-sm bg-[#D8DBDE] text-[#6B7785] flex items-center justify-center text-xs font-serif font-semibold shrink-0">
                                    2</div>
                                <span class="step-label text-sm font-medium text-[#9AA2AA] hidden sm:inline tracking-[0.02em]">Additional Info</span>
                            </div>
                            <div class="flex-1 h-[1px] bg-[#D8DBDE] mx-3" data-step-line="2"></div>
                            <div class="flex items-center gap-2.5" data-step-indicator="3">
                                <div
                                    class="step-circle w-8 h-8 rounded-sm bg-[#D8DBDE] text-[#6B7785] flex items-center justify-center text-xs font-serif font-semibold shrink-0">
                                    3</div>
                                <span class="step-label text-sm font-medium text-[#9AA2AA] hidden sm:inline tracking-[0.02em]">Documents</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['form_error'])): ?>
                        <p class="mb-5 text-sm text-[#A32D2D] bg-[#FBEEEE] border border-[#EACACA] rounded-sm px-3.5 py-2.5">
                            <?= htmlspecialchars($_SESSION['form_error']) ?>
                        </p>
                        <?php unset($_SESSION['form_error']); ?>
                    <?php endif; ?>

                    <!-- ==== Review Status Banner (PENDING / REJECTED lang; APPROVED may sariling formal seal na sa loob) ==== -->
                    <?php if (!empty($existingInfo['status'])): ?>
                        <?php if ($existingInfo['status'] === 'PENDING'): ?>
                            <p class="mb-5 text-sm text-[#8A6D1F] bg-[#FBF5E6] border border-[#E7D8B0] rounded-sm px-3.5 py-2.5">
                                Your 201 File is pending review by HR. You cannot make changes while it is under review.
                            </p>
                        <?php elseif ($existingInfo['status'] === 'REJECTED'): ?>
                            <p class="mb-5 text-sm text-[#A32D2D] bg-[#FBEEEE] border border-[#EACACA] rounded-sm px-3.5 py-2.5">
                                Your 201 File was
                                rejected<?= !empty($existingInfo['review_notes']) ? ': ' . htmlspecialchars($existingInfo['review_notes']) : '.' ?>
                                Please review and resubmit.
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($isLocked): ?>

                        <!-- ==== LOCKED VIEW (read-only, walang edit habang PENDING/APPROVED) ==== -->
                        <div>

                            <?php if ($existingInfo['status'] === 'APPROVED'): ?>
                                <!-- ==== Formal Verification Stamp ==== -->
                                <div class="flex items-start justify-between gap-4 mb-6 pb-6 border-b border-dashed border-[#D9D4C6]">
                                    <div class="min-w-0">
                                        <p class="text-[10.5px] font-semibold tracking-[0.14em] uppercase text-[#8B8371] mb-1">
                                            Status
                                        </p>
                                        <p class="text-[13px] text-[#4A4636] leading-relaxed max-w-md">
                                            This record has been reviewed and confirmed accurate by Human Resources. It is now part
                                            of the employee's official 201 File on record.
                                        </p>
                                        <?php if (!empty($existingInfo['review_notes'])): ?>
                                            <p class="text-[12px] text-[#8B8371] mt-2 italic">
                                                HR note: <?= htmlspecialchars($existingInfo['review_notes']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="border-2 text-[#1F6B3A] border-[#1F6B3A] rounded-sm px-4 py-1.5 rotate-[-2deg] select-none shrink-0">
                                        <p class="font-serif font-bold text-[12px] tracking-[0.1em] uppercase leading-tight text-center">Verified<br>&amp; Approved</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="text-sm text-[#6B6350] mb-6">
                                    Your submitted information is being reviewed by HR. You'll be able to make changes again once it has
                                    been approved or rejected.
                                </p>
                            <?php endif; ?>

                            <div class="flex flex-col gap-6">

                                <!-- SECTION: Personal Details -->
                                <div>
                                    <p class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C] mb-3 pb-2 border-b-2 border-[#0B2540]">
                                        I. Personal Details
                                    </p>
                                    <div class="flex flex-col">
                                        <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                            <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Full Name</p>
                                            <p class="text-[13.5px] text-[#241F14] leading-snug">
                                                <?= htmlspecialchars(trim(($existingInfo['first_name'] ?? '') . ' ' . ($existingInfo['middle_name'] ?? '') . ' ' . ($existingInfo['last_name'] ?? '') . ' ' . ($existingInfo['extension_name'] ?? ''))) ?>
                                            </p>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                            <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Birthdate / Age</p>
                                            <p class="text-[13.5px] text-[#241F14] leading-snug">
                                                <?= htmlspecialchars($existingInfo['birthdate'] ?? '') ?> (<?= htmlspecialchars($existingInfo['age'] ?? '') ?>)
                                            </p>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                            <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Gender</p>
                                            <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($existingInfo['gender'] ?? '') ?></p>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                            <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Marital Status</p>
                                            <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($existingInfo['marital_status'] ?? '') ?></p>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8] last:border-b-0">
                                            <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Birthplace</p>
                                            <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($existingInfo['birthplace'] ?? '') ?></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION: Background -->
                                <div>
                                    <p class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C] mb-3 pb-2 border-b-2 border-[#0B2540]">
                                        II. Background
                                    </p>
                                    <div class="flex flex-col">
                                        <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                                            <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Religion</p>
                                            <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($existingInfo['religion'] ?? '') ?></p>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8] last:border-b-0">
                                            <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Citizenship</p>
                                            <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($existingInfo['citizenship'] ?? '') ?></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- SECTION: Address -->
                                <div>
                                    <p class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C] mb-3 pb-2 border-b-2 border-[#0B2540]">
                                        III. Address
                                    </p>
                                    <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2">
                                        <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Present Complete Address</p>
                                        <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($existingInfo['present_address'] ?? '') ?></p>
                                    </div>
                                </div>

                            </div>

                            <?php if (!empty($uploadedDocs)): ?>
                                <div class="mt-6 pt-5 border-t-2 border-[#0B2540]">
                                    <details class="group/main">
                                        <summary
                                            class="flex items-center justify-between gap-2 cursor-pointer select-none list-none mb-3">
                                            <span class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C]">
                                                IV. Submitted Documents
                                                <span
                                                    class="text-[#8B8371] normal-case font-medium tracking-normal">(<?= array_sum(array_map('count', $uploadedDocs)) ?>
                                                    files)</span>
                                            </span>
                                            <span class="flex items-center gap-1 text-[11px] font-semibold text-[#0B2540]">
                                                <span class="group-open/main:hidden">Show all</span>
                                                <span class="hidden group-open/main:inline">Hide</span>
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="w-3.5 h-3.5 transition-transform group-open/main:rotate-180" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </span>
                                        </summary>

                                        <div class="space-y-2">
                                            <?php foreach ($uploadedDocs as $docKey => $docList): ?>
                                                <details class="group border border-[#E4E1D8] rounded-sm overflow-hidden">
                                                    <summary
                                                        class="flex items-center justify-between gap-2 px-3.5 py-2.5 text-[13px] font-semibold text-[#241F14] bg-[#F5F3EC] cursor-pointer select-none list-none">
                                                        <span class="flex items-center gap-2">
                                                            <?= htmlspecialchars($documentTypes[$docKey]['label'] ?? ucwords(str_replace('_', ' ', $docKey))) ?>
                                                            <span
                                                                class="text-[10px] font-mono font-semibold text-[#8B8371] bg-white border border-[#D9D4C6] rounded-full px-2 py-0.5">
                                                                <?= count($docList) ?>
                                                            </span>
                                                        </span>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="w-4 h-4 text-[#8B8371] transition-transform group-open:rotate-180 shrink-0"
                                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                        </svg>
                                                    </summary>
                                                    <ul class="space-y-1.5 px-3.5 py-3 bg-white">
                                                        <?php foreach ($docList as $file): ?>
                                                            <li
                                                                class="flex items-center justify-between gap-2 text-[13px] text-[#4A4636] bg-[#F5F3EC] rounded-sm px-2.5 py-1.5">
                                                                <span
                                                                    class="truncate"><?= htmlspecialchars($file['original_filename']) ?></span>
                                                                <a href="<?= BASE_URL ?>/page-1-viewdocument?id=<?= (int) $file['id'] ?>"
                                                                    target="_blank"
                                                                    class="text-[#0B2540] font-semibold hover:text-[#A9822C] underline underline-offset-2 shrink-0">View</a>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </details>
                                            <?php endforeach; ?>
                                        </div>
                                    </details>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php else: ?>

                        <form action="<?= BASE_URL ?>/page-1-personalhandler" method="post" enctype="multipart/form-data"
                            id="personalInfoForm">

                            <input type="hidden" name="user_id" value="<?= (int) $targetUser['id'] ?>">

                            <?php include ROOT_PATH . "/ui/page/page-1/step-1.php"; ?>
                            <?php include ROOT_PATH . "/ui/page/page-1/step-2.php"; ?>
                            <?php include ROOT_PATH . "/ui/page/page-1/step-3.php"; ?>

                        </form>
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

    <!-- ==== Document Preview Modal ==== -->
    <div id="docPreviewModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/60 p-4">
        <div class="bg-[#FCFBF8] rounded-sm w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden border border-[#D9D4C6]">
            <div class="flex items-center justify-between px-5 py-3 border-b-2 border-[#0B2540]">
                <p id="docPreviewName" class="text-sm font-semibold text-[#241F14] truncate pr-4"></p>
                <button type="button" id="docPreviewClose"
                    class="text-[#8B8371] hover:text-[#241F14] text-xl leading-none shrink-0">&times;</button>
            </div>
            <div id="docPreviewBody" class="flex-1 overflow-auto bg-[#EDEAE1] flex items-center justify-center p-4">
            </div>
        </div>
    </div>

    <!-- ==== Toast Notification ==== -->
    <div id="toastNotification"
        class="hidden fixed top-5 right-5 z-[60] max-w-sm w-full bg-[#FCFBF8] border border-[#CFE0CE] rounded-sm shadow-lg px-4 py-3.5 flex items-start gap-3 translate-x-4 opacity-0 transition-all duration-300">
        <div class="w-5 h-5 rounded-full bg-[#F0F6EF] text-[#1F6B3A] flex items-center justify-center shrink-0 mt-0.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        </div>
        <p class="text-sm text-[#241F14] font-medium flex-1">201 File information saved successfully.</p>
        <button type="button" id="toastClose"
            class="text-[#8B8371] hover:text-[#241F14] shrink-0 leading-none">&times;</button>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <script>
            (function () {
                const toast = document.getElementById('toastNotification');
                const closeBtn = document.getElementById('toastClose');

                function showToast() {
                    toast.classList.remove('hidden');
                    requestAnimationFrame(function () {
                        toast.classList.remove('translate-x-4', 'opacity-0');
                    });
                }

                function hideToast() {
                    toast.classList.add('translate-x-4', 'opacity-0');
                    setTimeout(function () {
                        toast.classList.add('hidden');
                    }, 300);
                }

                showToast();
                setTimeout(hideToast, 4000);
                closeBtn.addEventListener('click', hideToast);

                if (window.history.replaceState) {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('saved');
                    window.history.replaceState({}, document.title, url.pathname + url.search);
                }
            })();
        </script>
    <?php endif; ?>

    <?php if (!$isLocked): ?>
        <script>
            (function () {
                const form = document.getElementById('personalInfoForm');
                const steps = form.querySelectorAll('[data-step]');
                const nextBtns = form.querySelectorAll('[data-next]');
                const backBtns = form.querySelectorAll('[data-back]');
                const circles = document.querySelectorAll('[data-step-indicator] .step-circle');
                const labels = document.querySelectorAll('[data-step-indicator] .step-label');
                const lines = document.querySelectorAll('[data-step-line]');
                let currentStep = 1;

                function showStep(stepNumber) {
                    currentStep = stepNumber;

                    steps.forEach(function (s) {
                        if (s.dataset.step === String(stepNumber)) {
                            s.classList.remove('hidden');
                            s.classList.add('flex');
                        } else {
                            s.classList.add('hidden');
                            s.classList.remove('flex');
                        }
                    });

                    circles.forEach(function (c, i) {
                        const n = i + 1;
                        if (n <= stepNumber) {
                            c.classList.remove('bg-[#D8DBDE]', 'text-[#6B7785]');
                            c.classList.add('bg-[#0B2540]', 'text-white');
                        } else {
                            c.classList.add('bg-[#D8DBDE]', 'text-[#6B7785]');
                            c.classList.remove('bg-[#0B2540]', 'text-white');
                        }
                    });

                    labels.forEach(function (l, i) {
                        const n = i + 1;
                        if (n <= stepNumber) {
                            l.classList.remove('text-[#9AA2AA]', 'font-medium');
                            l.classList.add('text-[#0B2540]', 'font-semibold');
                        } else {
                            l.classList.add('text-[#9AA2AA]', 'font-medium');
                            l.classList.remove('text-[#0B2540]', 'font-semibold');
                        }
                    });

                    lines.forEach(function (line) {
                        const lineStep = parseInt(line.dataset.stepLine, 10);
                        const isDone = stepNumber > lineStep;
                        line.classList.toggle('bg-[#0B2540]', isDone);
                        line.classList.toggle('bg-[#D8DBDE]', !isDone);
                    });

                    window.scrollTo({ top: form.offsetTop - 20, behavior: 'smooth' });
                }

                function validateStep(stepNumber) {
                    const stepEl = form.querySelector('[data-step="' + stepNumber + '"]');
                    const fields = stepEl.querySelectorAll('input:not([type="file"]), select, textarea');
                    for (const el of fields) {
                        if (!el.checkValidity()) {
                            el.reportValidity();
                            return false;
                        }
                    }
                    return true;
                }

                // ==== Certification agreement gate bago mag-submit ====
                const agreementCheckbox = document.getElementById('agreement');
                const agreementError = document.getElementById('agreementError');

                if (agreementCheckbox) {
                    form.addEventListener('submit', function (e) {
                        if (!agreementCheckbox.checked) {
                            e.preventDefault();
                            agreementError.classList.remove('hidden');
                            agreementCheckbox.closest('.border').classList.add('border-red-400');
                            agreementCheckbox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    });

                    agreementCheckbox.addEventListener('change', function () {
                        if (agreementCheckbox.checked) {
                            agreementError.classList.add('hidden');
                            agreementCheckbox.closest('.border').classList.remove('border-red-400');
                        }
                    });
                }

                nextBtns.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        if (!validateStep(currentStep)) return;
                        showStep(currentStep + 1);
                    });
                });

                backBtns.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        showStep(currentStep - 1);
                    });
                });

                form.querySelectorAll('.uppercase-input').forEach(function (input) {
                    input.addEventListener('input', function () {
                        const pos = input.selectionStart;
                        input.value = input.value.toUpperCase();
                        input.setSelectionRange(pos, pos);
                    });
                });

                const birthdateInput = document.getElementById('birthdate');
                const ageInput = document.getElementById('age');
                birthdateInput.addEventListener('change', function () {
                    const dob = new Date(birthdateInput.value);
                    if (isNaN(dob.getTime())) return;
                    const today = new Date();
                    let age = today.getFullYear() - dob.getFullYear();
                    const m = today.getMonth() - dob.getMonth();
                    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
                        age--;
                    }
                    ageInput.value = age >= 0 ? age : '';
                });

                const maritalSelect = document.getElementById('marital_status');
                const marriageDocBlock = form.querySelector('[data-marriage-doc]');
                const marriageInput = marriageDocBlock ? marriageDocBlock.querySelector('[data-doc-input]') : null;

                function syncMarriageDoc() {
                    if (!marriageDocBlock) return;
                    const isMarried = maritalSelect.value === 'MARRIED';
                    marriageDocBlock.classList.toggle('hidden', !isMarried);
                    if (marriageInput) {
                        const hasExisting = marriageInput.closest('.border')?.querySelector('ul');
                        marriageInput.required = isMarried && !hasExisting;
                    }
                }
                if (maritalSelect) {
                    maritalSelect.addEventListener('change', syncMarriageDoc);
                    syncMarriageDoc();
                }

                form.querySelectorAll('[data-doc-input]').forEach(function (input) {
                    const hasExisting = input.closest('.border')?.querySelector('ul');
                    if (input.dataset.docRequired === '1' && !hasExisting) {
                        input.required = true;
                    }
                });

                function formatFileSize(bytes) {
                    if (bytes < 1024) return bytes + ' B';
                    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
                }

                function renderFilePreview(previewContainer, files) {
                    previewContainer.innerHTML = '';

                    Array.from(files).forEach(function (file) {
                        const card = document.createElement('div');
                        card.className = 'relative w-20 border border-[#D8DBDE] rounded-md overflow-hidden bg-white cursor-pointer hover:border-[#0B2540] transition-colors';

                        const thumbWrap = document.createElement('div');
                        thumbWrap.className = 'w-full h-16 flex items-center justify-center bg-[#F5F6F7]';

                        const objectUrl = URL.createObjectURL(file);

                        if (file.type.startsWith('image/')) {
                            const img = document.createElement('img');
                            img.className = 'w-full h-full object-cover';
                            img.src = objectUrl;
                            thumbWrap.appendChild(img);
                        } else if (file.type === 'application/pdf') {
                            const icon = document.createElement('span');
                            icon.textContent = 'PDF';
                            icon.className = 'text-[11px] font-bold text-[#A9822C]';
                            thumbWrap.appendChild(icon);
                        } else {
                            const icon = document.createElement('span');
                            icon.textContent = 'FILE';
                            icon.className = 'text-[10px] font-bold text-[#6B7785]';
                            thumbWrap.appendChild(icon);
                        }

                        const nameLabel = document.createElement('p');
                        nameLabel.className = 'text-[9px] text-[#6B7785] px-1 py-1 truncate';
                        nameLabel.title = file.name;
                        nameLabel.textContent = file.name;

                        const sizeLabel = document.createElement('p');
                        sizeLabel.className = 'text-[9px] text-[#9AA2AA] px-1 pb-1';
                        sizeLabel.textContent = formatFileSize(file.size);

                        card.appendChild(thumbWrap);
                        card.appendChild(nameLabel);
                        card.appendChild(sizeLabel);

                        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
                        const isValid = allowedTypes.includes(file.type) && file.size <= 5 * 1024 * 1024;

                        if (!allowedTypes.includes(file.type)) {
                            card.classList.add('border-red-400');
                            const warn = document.createElement('p');
                            warn.className = 'text-[9px] text-red-600 px-1 pb-1';
                            warn.textContent = 'Invalid type';
                            card.appendChild(warn);
                        } else if (file.size > 5 * 1024 * 1024) {
                            card.classList.add('border-red-400');
                            const warn = document.createElement('p');
                            warn.className = 'text-[9px] text-red-600 px-1 pb-1';
                            warn.textContent = 'Too large';
                            card.appendChild(warn);
                        }

                        if (isValid) {
                            card.addEventListener('click', function () {
                                openDocPreview(objectUrl, file.type, file.name);
                            });
                        }

                        previewContainer.appendChild(card);
                    });
                }

                form.querySelectorAll('[data-doc-input]').forEach(function (input) {
                    const previewContainer = input.parentElement.querySelector('[data-doc-preview]');
                    input.addEventListener('change', function () {
                        if (!input.files || input.files.length === 0) {
                            previewContainer.innerHTML = '';
                            return;
                        }
                        renderFilePreview(previewContainer, input.files);
                    });
                });

                const modal = document.getElementById('docPreviewModal');
                const modalBody = document.getElementById('docPreviewBody');
                const modalName = document.getElementById('docPreviewName');
                const modalClose = document.getElementById('docPreviewClose');

                function openDocPreview(url, mime, name) {
                    modalName.textContent = name;
                    modalBody.innerHTML = '';

                    if (mime.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = url;
                        img.alt = name;
                        img.className = 'max-w-full max-h-[70vh] object-contain rounded';
                        modalBody.appendChild(img);
                    } else if (mime === 'application/pdf') {
                        const iframe = document.createElement('iframe');
                        iframe.src = url;
                        iframe.className = 'w-full h-[70vh] rounded bg-white';
                        modalBody.appendChild(iframe);
                    } else {
                        const p = document.createElement('p');
                        p.className = 'text-sm text-[#6B7785]';
                        p.textContent = 'Preview not available for this file type.';
                        modalBody.appendChild(p);
                    }

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }

                function closeDocPreview() {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    modalBody.innerHTML = '';
                }

                document.querySelectorAll('[data-view-doc]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        openDocPreview(btn.dataset.docUrl, btn.dataset.docMime, btn.dataset.docName);
                    });
                });

                modalClose.addEventListener('click', closeDocPreview);
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) closeDocPreview();
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') closeDocPreview();
                });

                const DRAFT_KEY = 'personalInfoDraft_' + (form.querySelector('[name="user_id"]')?.value || '');

                function saveDraft() {
                    const data = { _step: currentStep };
                    form.querySelectorAll('input:not([type="file"]), select, textarea').forEach(function (el) {
                        if (!el.name) return;
                        if (el.type === 'radio' || el.type === 'checkbox') {
                            if (el.checked) data[el.name] = el.value;
                        } else {
                            data[el.name] = el.value;
                        }
                    });
                    localStorage.setItem(DRAFT_KEY, JSON.stringify(data));
                }

                function loadDraft() {
                    const raw = localStorage.getItem(DRAFT_KEY);
                    if (!raw) return;
                    let data;
                    try {
                        data = JSON.parse(raw);
                    } catch (e) {
                        return;
                    }
                    Object.keys(data).forEach(function (name) {
                        if (name === '_step') return;
                        const els = form.querySelectorAll('[name="' + name + '"]');
                        els.forEach(function (el) {
                            if (el.type === 'radio' || el.type === 'checkbox') {
                                el.checked = (el.value === data[name]);
                            } else if (!el.value) {
                                el.value = data[name];
                            }
                        });
                    });
                    if (ageInput && birthdateInput.value && !ageInput.value) {
                        birthdateInput.dispatchEvent(new Event('change'));
                    }
                    syncMarriageDoc();
                }

                loadDraft();

                form.addEventListener('input', saveDraft);
                form.addEventListener('change', saveDraft);

                form.addEventListener('submit', function () {
                    localStorage.removeItem(DRAFT_KEY);
                });

                <?php if (!empty($old['marital_status']) || !empty($old['present_address']) || !empty($old['religion'])): ?>
                    showStep(2);
                <?php endif; ?>
            })();
        </script>
    <?php endif; ?>

</body>

</html>
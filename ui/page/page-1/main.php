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

// I-lock ang form kapag PENDING pa ang review — dito na lang after ma-fetch si $existingInfo
$isLocked = !empty($existingInfo['status']) && $existingInfo['status'] === 'PENDING';

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Personal Information</title>
    <?php include ROOT_PATH . "/link/top.php"; ?>
</head>

<body class="bg-[#F5F6F7] font-['Inter']">

    <?php include ROOT_PATH . "/ui/navigation/top.php"; ?>

    <!-- ==== PAGE CONTENT ==== -->
    <main class="md:pl-64 pt-6 pb-24 md:pb-10 px-4 md:px-8">
        <div class="max-w-2xl mx-auto">

            <p
                class="font-['Barlow_Condensed'] font-semibold text-[13px] tracking-[0.16em] uppercase text-[#A9822C] mb-1">
                Employee 201 File
            </p>
            <h1 class="font-['Barlow_Condensed'] font-bold text-[26px] uppercase text-[#0B2540] mb-1">
                Personal Information
            </h1>
            <p class="text-sm text-[#6B7785] mb-8">
                For <span
                    class="font-semibold text-[#1B2733]"><?= htmlspecialchars($targetUser['first_name'] . ' ' . $targetUser['last_name']) ?></span>
                (<?= htmlspecialchars($targetUser['username']) ?>)
            </p>

            <?php if (!$isLocked): ?>
            <!-- Stepper -->
            <div class="flex items-center mb-8">
                <div class="flex items-center gap-2.5" data-step-indicator="1">
                    <div
                        class="step-circle w-8 h-8 rounded-full bg-[#0B2540] text-white flex items-center justify-center text-xs font-semibold shrink-0">
                        1</div>
                    <span class="step-label text-sm font-semibold text-[#0B2540] hidden sm:inline">Basic Info</span>
                </div>
                <div class="flex-1 h-[2px] bg-[#D8DBDE] mx-3" data-step-line="1"></div>
                <div class="flex items-center gap-2.5" data-step-indicator="2">
                    <div
                        class="step-circle w-8 h-8 rounded-full bg-[#D8DBDE] text-[#6B7785] flex items-center justify-center text-xs font-semibold shrink-0">
                        2</div>
                    <span class="step-label text-sm font-medium text-[#9AA2AA] hidden sm:inline">Additional Info</span>
                </div>
                <div class="flex-1 h-[2px] bg-[#D8DBDE] mx-3" data-step-line="2"></div>
                <div class="flex items-center gap-2.5" data-step-indicator="3">
                    <div
                        class="step-circle w-8 h-8 rounded-full bg-[#D8DBDE] text-[#6B7785] flex items-center justify-center text-xs font-semibold shrink-0">
                        3</div>
                    <span class="step-label text-sm font-medium text-[#9AA2AA] hidden sm:inline">Documents</span>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['form_error'])): ?>
                <p class="mb-5 text-sm text-red-600 bg-red-50 border border-red-200 rounded-md px-3.5 py-2.5">
                    <?= htmlspecialchars($_SESSION['form_error']) ?>
                </p>
                <?php unset($_SESSION['form_error']); ?>
            <?php endif; ?>

            <!-- ==== Review Status Banner ==== -->
            <?php if (!empty($existingInfo['status'])): ?>
                <?php if ($existingInfo['status'] === 'PENDING'): ?>
                    <p class="mb-5 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-3.5 py-2.5">
                        Your 201 File is pending review by HR. You cannot make changes while it is under review.
                    </p>
                <?php elseif ($existingInfo['status'] === 'APPROVED'): ?>
                    <p class="mb-5 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3.5 py-2.5">
                        Your 201 File has been verified and approved.
                    </p>
                <?php elseif ($existingInfo['status'] === 'REJECTED'): ?>
                    <p class="mb-5 text-sm text-red-600 bg-red-50 border border-red-200 rounded-md px-3.5 py-2.5">
                        Your 201 File was rejected<?= !empty($existingInfo['review_notes']) ? ': ' . htmlspecialchars($existingInfo['review_notes']) : '.' ?>
                        Please review and resubmit.
                    </p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($isLocked): ?>

                <!-- ==== LOCKED VIEW (read-only, walang edit habang PENDING) ==== -->
                <div class="bg-white border border-black/5 rounded-xl p-6 md:p-8">
                    <p class="text-sm text-[#6B7785] mb-6">
                        Your submitted information is being reviewed by HR. You'll be able to make changes again once
                        it has been approved or rejected.
                    </p>

                    <div class="space-y-6">

                        <!-- SECTION: Personal Details -->
                        <div>
                            <p class="text-[11px] font-bold tracking-[0.1em] uppercase text-[#0B2540] mb-3 pb-2 border-b border-[#E8EAEC] flex items-center gap-2">
                                <i class="fa-solid fa-id-card text-[#A9822C]"></i> Personal Details
                            </p>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-4 text-sm">
                                <div class="col-span-2">
                                    <p class="text-[11px] font-semibold tracking-[0.06em] uppercase text-[#9AA2AA] mb-1 flex items-center gap-1.5">
                                        <i class="fa-solid fa-user text-black w-4"></i> Full Name
                                    </p>
                                    <p class="text-[#1B2733] font-medium">
                                        <?= htmlspecialchars(trim(($existingInfo['first_name'] ?? '') . ' ' . ($existingInfo['middle_name'] ?? '') . ' ' . ($existingInfo['last_name'] ?? '') . ' ' . ($existingInfo['extension_name'] ?? ''))) ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold tracking-[0.06em] uppercase text-[#9AA2AA] mb-1 flex items-center gap-1.5">
                                        <i class="fa-solid fa-cake-candles text-black w-4"></i> Birthdate / Age
                                    </p>
                                    <p class="text-[#1B2733] font-medium">
                                        <?= htmlspecialchars($existingInfo['birthdate'] ?? '') ?>
                                        (<?= htmlspecialchars($existingInfo['age'] ?? '') ?>)
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold tracking-[0.06em] uppercase text-[#9AA2AA] mb-1 flex items-center gap-1.5">
                                        <i class="fa-solid fa-venus-mars text-black w-4"></i> Gender
                                    </p>
                                    <p class="text-[#1B2733] font-medium"><?= htmlspecialchars($existingInfo['gender'] ?? '') ?></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold tracking-[0.06em] uppercase text-[#9AA2AA] mb-1 flex items-center gap-1.5">
                                        <i class="fa-solid fa-ring text-black w-4"></i> Marital Status
                                    </p>
                                    <p class="text-[#1B2733] font-medium"><?= htmlspecialchars($existingInfo['marital_status'] ?? '') ?></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold tracking-[0.06em] uppercase text-[#9AA2AA] mb-1 flex items-center gap-1.5">
                                        <i class="fa-solid fa-location-dot text-black w-4"></i> Birthplace
                                    </p>
                                    <p class="text-[#1B2733] font-medium"><?= htmlspecialchars($existingInfo['birthplace'] ?? '') ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION: Background -->
                        <div>
                            <p class="text-[11px] font-bold tracking-[0.1em] uppercase text-[#0B2540] mb-3 pb-2 border-b border-[#E8EAEC] flex items-center gap-2">
                                <i class="fa-solid fa-globe text-[#A9822C]"></i> Background
                            </p>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-4 text-sm">
                                <div>
                                    <p class="text-[11px] font-semibold tracking-[0.06em] uppercase text-[#9AA2AA] mb-1 flex items-center gap-1.5">
                                        <i class="fa-solid fa-place-of-worship text-black w-4"></i> Religion
                                    </p>
                                    <p class="text-[#1B2733] font-medium"><?= htmlspecialchars($existingInfo['religion'] ?? '') ?></p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold tracking-[0.06em] uppercase text-[#9AA2AA] mb-1 flex items-center gap-1.5">
                                        <i class="fa-solid fa-flag text-black w-4"></i> Citizenship
                                    </p>
                                    <p class="text-[#1B2733] font-medium"><?= htmlspecialchars($existingInfo['citizenship'] ?? '') ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION: Address -->
                        <div>
                            <p class="text-[11px] font-bold tracking-[0.1em] uppercase text-[#0B2540] mb-3 pb-2 border-b border-[#E8EAEC] flex items-center gap-2">
                                <i class="fa-solid fa-house text-[#A9822C]"></i> Address
                            </p>
                            <div class="text-sm">
                                <p class="text-[11px] font-semibold tracking-[0.06em] uppercase text-[#9AA2AA] mb-1">
                                    Present Complete Address
                                </p>
                                <p class="text-[#1B2733] font-medium"><?= htmlspecialchars($existingInfo['present_address'] ?? '') ?></p>
                            </div>
                        </div>

                    </div>

                    <?php if (!empty($uploadedDocs)): ?>
                        <div class="mt-6 pt-6 border-t border-[#E8EAEC]">
                            <details class="group/main" open>
                                <summary
                                    class="flex items-center justify-between gap-2 cursor-pointer select-none list-none mb-3">
                                    <span class="text-[11px] font-semibold tracking-[0.08em] uppercase text-[#9AA2AA]">
                                        Submitted Documents
                                        <span class="text-[#6B7785] normal-case font-medium">(<?= array_sum(array_map('count', $uploadedDocs)) ?> files)</span>
                                    </span>
                                    <span class="flex items-center gap-1 text-[11px] font-semibold text-[#0B2540]">
                                        <span class="group-open/main:hidden">Show all</span>
                                        <span class="hidden group-open/main:inline">Hide</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transition-transform group-open/main:rotate-180"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </span>
                                </summary>

                                <div class="space-y-2">
                                    <?php foreach ($uploadedDocs as $docKey => $docList): ?>
                                        <details class="group border border-[#E8EAEC] rounded-md overflow-hidden">
                                            <summary
                                                class="flex items-center justify-between gap-2 px-3.5 py-2.5 text-[13px] font-semibold text-[#1B2733] bg-[#F5F6F7] cursor-pointer select-none list-none">
                                                <span class="flex items-center gap-2">
                                                    <?= htmlspecialchars($documentTypes[$docKey]['label'] ?? ucwords(str_replace('_', ' ', $docKey))) ?>
                                                    <span
                                                        class="text-[10px] font-semibold text-[#6B7785] bg-white border border-[#D8DBDE] rounded-full px-2 py-0.5">
                                                        <?= count($docList) ?>
                                                    </span>
                                                </span>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#9AA2AA] transition-transform group-open:rotate-180 shrink-0"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </summary>
                                            <ul class="space-y-1.5 px-3.5 py-3 bg-white">
                                                <?php foreach ($docList as $file): ?>
                                                    <li
                                                        class="flex items-center justify-between gap-2 text-[13px] text-[#4B5866] bg-[#F5F6F7] rounded px-2.5 py-1.5">
                                                        <span
                                                            class="truncate"><?= htmlspecialchars($file['original_filename']) ?></span>
                                                        <a href="<?= BASE_URL ?>/page-1-viewdocument?id=<?= (int) $file['id'] ?>"
                                                            target="_blank"
                                                            class="text-[#0B2540] font-semibold hover:underline shrink-0">View</a>
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
                    class="bg-white border border-black/5 rounded-xl p-6 md:p-8" id="personalInfoForm">

                    <input type="hidden" name="user_id" value="<?= (int) $targetUser['id'] ?>">

                    <?php include ROOT_PATH . "/ui/page/page-1/step-1.php"; ?>
                    <?php include ROOT_PATH . "/ui/page/page-1/step-2.php"; ?>
                    <?php include ROOT_PATH . "/ui/page/page-1/step-3.php"; ?>

                </form>
            <?php endif; ?>
        </div>
    </main>

    <!-- ==== Document Preview Modal ==== -->
    <div id="docPreviewModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/60 p-4">
        <div class="bg-white rounded-xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-[#E8EAEC]">
                <p id="docPreviewName" class="text-sm font-semibold text-[#1B2733] truncate pr-4"></p>
                <button type="button" id="docPreviewClose"
                    class="text-[#6B7785] hover:text-[#1B2733] text-xl leading-none shrink-0">&times;</button>
            </div>
            <div id="docPreviewBody" class="flex-1 overflow-auto bg-[#F5F6F7] flex items-center justify-center p-4">
            </div>
        </div>
    </div>

    <!-- ==== Toast Notification ==== -->
    <div id="toastNotification"
        class="hidden fixed top-5 right-5 z-[60] max-w-sm w-full bg-white border border-green-200 rounded-lg shadow-lg px-4 py-3.5 flex items-start gap-3 translate-x-4 opacity-0 transition-all duration-300">
        <div class="w-5 h-5 rounded-full bg-green-100 text-green-700 flex items-center justify-center shrink-0 mt-0.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        </div>
        <p class="text-sm text-[#1B2733] font-medium flex-1">201 File information saved successfully.</p>
        <button type="button" id="toastClose" class="text-[#9AA2AA] hover:text-[#1B2733] shrink-0 leading-none">&times;</button>
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
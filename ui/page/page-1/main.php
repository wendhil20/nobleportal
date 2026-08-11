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

// Kunin kung MARRIED ba (kung meron nang naka-save na info) + review status + reference number
$stmt = $conn->prepare("SELECT reference_number, marital_status, status, review_notes, first_name, middle_name, last_name, extension_name, birthdate, age, gender, birthplace, present_address, religion, citizenship FROM nobleuser_employee_information WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $targetUserId);
$stmt->execute();
$existingInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ==== Kunin ang mga document na na-flag ng HR bilang "needs re-upload" ====

$flaggedDocs = [];
$stmt = $conn->prepare("SELECT document_type, reason FROM nobleuser_document_flags WHERE user_id = ? AND resolved_at IS NULL");
$stmt->bind_param("i", $targetUserId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $flaggedDocs[$row['document_type']] = $row['reason'];
}
$stmt->close();

$isLocked = !empty($existingInfo['status']) && (
    in_array($existingInfo['status'], ['PENDING', 'APPROVED'], true)
    || ($existingInfo['status'] === 'REJECTED' && !empty($flaggedDocs))
);

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

// Gamitin ang tunay na reference number (hal. NHCC-HR2026-0001) kapag meron na
// itong na-generate mula sa unang submission; kung wala pa (hindi pa nag-susumite),
// ipakita na lang ang computed placeholder.
$fileNo = $existingInfo['reference_number'] ?? ('HR-201-' . str_pad($targetUser['id'], 5, '0', STR_PAD_LEFT));
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

            <div id="fileCard" class="bg-[#FCFBF8] border border-[#D9D4C6] rounded-sm shadow-[0_1px_2px_rgba(0,0,0,0.04)]">

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
                        <?php if ($existingInfo['status'] === 'PENDING' && empty($flaggedDocs)): ?>
                            <p class="mb-5 text-sm text-[#8A6D1F] bg-[#FBF5E6] border border-[#E7D8B0] rounded-sm px-3.5 py-2.5">
                                Your 201 File is pending review by HR. You cannot make changes while it is under review.
                            </p>
                        <?php elseif ($existingInfo['status'] === 'PENDING' && !empty($flaggedDocs)): ?>
                            <p class="mb-5 text-sm text-[#8A6D1F] bg-[#FBF5E6] border border-[#E7D8B0] rounded-sm px-3.5 py-2.5">
                                HR has requested a re-upload for one or more documents (see below). Your other information
                                remains on file and does not need to be re-entered.
                            </p>
                        <?php elseif ($existingInfo['status'] === 'REJECTED'): ?>
                            <p class="mb-5 text-sm text-[#A32D2D] bg-[#FBEEEE] border border-[#EACACA] rounded-sm px-3.5 py-2.5">
                                Your 201 File was
                                rejected<?= !empty($existingInfo['review_notes']) ? ': ' . htmlspecialchars($existingInfo['review_notes']) : '.' ?>
                                Please review and resubmit.
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- ==== Documents Needing Re-upload (independent sa overall status — lumalabas kahit locked/approved) ==== -->
                    <?php if (!empty($flaggedDocs)): ?>
                        <div class="mb-6 border border-[#EACACA] bg-[#FBEEEE] rounded-sm overflow-hidden" id="flaggedDocsWrapper">
                            <div class="px-4 py-2.5 border-b border-[#EACACA] flex items-center gap-2" id="flaggedDocsHeader">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" class="text-[#A32D2D] shrink-0"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                                <p class="text-[12.5px] font-semibold text-[#A32D2D] tracking-[0.02em]" id="flaggedDocsCount">
                                    HR requested re-upload for <?= count($flaggedDocs) ?> document<?= count($flaggedDocs) > 1 ? 's' : '' ?>
                                </p>
                            </div>
                            <div class="divide-y divide-[#EACACA]" id="flaggedDocsList">
                                <?php foreach ($flaggedDocs as $flagKey => $flagReason): ?>
                                    <?php
                                        $flagDocMeta = $documentTypes[$flagKey] ?? ['label' => ucwords(str_replace('_', ' ', $flagKey)), 'multiple' => false, 'max' => 1];
                                    ?>
                                    <div class="px-4 py-3" data-flag-doc="<?= htmlspecialchars($flagKey) ?>">
                                        <div class="flex items-start justify-between gap-3 flex-wrap mb-2">
                                            <div>
                                                <p class="text-[13.5px] font-semibold text-[#241F14]"><?= htmlspecialchars($flagDocMeta['label']) ?></p>
                                                <p class="text-[12px] text-[#A32D2D] mt-0.5">Reason: <?= htmlspecialchars($flagReason) ?></p>
                                            </div>
                                        </div>
                                        <form action="<?= BASE_URL ?>/page-1-reupload" method="post" enctype="multipart/form-data"
                                            class="flex flex-col sm:flex-row items-start sm:items-center gap-2" data-reupload-form>
                                            <input type="hidden" name="document_type" value="<?= htmlspecialchars($flagKey) ?>">
                                            <input type="file" name="document<?= !empty($flagDocMeta['multiple']) ? '[]' : '' ?>"
                                                <?= !empty($flagDocMeta['multiple']) ? 'multiple' : '' ?>
                                                accept=".jpg,.jpeg,.png,.webp,.pdf"
                                                required
                                                class="flex-1 w-full text-[12px] text-[#4A4636] file:mr-3 file:py-1.5 file:px-3 file:rounded-sm file:border-0 file:text-[11.5px] file:font-semibold file:bg-[#0B2540] file:text-white hover:file:bg-[#132F52]">
                                            <button type="submit"
                                                class="px-4 py-1.5 bg-[#0B2540] text-white text-[12px] font-semibold rounded-sm hover:bg-[#132F52] transition-colors shrink-0 whitespace-nowrap">
                                                Upload
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($isLocked): ?>

                        <!-- ==== LOCKED VIEW (read-only, walang edit habang PENDING/APPROVED) ==== -->
                        <?php include ROOT_PATH . "/ui/page/page-1/step-4.php"; ?>

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
        <div class="w-5 h-5 rounded-full bg-[#F0F6EF] text-[#1F6B3A] flex items-center justify-center shrink-0 mt-0.5" id="toastIconWrap">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        </div>
        <p id="toastMessage" class="text-sm text-[#241F14] font-medium flex-1">201 File information saved successfully.</p>
        <button type="button" id="toastClose"
            class="text-[#8B8371] hover:text-[#241F14] shrink-0 leading-none">&times;</button>
    </div>

    <!-- ==== AJAX Re-upload (real-time, walang page reload) ==== -->
    <script>
    (function () {
        const toast = document.getElementById('toastNotification');
        const toastMessage = document.getElementById('toastMessage');
        const toastIconWrap = document.getElementById('toastIconWrap');
        const closeBtn = document.getElementById('toastClose');

        function showAjaxToast(message, isError) {
            toastMessage.textContent = message;

            toast.classList.toggle('border-[#EACACA]', !!isError);
            toast.classList.toggle('border-[#CFE0CE]', !isError);
            toastIconWrap.classList.toggle('bg-[#FBEEEE]', !!isError);
            toastIconWrap.classList.toggle('text-[#A32D2D]', !!isError);
            toastIconWrap.classList.toggle('bg-[#F0F6EF]', !isError);
            toastIconWrap.classList.toggle('text-[#1F6B3A]', !isError);

            toast.classList.remove('hidden');
            requestAnimationFrame(function () {
                toast.classList.remove('translate-x-4', 'opacity-0');
            });
            clearTimeout(showAjaxToast._t);
            showAjaxToast._t = setTimeout(hideAjaxToast, 4000);
        }

        function hideAjaxToast() {
            toast.classList.add('translate-x-4', 'opacity-0');
            setTimeout(function () { toast.classList.add('hidden'); }, 300);
        }

        closeBtn.addEventListener('click', hideAjaxToast);

        function bindReuploadForms() {
            document.querySelectorAll('[data-reupload-form]').forEach(function (form) {
                if (form.dataset.bound === '1') return;
                form.dataset.bound = '1';

                form.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const submitBtn = form.querySelector('button[type="submit"]');
                    const fileInput = form.querySelector('input[type="file"]');

                    if (!fileInput.files || fileInput.files.length === 0) {
                        return;
                    }

                    const originalBtnText = submitBtn.textContent;
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Uploading...';

                    const formData = new FormData(form);

                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(function (res) {
                            return res.json().then(function (data) {
                                if (!res.ok || !data.success) {
                                    throw new Error(data.message || 'Upload failed.');
                                }
                                return data;
                            });
                        })
                        .then(function (data) {
                            const docCard = form.closest('[data-flag-doc]');
                            const list = document.getElementById('flaggedDocsList');
                            const wrapper = document.getElementById('flaggedDocsWrapper');
                            const countLabel = document.getElementById('flaggedDocsCount');

                            if (docCard) docCard.remove();

                            if (list && wrapper && countLabel) {
                                const remaining = list.querySelectorAll('[data-flag-doc]').length;
                                if (remaining === 0) {
                                    wrapper.remove();
                                } else {
                                    countLabel.textContent = 'HR requested re-upload for ' + remaining + ' document' + (remaining > 1 ? 's' : '');
                                }
                            }

                            showAjaxToast(data.message || 'Document re-uploaded successfully.');
                        })
                        .catch(function (err) {
                            showAjaxToast(err.message || 'Something went wrong.', true);
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalBtnText;
                        });
                });
            });
        }

        // Pinapalabas para magamit ng ibang script blocks (hal. pagkatapos mag-swap ng #fileCard)
        window.NHCC = window.NHCC || {};
        window.NHCC.showToast = showAjaxToast;
        window.NHCC.bindReuploadForms = bindReuploadForms;

        bindReuploadForms();
    })();
    </script>

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

                // ==== Certification agreement gate + AJAX submit ====
                const agreementCheckbox = document.getElementById('agreement');
                const agreementError = document.getElementById('agreementError');
                const submitBtn = document.getElementById('submitBtn');

                function refreshFileCard() {
                    return fetch(window.location.pathname)
                        .then(function (res) { return res.text(); })
                        .then(function (html) {
                            const parser = new DOMParser();
                            const newDoc = parser.parseFromString(html, 'text/html');
                            const newCard = newDoc.getElementById('fileCard');
                            const oldCard = document.getElementById('fileCard');
                            if (newCard && oldCard) {
                                oldCard.replaceWith(newCard);
                                if (window.NHCC && window.NHCC.bindReuploadForms) {
                                    window.NHCC.bindReuploadForms();
                                }
                            }
                        });
                }

                form.addEventListener('submit', function (e) {
                    e.preventDefault();

                    if (agreementCheckbox && !agreementCheckbox.checked) {
                        agreementError.classList.remove('hidden');
                        agreementCheckbox.closest('.border').classList.add('border-red-400');
                        agreementCheckbox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        return;
                    }

                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }

                    const originalBtnText = submitBtn.textContent;
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Submitting...';

                    const formData = new FormData(form);

                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(function (res) {
                            return res.json().then(function (data) {
                                if (!res.ok || !data.success) {
                                    throw new Error(data.message || 'Submission failed.');
                                }
                                return data;
                            });
                        })
                        .then(function (data) {
                            localStorage.removeItem(DRAFT_KEY);
                            return refreshFileCard().then(function () {
                                if (window.NHCC && window.NHCC.showToast) {
                                    window.NHCC.showToast(data.message || '201 File information saved successfully.');
                                }
                            });
                        })
                        .catch(function (err) {
                            if (window.NHCC && window.NHCC.showToast) {
                                window.NHCC.showToast(err.message || 'Something went wrong.', true);
                            }
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalBtnText;
                        });
                });

                if (agreementCheckbox) {
                    agreementCheckbox.addEventListener('change', function () {
                        if (agreementCheckbox.checked) {
                            agreementError.classList.add('hidden');
                            agreementCheckbox.closest('.border').classList.remove('border-red-400');
                        }
                    });
                }
            })();
        </script>
    <?php endif; ?>

</body>

</html>
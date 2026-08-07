<?php
//mainview.php — HR 201 File: Single Employee View (read-only + review actions)
include ROOT_PATH . "/network/connect.php";
include ROOT_PATH . "/controlpanel/auth/role/auth_guard.php";

requireAccess('hr', 'head');

$targetUserId = (int) ($_GET['id'] ?? 0);

if ($targetUserId <= 0) {
    header("Location: " . BASE_URL . "/hrpage-1");
    exit;
}

$stmt = $conn->prepare("SELECT id, first_name, last_name, username FROM nobleuserlist WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $targetUserId);
$stmt->execute();
$targetUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$targetUser) {
    header("Location: " . BASE_URL . "/hrpage-1");
    exit;
}

$documentTypes = require ROOT_PATH . "/ui/page/page-1/backend/document_types.php";

$stmt = $conn->prepare("SELECT * FROM nobleuser_employee_information WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $targetUserId);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();
$stmt->close();

$uploadedDocs = [];
$stmt = $conn->prepare("SELECT id, document_type, original_filename, mime_type, file_size, uploaded_at FROM nobleuser_employee_documents WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $targetUserId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $uploadedDocs[$row['document_type']][] = $row;
}
$stmt->close();

function field($label, $value)
{
    echo '<div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8] last:border-b-0">';
    echo '<p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">' . htmlspecialchars($label) . '</p>';
    echo '<p class="text-[13.5px] text-[#241F14] leading-snug">' . ($value !== '' && $value !== null ? htmlspecialchars($value) : '<span class="text-[#B7AF9C] italic">Not provided</span>') . '</p>';
    echo '</div>';
}

$totalDocTypes = 0;
$submittedDocTypes = 0;
if (!empty($documentTypes)) {
    foreach ($documentTypes as $key => $doc) {
        if ($key === 'marriage_certificate' && ($info['marital_status'] ?? '') !== 'MARRIED') continue;
        $totalDocTypes++;
        if (!empty($uploadedDocs[$key])) $submittedDocTypes++;
    }
}

$today = date('F j, Y');
$fileNo = 'HR-201-' . str_pad($targetUser['id'], 5, '0', STR_PAD_LEFT);
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
        <div class="max-w-5xl mx-auto">

            <a href="<?= BASE_URL ?>/hrpage-1" class="inline-flex items-center gap-1.5 text-[12.5px] text-[#6B6350] hover:text-[#0B2540] font-medium mb-4 tracking-[0.02em]">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                RETURN TO EMPLOYEE REGISTRY
            </a>

            <!-- ==== Document sheet ==== -->
            <div class="bg-[#FCFBF8] border border-[#D9D4C6] rounded-sm shadow-[0_1px_2px_rgba(0,0,0,0.04)]">

                <!-- Letterhead -->
                <div class="border-b-2 border-[#0B2540] px-6 md:px-10 pt-7 pb-5">
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div>
                            <p class="text-[10.5px] font-semibold tracking-[0.24em] uppercase text-[#A9822C] mb-1.5">
                                Human Resources Department
                            </p>
                            <h1 class="font-serif font-normal text-[26px] md:text-[30px] text-[#0B2540] leading-tight">
                                Employee 201 File
                            </h1>
                            <p class="text-[12.5px] text-[#6B6350] mt-1">
                                Official personnel record and document dossier
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-[10px] tracking-[0.1em] uppercase text-[#8B8371]">File No.</p>
                            <p class="text-[13.5px] font-semibold text-[#241F14] font-mono">Ref: <?= htmlspecialchars($fileNo) ?></p>
                            <p class="text-[10px] tracking-[0.1em] uppercase text-[#8B8371] mt-2">Generated</p>
                            <p class="text-[12.5px] text-[#241F14]"><?= htmlspecialchars($today) ?></p>
                        </div>
                    </div>

                    <div class="mt-5 pt-5 border-t border-dashed border-[#D9D4C6] flex items-center justify-between flex-wrap gap-3">
                        <div>
                            <p class="text-[10px] tracking-[0.1em] uppercase text-[#8B8371] mb-0.5">Employee Name</p>
                            <p class="font-serif text-[19px] text-[#0B2540]">
                                <?= htmlspecialchars($targetUser['last_name'] . ', ' . $targetUser['first_name']) ?>
                            </p>
                            <p class="text-[12px] text-[#8B8371] mt-0.5"><?= htmlspecialchars($targetUser['username']) ?></p>
                        </div>

                        <?php
                        $stampStyles = [
                            'PENDING'  => ['Under Review', 'text-[#8A6D1F] border-[#8A6D1F]'],
                            'APPROVED' => ['Approved', 'text-[#1F6B3A] border-[#1F6B3A]'],
                            'REJECTED' => ['Rejected', 'text-[#A32D2D] border-[#A32D2D]'],
                        ];
                        $st = $info['status'] ?? 'PENDING';
                        [$stampLabel, $stampColor] = $stampStyles[$st] ?? $stampStyles['PENDING'];
                        ?>
                        <?php if ($info): ?>
                        <div class="border-2 <?= $stampColor ?> rounded-sm px-4 py-1.5 rotate-[-2deg] select-none">
                            <p class="font-serif font-bold text-[13px] tracking-[0.12em] uppercase <?= $stampColor ?>"><?= htmlspecialchars($stampLabel) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php if (isset($_GET['reviewed'])): ?>
                <div class="mx-6 md:mx-10 mt-5 flex items-center gap-2 text-[13px] font-medium text-[#1F6B3A] bg-[#F0F6EF] border border-[#CFE0CE] px-4 py-2.5">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                    Review has been recorded on file.
                </div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['review_error'])): ?>
                <div class="mx-6 md:mx-10 mt-5 flex items-center gap-2 text-[13px] font-medium text-[#A32D2D] bg-[#FBEEEE] border border-[#EACACA] px-4 py-2.5">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
                    <?= htmlspecialchars($_SESSION['review_error']) ?>
                </div>
                <?php unset($_SESSION['review_error']); ?>
            <?php endif; ?>

            <?php if (!$info): ?>
                <div class="px-6 md:px-10 py-16 text-center">
                    <p class="text-[14px] text-[#6B6350] italic">No 201 File information has been submitted by this employee at this time.</p>
                </div>
            <?php else: ?>

                <!-- Body -->
                <div class="px-6 md:px-10 py-7 grid grid-cols-1 lg:grid-cols-5 gap-8">

                    <!-- LEFT: Personal Particulars -->
                    <div class="lg:col-span-2 flex flex-col gap-7">

                        <div>
                            <p class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C] mb-3 pb-2 border-b-2 border-[#0B2540]">
                                I. Personal Particulars
                            </p>
                            <div class="flex flex-col">
                                <?php field('First Name', $info['first_name']); ?>
                                <?php field('Middle Name', $info['middle_name']); ?>
                                <?php field('Last Name', $info['last_name']); ?>
                                <?php field('Extension Name', $info['extension_name']); ?>
                                <?php field('Birthdate', $info['birthdate']); ?>
                                <?php field('Age', $info['age']); ?>
                                <?php field('Gender', $info['gender']); ?>
                                <?php field('Birthplace', $info['birthplace']); ?>
                                <?php field('Marital Status', $info['marital_status']); ?>
                                <?php field('Religion', $info['religion']); ?>
                                <?php field('Citizenship', $info['citizenship']); ?>
                                <?php field('Present Complete Address', $info['present_address']); ?>
                            </div>
                        </div>

                        <?php if ($st === 'PENDING'): ?>
                        <div>
                            <p class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C] mb-3 pb-2 border-b-2 border-[#0B2540]">
                                II. HR Endorsement
                            </p>
                            <form action="<?= BASE_URL ?>/hr-approved" method="post" class="flex flex-col gap-3">
                                <input type="hidden" name="user_id" value="<?= (int) $targetUser['id'] ?>">
                                <div>
                                    <label class="block text-[10.5px] font-semibold tracking-[0.08em] uppercase text-[#8B8371] mb-1.5">Remarks</label>
                                    <textarea name="notes" rows="3" placeholder="Required if rejecting this file"
                                        class="w-full bg-white border border-[#D9D4C6] rounded-sm px-3 py-2 text-[13px] outline-none focus:border-[#0B2540] resize-none"></textarea>
                                </div>
                                <div class="flex gap-2.5 pt-1">
                                    <button type="submit" name="action" value="approve"
                                        class="flex-1 px-4 py-2.5 bg-[#0B2540] text-white font-serif font-semibold text-[13px] tracking-[0.03em] rounded-sm hover:bg-[#132F52] transition-colors">
                                        Approve File
                                    </button>
                                    <button type="submit" name="action" value="reject"
                                        class="flex-1 px-4 py-2.5 bg-transparent text-[#A32D2D] border border-[#A32D2D] font-serif font-semibold text-[13px] tracking-[0.03em] rounded-sm hover:bg-[#FBEEEE] transition-colors">
                                        Reject File
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php elseif (!empty($info['review_notes'])): ?>
                        <div>
                            <p class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C] mb-3 pb-2 border-b-2 border-[#0B2540]">
                                II. HR Endorsement
                            </p>
                            <p class="text-[12.5px] text-[#4A4636] leading-relaxed bg-[#F5F3EC] border-l-2 border-[#A9822C] pl-3 py-2">
                                <?= htmlspecialchars($info['review_notes']) ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- RIGHT: Document Checklist -->
                    <div class="lg:col-span-3">
                        <div class="flex items-baseline justify-between mb-3 pb-2 border-b-2 border-[#0B2540]">
                            <p class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C]">
                                III. Document Checklist
                            </p>
                            <p class="text-[11px] font-semibold text-[#8B8371] font-mono"><?= $submittedDocTypes ?> / <?= $totalDocTypes ?> filed</p>
                        </div>

                        <table class="w-full border-collapse">
                            <tbody>
                            <?php $i = 0; foreach ($documentTypes as $key => $doc):
                                if ($key === 'marriage_certificate' && ($info['marital_status'] ?? '') !== 'MARRIED') continue;
                                $files = $uploadedDocs[$key] ?? [];
                                $hasFiles = !empty($files);
                                $i++;
                                ?>
                                <tr class="border-b border-[#E4E1D8] align-top">
                                    <td class="py-2.5 pr-2 text-[11px] font-mono text-[#B7AF9C] w-6 whitespace-nowrap"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></td>
                                    <td class="py-2.5 pr-3 text-[13px] text-[#241F14] font-medium"><?= htmlspecialchars($doc['label']) ?></td>
                                    <td class="py-2.5 pr-3 text-right w-[110px] whitespace-nowrap">
                                        <?php if ($hasFiles): ?>
                                            <span class="inline-flex items-center gap-1 text-[10.5px] font-bold text-[#1F6B3A]">
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                                FILED (<?= count($files) ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-[10.5px] font-bold text-[#B7AF9C] tracking-[0.04em]">— NONE —</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-2.5 text-right w-16">
                                        <?php if ($hasFiles): ?>
                                            <a href="<?= BASE_URL ?>/hr-viewdocument?id=<?= (int) $files[0]['id'] ?>" target="_blank"
                                                class="text-[11px] font-semibold text-[#0B2540] hover:text-[#A9822C] underline underline-offset-2">
                                                View
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if ($hasFiles && count($files) > 1): ?>
                                <tr class="border-b border-[#E4E1D8]">
                                    <td></td>
                                    <td colspan="3" class="pb-2.5">
                                        <ul class="space-y-1">
                                            <?php foreach (array_slice($files, 1) as $file): ?>
                                                <li class="flex items-center justify-between text-[11px] text-[#6B6350]">
                                                    <span class="truncate"><?= htmlspecialchars($file['original_filename']) ?></span>
                                                    <a href="<?= BASE_URL ?>/hr-viewdocument?id=<?= (int) $file['id'] ?>" target="_blank" class="font-semibold text-[#0B2540] hover:text-[#A9822C] underline underline-offset-2 shrink-0 ml-3">View</a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>

            <?php endif; ?>

                <!-- Footer -->
                <div class="border-t border-[#D9D4C6] px-6 md:px-10 py-3.5 flex items-center justify-between flex-wrap gap-2">
                    <p class="text-[10px] text-[#B7AF9C] tracking-[0.04em]">This document is system generated and restricted to authorized HR personnel.</p>
                    <p class="text-[10px] text-[#B7AF9C] font-mono"><?= htmlspecialchars($fileNo) ?></p>
                </div>

            </div>

        </div>
    </div>

</body>

</html>
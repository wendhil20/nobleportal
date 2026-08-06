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
    echo '<div>';
    echo '<p class="text-[10px] font-semibold tracking-[0.08em] uppercase text-[#6B7785] mb-0.5">' . htmlspecialchars($label) . '</p>';
    echo '<p class="text-[13px] text-[#1B2733] leading-snug">' . ($value !== '' && $value !== null ? htmlspecialchars($value) : '<span class="text-[#9AA2AA]">—</span>') . '</p>';
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee 201 File</title>
    <?php include ROOT_PATH . '/link/top.php'; ?>
</head>

<body class="bg-[#F5F6F7] font-['Inter']">

    <?php include ROOT_PATH . "/controlpanel/navigation/top.php"; ?>

    <div id="mainContent" class="transition-all duration-300 ease-in-out md:pl-64 pt-6 pb-24 md:pb-10 px-4 md:px-8">
        <div class="max-w-6xl mx-auto">

            <a href="<?= BASE_URL ?>/hrpage-1" class="text-[13px] text-[#6B7785] hover:text-[#0B2540] font-medium mb-3 inline-block">
                 Back to Employee List
            </a>

            <div class="flex items-center justify-between mb-5">
                <div>
                    <p class="font-['Barlow_Condensed'] font-semibold text-[12px] tracking-[0.16em] uppercase text-[#A9822C] mb-0.5">
                        Employee 201 File
                    </p>
                    <h1 class="font-['Barlow_Condensed'] font-bold text-[22px] uppercase text-[#0B2540] leading-none">
                        <?= htmlspecialchars($targetUser['first_name'] . ' ' . $targetUser['last_name']) ?>
                    </h1>
                    <p class="text-xs text-[#6B7785] mt-1">
                        <?= htmlspecialchars($targetUser['username']) ?>
                    </p>
                </div>
            </div>

            <?php if (isset($_GET['reviewed'])): ?>
                <p class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3.5 py-2">
                    Review saved.
                </p>
            <?php endif; ?>
            <?php if (!empty($_SESSION['review_error'])): ?>
                <p class="mb-4 text-sm text-red-600 bg-red-50 border border-red-200 rounded-md px-3.5 py-2">
                    <?= htmlspecialchars($_SESSION['review_error']) ?>
                </p>
                <?php unset($_SESSION['review_error']); ?>
            <?php endif; ?>

            <?php if (!$info): ?>
                <div class="bg-white border border-black/5 rounded-xl p-6 md:p-8 text-center text-[#9AA2AA]">
                    This employee hasn't submitted their 201 File information yet.
                </div>
            <?php else: ?>

            <!-- ==== Two-column layout: left = review + personal info (sticky), right = documents ==== -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 items-start">

                <!-- LEFT COLUMN -->
                <div class="lg:col-span-2 flex flex-col gap-5 lg:sticky lg:top-6">

                    <!-- Review Status -->
                    <div class="bg-white border border-black/5 rounded-xl p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="font-['Barlow_Condensed'] font-bold text-[14px] uppercase text-[#0B2540]">Review Status</h2>
                            <?php
                            $badges = [
                                'PENDING'  => 'text-amber-700 bg-amber-50 border-amber-200',
                                'APPROVED' => 'text-green-700 bg-green-50 border-green-200',
                                'REJECTED' => 'text-red-600 bg-red-50 border-red-200',
                            ];
                            $st = $info['status'] ?? 'PENDING';
                            ?>
                            <span class="text-[10px] font-semibold rounded-full px-2.5 py-1 border <?= $badges[$st] ?? '' ?>">
                                <?= htmlspecialchars($st) ?>
                            </span>
                        </div>

                        <?php if (!empty($info['review_notes'])): ?>
                            <p class="text-xs text-[#6B7785] mb-3">Note: <?= htmlspecialchars($info['review_notes']) ?></p>
                        <?php endif; ?>

                        <?php if ($st === 'PENDING'): ?>
                            <form action="<?= BASE_URL ?>/hr-employee-review" method="post" class="flex flex-col gap-2.5">
                                <input type="hidden" name="user_id" value="<?= (int) $targetUser['id'] ?>">
                                <textarea name="notes" rows="2" placeholder="Notes (required if rejecting)"
                                    class="w-full bg-white border border-[#D8DBDE] rounded-md px-3 py-2 text-[13px] outline-none focus:border-[#0B2540] resize-none"></textarea>
                                <div class="flex gap-2.5">
                                    <button type="submit" name="action" value="approve"
                                        class="flex-1 px-4 py-2 bg-green-700 text-white font-['Barlow_Condensed'] font-bold text-[13px] uppercase tracking-[0.06em] rounded-md hover:bg-green-800">
                                        Approve
                                    </button>
                                    <button type="submit" name="action" value="reject"
                                        class="flex-1 px-4 py-2 bg-red-600 text-white font-['Barlow_Condensed'] font-bold text-[13px] uppercase tracking-[0.06em] rounded-md hover:bg-red-700">
                                        Reject
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- Personal Information -->
                    <div class="bg-white border border-black/5 rounded-xl p-5">
                        <h2 class="font-['Barlow_Condensed'] font-bold text-[14px] uppercase text-[#0B2540] mb-4">
                            Personal Information
                        </h2>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-3">
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
                        </div>
                        <div class="mt-3 pt-3 border-t border-[#F0F1F2]">
                            <?php field('Present Complete Address', $info['present_address']); ?>
                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN: Documents -->
                <div class="lg:col-span-3">
                    <div class="bg-white border border-black/5 rounded-xl p-5">
                        <h2 class="font-['Barlow_Condensed'] font-bold text-[14px] uppercase text-[#0B2540] mb-4">
                            Submitted Documents
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <?php foreach ($documentTypes as $key => $doc):
                                if ($key === 'marriage_certificate' && ($info['marital_status'] ?? '') !== 'MARRIED') continue;
                                $files = $uploadedDocs[$key] ?? [];
                                ?>
                                <div class="border border-[#E8EAEC] rounded-md p-3">
                                    <div class="flex items-center justify-between mb-1.5 gap-2">
                                        <span class="text-[12px] font-semibold text-[#1B2733] truncate">
                                            <?= htmlspecialchars($doc['label']) ?>
                                        </span>
                                        <?php if (!empty($files)): ?>
                                            <span class="text-[10px] font-semibold text-green-700 bg-green-50 border border-green-200 rounded-full px-2 py-0.5 shrink-0">
                                                ✓ <?= count($files) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-[10px] font-semibold text-[#9AA2AA] bg-[#F5F6F7] border border-[#E8EAEC] rounded-full px-2 py-0.5 shrink-0">
                                                None
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($files)): ?>
                                        <ul class="space-y-1">
                                            <?php foreach ($files as $file): ?>
                                                <li class="flex items-center justify-between gap-2 text-[10px] text-[#6B7785] bg-[#F5F6F7] rounded px-2 py-1">
                                                    <span class="truncate"><?= htmlspecialchars($file['original_filename']) ?></span>
                                                    <a href="<?= BASE_URL ?>/hr-viewdocument?id=<?= (int) $file['id'] ?>"
                                                        target="_blank"
                                                        class="text-[#0B2540] font-semibold hover:underline shrink-0">
                                                        View
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>

            <?php endif; ?>

        </div>
    </div>

</body>

</html>
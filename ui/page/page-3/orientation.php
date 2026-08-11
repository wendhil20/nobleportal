<?php
//orientation.php
include ROOT_PATH . "/network/connect.php";

/* =========================================================
   CONFIG — must match controlpanel/hr/page-3/hr-orientation.php
   ========================================================= */
$PHOTO_URL    = BASE_URL . '/uploads/orientation/photos/';
$HANDBOOK_URL = BASE_URL . '/uploads/orientation/handbooks/';

/* =========================================================
   CURRENT EMPLOYEE
   NOTE: adjust the session key below if your login flow stores
   the logged-in user's id under a different name.
   ========================================================= */
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

$stmt = $conn->prepare("SELECT id, first_name, last_name FROM nobleuserlist WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("SELECT * FROM nobleuser_orientation WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$orientation = $stmt->get_result()->fetch_assoc();
$stmt->close();

$hasPhoto    = !empty($orientation['photo_path']);
$hasHandbook = !empty($orientation['handbook_path']);
$isComplete  = $orientation && $hasPhoto && $hasHandbook;

function formatOrientationDate(?string $date): ?string {
    if (!$date) return null;
    $ts = strtotime($date);
    return $ts ? date('F j, Y', $ts) : null;
}
$formattedDate = formatOrientationDate($orientation['orientation_date'] ?? null);

$initials = $employee
    ? strtoupper(mb_substr($employee['first_name'], 0, 1) . mb_substr($employee['last_name'], 0, 1))
    : '';

$refCode = 'EMP-' . str_pad((string) $userId, 6, '0', STR_PAD_LEFT);

/* Handbook: force browser's built-in PDF viewer toolbar (download/print icons)
   to be hidden. This is a soft restriction only — see note in the UI below. */
$handbookViewSrc = $hasHandbook
    ? $HANDBOOK_URL . htmlspecialchars($orientation['handbook_path']) . '#toolbar=0&navpanes=0&scrollbar=1&view=FitH'
    : null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Orientation</title>
    <?php include ROOT_PATH . "/link/top.php"; ?>
    <style>
        /* Best-effort: hide this section entirely if the page is sent to a printer */
        @media print {
            .orientation-record,
            .handbook-viewer-wrap {
                display: none !important;
            }
            .orientation-record::after {
                content: "This record is not available for printing.";
            }
        }
    </style>
</head>

<body class="bg-[#EDEAE1] font-['Inter']">

    <?php include ROOT_PATH . "/ui/navigation/top.php"; ?>

    <!-- ==== PAGE CONTENT ==== -->
    <main class="md:pl-64 pt-6 pb-24 md:pb-10 px-4 md:px-8">
        <div class="max-w-3xl mx-auto">

            <p class="font-['Barlow_Condensed'] font-semibold text-[13px] tracking-[0.16em] uppercase text-[#A9822C] mb-1">
                Onboarding
            </p>
            <h1 class="font-['Barlow_Condensed'] font-bold text-[26px] uppercase text-[#0B2540] mb-1">
                Employee Orientation
            </h1>
            <p class="text-[13px] text-[#6B7785] mb-6 max-w-xl">
                This page shows your official orientation record as kept on file by Human Resources —
                the date you attended orientation, your orientation photo, and a copy of the employee
                handbook that was issued to you.
            </p>

            <?php if (!$orientation): ?>

                <!-- ==== NOT YET ON FILE ==== -->
                <div class="bg-white border border-[#D8DBDE] rounded-md p-10 text-center">
                    <h2 class="font-['Barlow_Condensed'] font-bold text-[17px] uppercase tracking-wide text-[#1B2733] mb-1.5">
                        Record Not Yet on File
                    </h2>
                    <p class="text-[14px] text-[#6B7785] max-w-sm mx-auto">
                        HR hasn't set up your orientation record yet. Your photo and orientation date
                        will appear here once it's on file.
                    </p>
                </div>

            <?php else: ?>

                <!-- ==== ORIENTATION RECORD (formal document) ==== -->
                <div class="orientation-record bg-white border border-[#D8DBDE] rounded-md mb-6 relative overflow-hidden">

                    <!-- letterhead -->
                    <div class="border-b-2 border-[#0B2540] px-6 md:px-8 pt-6 pb-4 flex items-start justify-between gap-4">
                        <div>
                            <p class="font-mono text-[11px] tracking-[0.15em] uppercase text-[#9AA2AA]">
                                Human Resources Department
                            </p>
                            <h2 class="font-['Barlow_Condensed'] font-bold text-[18px] uppercase tracking-wide text-[#0B2540]">
                                Certificate of Orientation on File
                            </h2>
                        </div>
                   
                    </div>

                    <p class="px-6 md:px-8 pt-4 text-[13px] text-[#6B7785] italic">
                        This certifies that the individual named below has attended the required
                        onboarding orientation and that a photo and handbook acknowledgment are
                        recorded in the HR system as indicated.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-6 px-6 md:px-8 py-7">
                        <div class="flex flex-col items-center sm:items-start shrink-0 mx-auto sm:mx-0">
                            <div class="w-28 h-32 rounded-sm overflow-hidden bg-[#F5F6F7] border border-[#D8DBDE] flex items-center justify-center">
                                <?php if ($hasPhoto): ?>
                                    <img src="<?= $PHOTO_URL . htmlspecialchars($orientation['photo_path']) ?>"
                                        alt="Orientation photo" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <span class="text-[22px] font-semibold text-[#9AA2AA]"><?= htmlspecialchars($initials) ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-[10px] text-[#9AA2AA] mt-1.5 text-center sm:text-left max-w-[7rem]">
                                <?= $hasPhoto ? 'Photo taken on orientation day' : 'Photo not yet uploaded' ?>
                            </p>
                        </div>

                        <dl class="flex-1 grid grid-cols-1 gap-5 text-center sm:text-left">
                            <div>
                                <dt class="text-[11px] font-semibold tracking-[0.1em] uppercase text-[#9AA2AA] mb-1">Name</dt>
                                <dd class="text-[17px] font-medium text-[#1B2733]">
                                    <?= htmlspecialchars(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? '')) ?>
                                </dd>
                                <p class="text-[11px] text-[#9AA2AA] mt-0.5">Full name as recorded in the employee system</p>
                            </div>
                            <div>
                                <dt class="text-[11px] font-semibold tracking-[0.1em] uppercase text-[#9AA2AA] mb-1">Date of Orientation</dt>
                                <dd class="text-[17px] font-medium text-[#1B2733]">
                                    <?= $formattedDate ? htmlspecialchars($formattedDate) : '&mdash;' ?>
                                </dd>
                                <p class="text-[11px] text-[#9AA2AA] mt-0.5">
                                    The day you attended orientation and received company policies and HRIS usage instructions
                                </p>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- ==== HANDBOOK (view-only) ==== -->
                <div class="bg-white border border-[#D8DBDE] rounded-md px-6 md:px-8 py-5 mb-5">
                    <h3 class="text-[11px] font-semibold tracking-[0.1em] uppercase text-[#6B7785] mb-1">
                        Employee Handbook
                    </h3>
                    <p class="text-[12px] text-[#9AA2AA] mb-3">
                        Reference copy of the handbook issued during orientation. Viewable on this page only.
                    </p>

                    <?php if ($hasHandbook): ?>
                        <div class="handbook-viewer-wrap border border-[#D8DBDE] rounded-sm overflow-hidden"
                             oncontextmenu="return false;">
                            <iframe
                                src="<?= $handbookViewSrc ?>"
                                title="<?= htmlspecialchars($orientation['handbook_original_name'] ?? 'Employee handbook') ?>"
                                class="w-full h-[70vh]"
                                style="border:0;"
                                loading="lazy">
                            </iframe>
                        </div>
                        <p class="text-[11px] text-[#9AA2AA] mt-2">
                            <?= htmlspecialchars($orientation['handbook_original_name'] ?? 'Employee Handbook') ?>
                            &nbsp;•&nbsp; Download and print controls are hidden in this viewer.
                        </p>
                    <?php else: ?>
                        <p class="text-[13px] text-[#9AA2AA]">Not yet uploaded by HR.</p>
                    <?php endif; ?>
                </div>

                <!-- ==== NOTES ==== -->
                <?php if (!empty($orientation['notes'])): ?>
                    <div class="bg-white border border-[#D8DBDE] rounded-md px-6 md:px-8 py-5">
                        <h3 class="text-[11px] font-semibold tracking-[0.1em] uppercase text-[#6B7785] mb-1">
                            Notes from HR
                        </h3>
                        <p class="text-[11px] text-[#9AA2AA] mb-2">
                            Additional remarks left by HR staff regarding your orientation record.
                        </p>
                        <p class="text-[14px] text-[#1B2733] whitespace-pre-line leading-relaxed">
                            <?= nl2br(htmlspecialchars($orientation['notes'])) ?>
                        </p>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </div>
    </main>

</body>

</html>
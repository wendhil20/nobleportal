<?php
//resign.php
include ROOT_PATH . "/network/connect.php";
include ROOT_PATH . "/ui/page/page-4/backend/employee-resignation-actions.php";

$userId = $_SESSION['user_id'] ?? 0;

if ($userId <= 0) {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM nobleuser_resignation WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$resignation = $stmt->get_result()->fetch_assoc();
$stmt->close();

$DOCUMENT_URL = BASE_URL . '/uploads/resignation/documents/';

$daysLeft = null;
if ($resignation && in_array($resignation['status'], ['RENDERING', 'READY_FOR_COMPLETION'], true)) {
    $end = strtotime($resignation['rendering_end_date']);
    $today = strtotime(date('Y-m-d'));
    $daysLeft = max(0, (int) ceil(($end - $today) / 86400));
}

function formatDisplayDate(?string $date): ?string
{
    if (!$date) return null;
    $ts = strtotime($date);
    return $ts ? date('F j, Y', $ts) : null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resignation</title>
    <?php include ROOT_PATH . "/link/top.php"; ?>
</head>

<body class="bg-[#EDEAE1] font-['Inter']">

    <?php include ROOT_PATH . "/ui/navigation/top.php"; ?>

    <main class="md:pl-64 pt-6 pb-24 md:pb-10 px-4 md:px-8">
        <div class="max-w-2xl mx-auto">

            <p class="font-['Barlow_Condensed'] font-semibold text-[13px] tracking-[0.16em] uppercase text-[#A9822C] mb-1">
                Employment
            </p>
            <h1 class="font-['Barlow_Condensed'] font-bold text-[26px] uppercase text-[#0B2540] mb-6">
                Resignation
            </h1>

            <?php if (!$resignation || $resignation['status'] === 'REJECTED'): ?>

                <?php if ($resignation && $resignation['status'] === 'REJECTED'): ?>
                    <div class="bg-white border border-[#E3B5B5] rounded-md p-6 mb-5">
                        <h2 class="font-['Barlow_Condensed'] font-bold text-[15px] uppercase text-[#8C2F2F] mb-1">
                            Previous Request Declined
                        </h2>
                        <?php if (!empty($resignation['rejection_reason'])): ?>
                            <p class="text-[13.5px] text-[#6B7785] mt-1">
                                Reason: <?= htmlspecialchars($resignation['rejection_reason']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- ==== REQUEST FORM ==== -->
                <div class="bg-white border border-[#D8DBDE] rounded-md p-6 md:p-8">
                    <h2 class="font-['Barlow_Condensed'] font-bold text-[17px] uppercase text-[#1B2733] mb-1.5">
                        Submit a Resignation Request
                    </h2>
                    <p class="text-[13.5px] text-[#6B7785] mb-5">
                        This will notify HR of your intent to resign. Once approved, HR will prepare
                        your resignation document and set your rendering period.
                    </p>
                    <form method="POST" action="<?= BASE_URL ?>/resignation-handler">
                        <label class="block text-[11px] font-semibold tracking-[0.1em] uppercase text-[#9AA2AA] mb-1.5">
                            Reason (optional)
                        </label>
                        <textarea name="reason" rows="4"
                            class="w-full border border-[#D8DBDE] rounded-md px-3 py-2.5 text-[13.5px] text-[#1B2733] focus:outline-none focus:ring-1 focus:ring-[#A9822C] mb-5"
                            placeholder="Let HR know your reason for resigning..."></textarea>
                        <button type="submit" name="submit_resignation_request" value="1"
                            class="bg-[#0B2540] text-white text-[13.5px] font-semibold px-5 py-2.5 rounded-md hover:bg-[#0B2540]/90 transition-colors">
                            Submit Request
                        </button>
                    </form>
                </div>

            <?php elseif ($resignation['status'] === 'PENDING'): ?>

                <div class="bg-white border border-[#D8DBDE] rounded-md p-10 text-center">
                    <h2 class="font-['Barlow_Condensed'] font-bold text-[17px] uppercase text-[#1B2733] mb-1.5">
                        Awaiting Admin Review
                    </h2>
                    <p class="text-[14px] text-[#6B7785] max-w-sm mx-auto">
                        Your resignation request was submitted on
                        <?= htmlspecialchars(formatDisplayDate($resignation['requested_at'])) ?>
                        and is currently being reviewed by HR.
                    </p>
                </div>

            <?php else: ?>

                <!-- ==== 3-STEP TRACKER ==== -->
                <?php
                $step1Done = !empty($resignation['document_path']);
                $step2Done = in_array($resignation['status'], ['READY_FOR_COMPLETION', 'RESIGNED'], true);
                $step3Done = $resignation['status'] === 'RESIGNED';
                ?>
                <div class="bg-white border border-[#D8DBDE] rounded-md mb-6">
                    <div class="border-b-2 border-[#0B2540] px-6 md:px-8 pt-6 pb-4">
                        <p class="font-mono text-[11px] tracking-[0.15em] uppercase text-[#9AA2AA]">
                            Human Resources Department
                        </p>
                        <h2 class="font-['Barlow_Condensed'] font-bold text-[18px] uppercase tracking-wide text-[#0B2540]">
                            Resignation Progress
                        </h2>
                    </div>

                    <!-- stepper -->
                    <div class="px-6 md:px-8 pt-6 pb-2">
                        <div class="flex items-start">
                            <!-- Step 1 -->
                            <div class="flex-1 flex flex-col items-center text-center">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-[12px] font-bold
                                    <?= $step1Done ? 'bg-[#1E7B3B] text-white' : 'bg-[#A9822C] text-white' ?>">
                                    <?= $step1Done ? '&#10003;' : '1' ?>
                                </div>
                                <p class="text-[11.5px] font-semibold text-[#1B2733] mt-2">Resignation Document</p>
                                <p class="text-[10.5px] text-[#9AA2AA] mt-0.5">
                                    <?= $step1Done ? 'Uploaded by HR' : 'Waiting for HR' ?>
                                </p>
                            </div>
                            <div class="w-full h-[2px] mt-4 <?= $step1Done ? 'bg-[#1E7B3B]' : 'bg-[#E4E1D8]' ?>"></div>
                            <!-- Step 2 -->
                            <div class="flex-1 flex flex-col items-center text-center">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-[12px] font-bold
                                    <?= $step2Done ? 'bg-[#1E7B3B] text-white' : ($step1Done ? 'bg-[#A9822C] text-white' : 'bg-[#E4E1D8] text-[#9AA2AA]') ?>">
                                    <?= $step2Done ? '&#10003;' : '2' ?>
                                </div>
                                <p class="text-[11.5px] font-semibold text-[#1B2733] mt-2">Rendering Period</p>
                                <p class="text-[10.5px] text-[#9AA2AA] mt-0.5">
                                    <?php if ($step2Done): ?>
                                        Completed
                                    <?php elseif ($step1Done): ?>
                                        <?= $daysLeft ?> day<?= $daysLeft === 1 ? '' : 's' ?> left
                                    <?php else: ?>
                                        Not started
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="w-full h-[2px] mt-4 <?= $step2Done ? 'bg-[#1E7B3B]' : 'bg-[#E4E1D8]' ?>"></div>
                            <!-- Step 3 -->
                            <div class="flex-1 flex flex-col items-center text-center">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-[12px] font-bold
                                    <?= $step3Done ? 'bg-[#1E7B3B] text-white' : ($step2Done ? 'bg-[#A9822C] text-white' : 'bg-[#E4E1D8] text-[#9AA2AA]') ?>">
                                    <?= $step3Done ? '&#10003;' : '3' ?>
                                </div>
                                <p class="text-[11.5px] font-semibold text-[#1B2733] mt-2">Resigned</p>
                                <p class="text-[10.5px] text-[#9AA2AA] mt-0.5">
                                    <?php if ($step3Done): ?>
                                        <?= htmlspecialchars(formatDisplayDate($resignation['resigned_at'])) ?>
                                    <?php elseif ($step2Done): ?>
                                        Awaiting confirmation
                                    <?php else: ?>
                                        Pending
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <?php if ($resignation['status'] === 'APPROVED'): ?>
                        <div class="px-6 md:px-8 pb-6 pt-4">
                            <p class="text-[13px] text-[#6B7785]">
                                Your request has been approved. HR is preparing your resignation
                                document and will set your rendering period shortly.
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if ($step1Done): ?>
                        <div class="border-t border-[#EDEFF1] px-6 md:px-8 py-5">
                            <h3 class="text-[11px] font-semibold tracking-[0.1em] uppercase text-[#6B7785] mb-2">
                                Resignation Document
                            </h3>
                            <a href="<?= $DOCUMENT_URL . htmlspecialchars($resignation['document_path']) ?>" target="_blank"
                                class="text-[13.5px] text-[#0B2540] font-medium hover:underline">
                                <?= htmlspecialchars($resignation['document_original_name'] ?? 'View document') ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($resignation['status'] === 'RENDERING' || $resignation['status'] === 'READY_FOR_COMPLETION'): ?>
                        <div class="border-t border-[#EDEFF1] px-6 md:px-8 py-5">
                            <dl class="grid grid-cols-2 gap-5 text-center sm:text-left">
                                <div>
                                    <dt class="text-[11px] font-semibold tracking-[0.1em] uppercase text-[#9AA2AA] mb-1">Rendering Started</dt>
                                    <dd class="text-[14px] font-medium text-[#1B2733]">
                                        <?= htmlspecialchars(formatDisplayDate($resignation['rendering_start_date'])) ?>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-[11px] font-semibold tracking-[0.1em] uppercase text-[#9AA2AA] mb-1">Last Day</dt>
                                    <dd class="text-[14px] font-medium text-[#1B2733]">
                                        <?= htmlspecialchars(formatDisplayDate($resignation['rendering_end_date'])) ?>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    <?php endif; ?>

                    <?php if ($step3Done): ?>
                        <div class="border-t border-[#EDEFF1] px-6 md:px-8 py-5 bg-[#F9F7F2] rounded-b-md">
                            <p class="text-[13.5px] font-medium text-[#0B2540]">
                                Your resignation has been finalized. Thank you for your service to the company.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

        </div>
    </main>

</body>

</html>
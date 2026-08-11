<?php
//resignrequest.php — HR 201 File: Resign Request
include ROOT_PATH . "/network/connect.php";
include ROOT_PATH . "/controlpanel/auth/role/auth_guard.php";

requireAccess('hr', 'head');

include ROOT_PATH . "/controlpanel/hr/page-4/backend/resignation-actions.php";

$res = $conn->query("SELECT r.*, u.first_name, u.last_name
    FROM nobleuser_resignation r
    JOIN nobleuserlist u ON u.id = r.user_id
    ORDER BY r.created_at DESC");
$requests = $res->fetch_all(MYSQLI_ASSOC);

function formatDisplayDate2(?string $date): ?string
{
    if (!$date) return null;
    $ts = strtotime($date);
    return $ts ? date('M j, Y', $ts) : null;
}

$statusBadge = [
    'PENDING'              => ['bg-[#FBEFDD] text-[#A9822C]', 'Pending Review'],
    'APPROVED'             => ['bg-[#E3ECF7] text-[#2A5C99]', 'Approved — Awaiting Document'],
    'RENDERING'            => ['bg-[#E3ECF7] text-[#2A5C99]', 'Rendering'],
    'READY_FOR_COMPLETION' => ['bg-[#FBEFDD] text-[#A9822C]', 'Ready to Finalize'],
    'RESIGNED'             => ['bg-[#E7F3EA] text-[#1E7B3B]', 'Resigned'],
    'REJECTED'             => ['bg-[#FBE7E7] text-[#8C2F2F]', 'Rejected'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Resign Request</title>
    <?php include ROOT_PATH . '/link/top.php'; ?>
</head>

<body class="bg-[#F5F6F7] font-['Inter']">

    <?php include ROOT_PATH . "/controlpanel/navigation/top.php"; ?>

    <div id="mainContent" class="transition-all duration-300 ease-in-out md:pl-64 pt-6 pb-24 md:pb-10 px-4 md:px-8">
        <div class="max-w-4xl mx-auto">

            <p class="font-['Barlow_Condensed'] font-semibold text-[13px] tracking-[0.16em] uppercase text-[#A9822C] mb-1">
                HR 201 File
            </p>
            <h1 class="font-['Barlow_Condensed'] font-bold text-[26px] uppercase text-[#0B2540] mb-6">
                Employee Resign Requests
            </h1>

            <?php if (empty($requests)): ?>
                <div class="bg-white border border-black/5 rounded-xl p-16 text-center">
                    <p class="text-[14px] text-[#6B7785] font-medium">No resignation requests yet.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($requests as $r):
                        $badge = $statusBadge[$r['status']] ?? ['bg-gray-100 text-gray-600', $r['status']];
                        $daysLeft = null;
                        if (in_array($r['status'], ['RENDERING', 'READY_FOR_COMPLETION'], true)) {
                            $end = strtotime($r['rendering_end_date']);
                            $today = strtotime(date('Y-m-d'));
                            $daysLeft = max(0, (int) ceil(($end - $today) / 86400));
                        }
                        ?>
                        <div class="bg-white border border-black/5 rounded-xl p-5 md:p-6">
                            <div class="flex items-start justify-between gap-3 flex-wrap mb-3">
                                <div>
                                    <p class="text-[15px] font-semibold text-[#1B2733]">
                                        <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>
                                    </p>
                                    <p class="text-[11.5px] text-[#9AA2AA] mt-0.5">
                                        Requested <?= htmlspecialchars(formatDisplayDate2($r['requested_at'])) ?>
                                    </p>
                                </div>
                                <span class="inline-block text-[10px] font-semibold tracking-[0.1em] uppercase px-2.5 py-1 rounded-full <?= $badge[0] ?>">
                                    <?= htmlspecialchars($badge[1]) ?>
                                </span>
                            </div>

                            <?php if (!empty($r['reason'])): ?>
                                <p class="text-[13px] text-[#6B7785] mb-4 leading-relaxed">
                                    "<?= htmlspecialchars($r['reason']) ?>"
                                </p>
                            <?php endif; ?>

                            <?php if ($r['status'] === 'PENDING'): ?>
                                <div class="flex items-center gap-2.5 pt-2 border-t border-[#EDEFF1]">
                                    <form method="POST" action="<?= BASE_URL ?>/admin-resignation-handler">
                                        <input type="hidden" name="approve_id" value="<?= (int) $r['id'] ?>">
                                        <button type="submit"
                                            class="text-[12.5px] font-semibold text-white bg-[#1E7B3B] px-3.5 py-1.5 rounded-md hover:bg-[#1E7B3B]/90 transition-colors">
                                            Approve
                                        </button>
                                    </form>
                                    <button type="button"
                                        onclick="document.getElementById('reject-form-<?= (int) $r['id'] ?>').classList.toggle('hidden')"
                                        class="text-[12.5px] font-semibold text-[#8C2F2F] px-3.5 py-1.5 rounded-md border border-[#E3B5B5] hover:bg-[#FBE7E7] transition-colors">
                                        Reject
                                    </button>
                                </div>
                                <form id="reject-form-<?= (int) $r['id'] ?>" method="POST" action="<?= BASE_URL ?>/admin-resignation-handler" class="hidden mt-3">
                                    <input type="hidden" name="reject_id" value="<?= (int) $r['id'] ?>">
                                    <textarea name="rejection_reason" rows="2" placeholder="Reason for rejection (optional)"
                                        class="w-full border border-[#D8DBDE] rounded-md px-3 py-2 text-[13px] mb-2 focus:outline-none focus:ring-1 focus:ring-[#A9822C]"></textarea>
                                    <button type="submit"
                                        class="text-[12px] font-semibold text-white bg-[#8C2F2F] px-3 py-1.5 rounded-md hover:bg-[#8C2F2F]/90 transition-colors">
                                        Confirm Rejection
                                    </button>
                                </form>

                            <?php elseif ($r['status'] === 'APPROVED'): ?>
                                <form method="POST" action="<?= BASE_URL ?>/admin-resignation-handler" enctype="multipart/form-data"
                                    class="pt-3 border-t border-[#EDEFF1] space-y-3">
                                    <input type="hidden" name="start_rendering_id" value="<?= (int) $r['id'] ?>">
                                    <div>
                                        <label class="block text-[11px] font-semibold tracking-[0.1em] uppercase text-[#9AA2AA] mb-1.5">
                                            Resignation Document (PDF)
                                        </label>
                                        <input type="file" name="resignation_document" accept="application/pdf" required
                                            class="text-[12.5px] text-[#4B5866]">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold tracking-[0.1em] uppercase text-[#9AA2AA] mb-1.5">
                                            Rendering Period (days)
                                        </label>
                                        <input type="number" name="rendering_days" value="30" min="1" max="90"
                                            class="w-24 border border-[#D8DBDE] rounded-md px-2.5 py-1.5 text-[13px] focus:outline-none focus:ring-1 focus:ring-[#A9822C]">
                                    </div>
                                    <button type="submit"
                                        class="text-[12.5px] font-semibold text-white bg-[#0B2540] px-3.5 py-1.5 rounded-md hover:bg-[#0B2540]/90 transition-colors">
                                        Upload &amp; Start Rendering
                                    </button>
                                </form>

                            <?php elseif ($r['status'] === 'RENDERING'): ?>
                                <div class="pt-3 border-t border-[#EDEFF1]">
                                    <p class="text-[13px] text-[#4B5866]">
                                        <span class="font-semibold text-[#0B2540]"><?= $daysLeft ?></span>
                                        day<?= $daysLeft === 1 ? '' : 's' ?> left
                                        &middot; Last day: <?= htmlspecialchars(formatDisplayDate2($r['rendering_end_date'])) ?>
                                    </p>
                                </div>

                            <?php elseif ($r['status'] === 'READY_FOR_COMPLETION'): ?>
                                <div class="pt-3 border-t border-[#EDEFF1] flex items-center justify-between flex-wrap gap-3">
                                    <p class="text-[13px] text-[#A9822C] font-medium">
                                        Rendering period has ended. Ready to finalize.
                                    </p>
                                    <form method="POST" action="<?= BASE_URL ?>/admin-resignation-handler">
                                        <input type="hidden" name="mark_resigned_id" value="<?= (int) $r['id'] ?>">
                                        <button type="submit"
                                            class="text-[12.5px] font-semibold text-white bg-[#1E7B3B] px-3.5 py-1.5 rounded-md hover:bg-[#1E7B3B]/90 transition-colors">
                                            Mark as Resigned
                                        </button>
                                    </form>
                                </div>

                            <?php elseif ($r['status'] === 'RESIGNED'): ?>
                                <p class="pt-3 border-t border-[#EDEFF1] text-[13px] text-[#1E7B3B] font-medium">
                                    Finalized on <?= htmlspecialchars(formatDisplayDate2($r['resigned_at'])) ?>
                                </p>

                            <?php elseif ($r['status'] === 'REJECTED'): ?>
                                <?php if (!empty($r['rejection_reason'])): ?>
                                    <p class="pt-3 border-t border-[#EDEFF1] text-[13px] text-[#8C2F2F]">
                                        Reason: <?= htmlspecialchars($r['rejection_reason']) ?>
                                    </p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

</body>

</html>
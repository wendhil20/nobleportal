<?php
//notification.php — Admin/HR Notification System
include ROOT_PATH . "/network/connect.php";
include ROOT_PATH . "/controlpanel/auth/role/auth_guard.php";


include ROOT_PATH . "/controlpanel/notification/backend/notification-actions.php";

// na ginagamit na sa controlpanel/navigation/top.php).
$adminId       = $_SESSION['admin_id'] ?? 0;
$adminRole     = $_SESSION['admin_role'] ?? '';
$adminPosition = $_SESSION['admin_position'] ?? null;

// ==== Fetch notifications relevant to this admin ====
// NOTE: for_user_id branch is scoped to recipient_type = 'admin' para hindi
// makita ng admin yung mga notification na for_user_id man ay para talaga
// sa employee/user side lang (recipient_type = 'user').
$stmt = $conn->prepare("SELECT id, for_role, for_position, for_user_id, recipient_type, title, message, link, is_read, created_at
    FROM nobleportalnotification
    WHERE (for_user_id = ? AND recipient_type = 'admin')
       OR (
            (for_role IS NOT NULL OR for_position IS NOT NULL)
            AND (for_role IS NULL OR for_role = ?)
            AND (for_position IS NULL OR for_position = ?)
          )
    ORDER BY created_at DESC");
$stmt->bind_param("iss", $adminId, $adminRole, $adminPosition);
$stmt->execute();
$res = $stmt->get_result();
$notifications = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$unreadCount = 0;
foreach ($notifications as $n) {
    if ((int) $n['is_read'] === 0) $unreadCount++;
}

function timeAgo($datetime)
{
    if (!$datetime) return '';
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification System</title>
    <?php include ROOT_PATH . "/link/top.php"; ?>
</head>

<body class="bg-[#F5F6F7] font-['Inter']">

    <?php include ROOT_PATH . "/controlpanel/navigation/top.php"; ?>

    <div id="mainContent" class="transition-all duration-300 ease-in-out md:pl-64 pt-6 pb-24 md:pb-10 px-4 md:px-8">
        <div class="max-w-2xl mx-auto">

            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <div>
                    <p class="font-['Barlow_Condensed'] font-semibold text-[13px] tracking-[0.16em] uppercase text-[#A9822C] mb-1">
                        Settings
                    </p>
                    <h1 class="font-['Barlow_Condensed'] font-bold text-[26px] uppercase text-[#0B2540] leading-none">
                        Notification System
                    </h1>
                </div>

                <?php if ($unreadCount > 0): ?>
                    <form action="<?= BASE_URL ?>/notification" method="post">
                        <button type="submit" name="mark_all_read" value="1"
                            class="text-[12.5px] font-semibold text-[#0B2540] hover:text-[#A9822C] transition-colors">
                            Mark all as read
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="bg-white border border-black/5 rounded-xl overflow-hidden">

                <?php if (empty($notifications)): ?>
                    <div class="py-16 text-center">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#C7CCD1" stroke-width="1.6" class="mx-auto mb-3">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        <p class="text-[14px] text-[#6B7785] font-medium">You have no notifications yet.</p>
                    </div>
                <?php else: ?>
                    <ul class="divide-y divide-[#EDEFF1]">
                        <?php foreach ($notifications as $n):
                            $isUnread = (int) $n['is_read'] === 0;
                            $href = !empty($n['link']) ? htmlspecialchars($n['link']) : null;
                            ?>
                            <li class="relative <?= $isUnread ? 'bg-[#F7F9FC]' : 'bg-white' ?>">
                                <div class="flex items-start gap-3 px-5 py-4">
                                    <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 <?= $isUnread ? 'bg-[#A9822C]' : 'bg-transparent' ?>"></span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="text-[13.5px] <?= $isUnread ? 'font-semibold text-[#1B2733]' : 'font-medium text-[#4B5866]' ?> leading-snug">
                                                <?= htmlspecialchars($n['title'] ?: 'Notification') ?>
                                            </p>
                                            <span class="text-[11px] text-[#9AA2AA] shrink-0 whitespace-nowrap">
                                                <?= htmlspecialchars(timeAgo($n['created_at'])) ?>
                                            </span>
                                        </div>

                                        <?php if (!empty($n['message'])): ?>
                                            <p class="text-[12.5px] text-[#6B7785] mt-1 leading-relaxed">
                                                <?= htmlspecialchars($n['message']) ?>
                                            </p>
                                        <?php endif; ?>

                                        <?php if ($href): ?>
                                            <div class="flex items-center gap-3 mt-2">
                                                <a href="<?= $href ?>"
                                                    <?php if ($isUnread): ?>
                                                        class="mark-read-view text-[11.5px] font-semibold text-[#0B2540] hover:text-[#A9822C] inline-flex items-center gap-1"
                                                        data-mark-id="<?= (int) $n['id'] ?>"
                                                    <?php else: ?>
                                                        class="text-[11.5px] font-semibold text-[#0B2540] hover:text-[#A9822C] inline-flex items-center gap-1"
                                                    <?php endif; ?>>
                                                    View
                                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17L17 7M7 7h10v10"/></svg>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

            </div>

        </div>
    </div>

    <script>
        // Pag click ng "View" sa isang unread notification, i-mark siyang
        // read sa background (keepalive) habang diretso namang nag-nanavigate
        // yung browser papunta sa link — walang preventDefault, walang delay.
        document.querySelectorAll('.mark-read-view').forEach(function (link) {
            link.addEventListener('click', function () {
                const id = this.dataset.markId;

                fetch('<?= BASE_URL ?>/admin-notification', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'mark_read_id=' + encodeURIComponent(id),
                    keepalive: true
                });
            });
        });
    </script>

</body>

</html>
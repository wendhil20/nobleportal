<?php
//notification.php 
include ROOT_PATH . "/network/connect.php";

include ROOT_PATH . "/ui/notification/backend/employee-notification-actions.php";

$userId = $_SESSION['user_id'] ?? 0;

if ($userId <= 0) {
    header("Location: " . BASE_URL . "/login");
    exit;
}

// ==== Fetch notifications relevant to this employee only ====

$stmt = $conn->prepare("SELECT id, for_user_id, recipient_type, title, message, link, is_read, created_at
    FROM nobleportalnotification
    WHERE for_user_id = ? AND recipient_type = 'user'
    ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
$notifications = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$unreadCount = 0;
foreach ($notifications as $n) {
    if ((int) $n['is_read'] === 0)
        $unreadCount++;
}

function timeAgo($datetime)
{
    if (!$datetime)
        return '';
    $diff = time() - strtotime($datetime);
    if ($diff < 60)
        return 'Just now';
    if ($diff < 3600)
        return floor($diff / 60) . 'm ago';
    if ($diff < 86400)
        return floor($diff / 3600) . 'h ago';
    if ($diff < 604800)
        return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <?php include ROOT_PATH . "/link/top.php"; ?>
</head>

<body class="bg-[#EDEAE1] font-['Inter']">

    <?php include ROOT_PATH . "/ui/navigation/top.php"; ?>

    <main class="md:pl-64 pt-6 pb-24 md:pb-10 px-4 md:px-8">
        <div class="max-w-2xl mx-auto">

            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <div>
                    <p class="text-[10.5px] font-semibold tracking-[0.24em] uppercase text-[#A9822C] mb-1.5">
                        Account
                    </p>
                    <h1 class="font-serif font-normal text-[26px] text-[#0B2540] leading-tight">
                        Notifications
                    </h1>
                </div>

                <?php if ($unreadCount > 0): ?>
                    <form action="<?= BASE_URL ?>/employee-notification" method="post">
                        <button type="submit" name="mark_all_read" value="1"
                            class="text-[12.5px] font-semibold text-[#0B2540] hover:text-[#A9822C] transition-colors">
                            Mark all as read
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="bg-[#FCFBF8] border border-[#D9D4C6] rounded-sm overflow-hidden">

                <?php if (empty($notifications)): ?>
                    <div class="py-16 text-center">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#C7CCD1" stroke-width="1.6"
                            class="mx-auto mb-3">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                        </svg>
                        <p class="text-[14px] text-[#6B7785] font-medium">You have no notifications yet.</p>
                    </div>
                <?php else: ?>
                    <ul class="divide-y divide-[#E4E1D8]">
                        <?php foreach ($notifications as $n):
                            $isUnread = (int) $n['is_read'] === 0;
                            $href = !empty($n['link']) ? htmlspecialchars($n['link']) : null;
                            ?>
                            <li class="relative <?= $isUnread ? 'bg-[#F7F4EA]' : 'bg-[#FCFBF8]' ?>">
                                <div class="flex items-start gap-3 px-5 py-4">
                                    <span
                                        class="mt-1.5 w-2 h-2 rounded-full shrink-0 <?= $isUnread ? 'bg-[#A9822C]' : 'bg-transparent' ?>"></span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-3">
                                            <p
                                                class="text-[13.5px] <?= $isUnread ? 'font-semibold text-[#241F14]' : 'font-medium text-[#4B5866]' ?> leading-snug">
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
        <form action="<?= BASE_URL ?>/notification-handler" method="post">
            <input type="hidden" name="mark_read_id" value="<?= (int) $n['id'] ?>">
            <button type="submit"
                class="text-[11.5px] font-semibold text-[#0B2540] hover:text-[#A9822C] inline-flex items-center gap-1">
                View
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M7 17L17 7M7 7h10v10" />
                </svg>
            </button>
        </form>
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
    </main>

    <script>
        document.querySelectorAll('.mark-read-view').forEach(function (link) {
            link.addEventListener('click', function () {
                const id = this.dataset.markId;

                fetch('<?= BASE_URL ?>/notification-handler', {
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
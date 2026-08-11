<?php
//notification.php 
include ROOT_PATH . "/network/connect.php";

include ROOT_PATH . "/ui/notification/backend/employee-notification-actions.php";

$userId = $_SESSION['user_id'] ?? 0;

if ($userId <= 0) {
    header("Location: " . BASE_URL . "/login");
    exit;
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

                <div id="notif-header-actions"></div>
            </div>

            <div class="bg-[#FCFBF8] border border-[#D9D4C6] rounded-sm overflow-hidden">
                <div id="notif-list">
                    <div class="py-16 text-center">
                        <p class="text-[14px] text-[#6B7785] font-medium">Loading...</p>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        const NOTIF_POLL_URL = '<?= BASE_URL ?>/notification-poll';
        const NOTIF_ACTION_URL = '<?= BASE_URL ?>/notification-handler';
        const POLL_INTERVAL_MS = 3000;

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str ?? '';
            return div.innerHTML;
        }

        function renderNotifications(data) {
            const listEl = document.getElementById('notif-list');
            const headerEl = document.getElementById('notif-header-actions');

            headerEl.innerHTML = data.unread_count > 0
                ? `<button type="button" id="mark-all-read-btn"
                     class="text-[12.5px] font-semibold text-[#0B2540] hover:text-[#A9822C] transition-colors">
                     Mark all as read
                   </button>`
                : '';

            const markAllBtn = document.getElementById('mark-all-read-btn');
            if (markAllBtn) {
                markAllBtn.addEventListener('click', markAllRead);
            }

            if (!data.notifications.length) {
                listEl.innerHTML = `
                    <div class="py-16 text-center">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#C7CCD1" stroke-width="1.6" class="mx-auto mb-3">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
                        </svg>
                        <p class="text-[14px] text-[#6B7785] font-medium">You have no notifications yet.</p>
                    </div>`;
                return;
            }

            listEl.innerHTML = `<ul class="divide-y divide-[#E4E1D8]">` +
                data.notifications.map(n => `
                    <li class="relative ${n.is_read ? 'bg-[#FCFBF8]' : 'bg-[#F7F4EA]'}">
                        <div class="flex items-start gap-3 px-5 py-4">
                            <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 ${n.is_read ? 'bg-transparent' : 'bg-[#A9822C]'}"></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-[13.5px] ${n.is_read ? 'font-medium text-[#4B5866]' : 'font-semibold text-[#241F14]'} leading-snug">
                                        ${escapeHtml(n.title)}
                                    </p>
                                    <span class="text-[11px] text-[#9AA2AA] shrink-0 whitespace-nowrap">${escapeHtml(n.time_ago)}</span>
                                </div>
                                ${n.message ? `<p class="text-[12.5px] text-[#6B7785] mt-1 leading-relaxed">${escapeHtml(n.message)}</p>` : ''}
                                ${n.link ? `
                                    <div class="flex items-center gap-3 mt-2">
                                        <button type="button" data-id="${n.id}" data-link="${escapeHtml(n.link)}"
                                            class="notif-view-btn text-[11.5px] font-semibold text-[#0B2540] hover:text-[#A9822C] inline-flex items-center gap-1">
                                            View
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M7 17L17 7M7 7h10v10" />
                                            </svg>
                                        </button>
                                    </div>` : ''}
                            </div>
                        </div>
                    </li>
                `).join('') +
                `</ul>`;

            document.querySelectorAll('.notif-view-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    markRead(btn.dataset.id, btn.dataset.link);
                });
            });
        }

        async function fetchNotifications() {
            try {
                const res = await fetch(NOTIF_POLL_URL, { credentials: 'same-origin' });
                if (!res.ok) return;
                const data = await res.json();
                renderNotifications(data);
            } catch (e) {
                console.error('Notification poll failed', e);
            }
        }

        async function markRead(id, link) {
            try {
                await fetch(NOTIF_ACTION_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'mark_read_id=' + encodeURIComponent(id) + '&ajax=1',
                    credentials: 'same-origin'
                });
            } catch (e) {
                console.error('Mark read failed', e);
            } finally {
                fetchNotifications();
                if (link) window.location.href = link;
            }
        }

        async function markAllRead() {
            try {
                await fetch(NOTIF_ACTION_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'mark_all_read=1&ajax=1',
                    credentials: 'same-origin'
                });
            } catch (e) {
                console.error('Mark all read failed', e);
            } finally {
                fetchNotifications();
            }
        }

        fetchNotifications();
        setInterval(fetchNotifications, POLL_INTERVAL_MS);
    </script>

</body>

</html>
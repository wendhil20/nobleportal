<?php
//top.php
?>

<!-- ==== DESKTOP SIDEBAR ==== -->
<aside
    class="hidden md:flex md:flex-col md:fixed md:inset-y-0 md:left-0 md:w-64 md:z-50 bg-white border-r border-black/5">

    <!-- Brand header (navy zone so the mark reads immediately, mirrors mobile top bar) -->
    <div class="bg-black px-5 pt-6 pb-5 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center shrink-0 shadow-sm">
            <img src="<?= BASE_URL ?>/icon/logo/logo.png" alt="Noblehome Construction" class="w-8 h-8 object-contain">
        </div>
        <div class="min-w-0">
            <p
                class="font-['Barlow_Condensed'] font-semibold text-[13px] leading-tight tracking-[0.06em] uppercase text-white truncate">
                <span class="text-amber-500">Noble</span>home
            </p>
            <p
                class="font-['Barlow_Condensed'] font-medium text-[10px] leading-tight tracking-[0.16em] uppercase text-white truncate mt-0.5">
                Construction Corp.
            </p>
        </div>
    </div>

    <!-- Profile card -->
    <div class="px-4 pt-4 pb-2 relative">
        <!-- Dropdown -->
        <div id="profileDropdown"
            class="hidden absolute left-4 right-4 top-[calc(100%-2px)] mt-1 bg-white rounded-lg shadow-lg border border-[#EDEFF1] overflow-hidden z-50">
            <a href="<?= BASE_URL ?>/logout/"
                class="block px-3 py-2.5 text-xs text-[#1B2733] hover:bg-[#F5F6F7] hover:text-red-500 transition-colors">
                Log out
            </a>
        </div>

        <button type="button" onclick="document.getElementById('profileDropdown').classList.toggle('hidden')"
            class="w-full flex items-center gap-2.5 px-2.5 py-2.5 rounded-lg border border-black/[0.06] hover:border-black/10 hover:bg-[#F9FAFA] transition-colors">
            <div
                class="w-8 h-8 rounded-full bg-[#A9822C] text-white flex items-center justify-center text-[11px] font-semibold shrink-0">
                <?= isset($_SESSION['first_name']) ? strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'] ?? '', 0, 1)) : 'NH' ?>
            </div>
            <div class="min-w-0 flex-1 text-left">
                <p class="text-[13px] font-semibold text-[#1B2733] truncate leading-tight">
                    <?= isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name'] . ' ' . ($_SESSION['last_name'] ?? '')) : 'Guest' ?>
                </p>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-[#C7CCD1] shrink-0" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m6 9 6 6 6-6"></path>
            </svg>
        </button>
    </div>

    <div class="mx-4 mt-2 border-b border-black/5"></div>

    <!-- Nav links -->
    <nav class="flex-1 flex flex-col px-3 pt-5 pb-4">
        <p class="px-3.5 pb-2 text-[10px] font-semibold tracking-[0.14em] uppercase text-[#9AA2AA]">Menu</p>
        <div class="flex flex-col gap-0.5">
            <a href="<?= BASE_URL ?>/page-1"
                class="nav-link flex items-center gap-3 pl-3 pr-3.5 py-2.5 border-l-[3px] border-transparent rounded-r-md text-sm font-medium text-[#4B5866] hover:text-[#0B2540] hover:bg-black/[0.03] transition-colors">
                <i class="fa-solid fa-tachograph-digital"></i>
                Create Employee Profile
            </a>
            <a href="<?= BASE_URL ?>/controlpanel/employees/"
                class="nav-link flex items-center gap-3 pl-3 pr-3.5 py-2.5 border-l-[3px] border-transparent rounded-r-md text-sm font-medium text-[#4B5866] hover:text-[#0B2540] hover:bg-black/[0.03] transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Employees
            </a>

        </div>
        <p class="px-3.5 pb-2 text-[10px] font-semibold tracking-[0.14em] uppercase text-[#9AA2AA]">Settings</p>
        <div class="flex flex-col gap-0.5">
            <a href="<?= BASE_URL ?>/page-5"
                class="nav-link flex items-center gap-3 pl-3 pr-3.5 py-2.5 border-l-[3px] border-transparent rounded-r-md text-sm font-medium text-[#4B5866] hover:text-[#0B2540] hover:bg-black/[0.03] transition-colors">
               <i class="fa-solid fa-circle-info"></i>
                Term & Policy
            </a>
        </div>
    </nav>

</aside>

<!-- ==== MOBILE TOP BAR (brand only, unchanged) ==== -->
<nav class="md:hidden sticky top-0 z-50 bg-black text-[#EDEFEF] shadow-sm">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between h-16">

            <div class="flex items-center gap-2.5">
                <img src="<?= BASE_URL ?>/icon/logo/logo.png" alt="Noblehome Construction"
                    class="w-8 h-8 object-contain">
                <span
                    class="font-['Barlow_Condensed'] font-semibold text-xs tracking-[0.1em] uppercase text-[#EDEFEF]/90">
                    <span class="text-amber-500">Noble</span>home Construction Corp.
                </span>
            </div>

        </div>
    </div>
</nav>

<!-- ==== BOTTOM TAB BAR (mobile only, app-style like TikTok Shop) ==== -->
<div id="bottomNav"
    class="md:hidden fixed bottom-0 inset-x-0 z-50 bg-white border-t border-black/10 shadow-[0_-2px_10px_rgba(0,0,0,0.04)]"
    style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="flex items-stretch justify-between px-1">

        <a href="<?= BASE_URL ?>/page-1"
            class="bottom-tab flex-1 flex flex-col items-center justify-center gap-0.5 py-2.5 text-[#9AA2AA]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9.5 12 3l9 6.5"></path>
                <path d="M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5"></path>
            </svg>
            <span class="text-[10px] font-medium">Dashboard</span>
        </a>

        <a href="<?= BASE_URL ?>/controlpanel/employees/"
            class="bottom-tab flex-1 flex flex-col items-center justify-center gap-0.5 py-2.5 text-[#9AA2AA]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <span class="text-[10px] font-medium">Employees</span>
        </a>

        <!-- Center action button (TikTok Shop style raised icon) -->
        <a href="<?= BASE_URL ?>/controlpanel/projects/"
            class="flex-1 flex flex-col items-center justify-center relative">
            <span
                class="absolute -top-5 w-12 h-12 rounded-full bg-[#A9822C] flex items-center justify-center shadow-md border-4 border-white">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#0B2540]" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="7" width="18" height="14" rx="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
            </span>
            <span class="text-[10px] font-medium text-[#9AA2AA] mt-6">Projects</span>
        </a>

        <a href="<?= BASE_URL ?>/controlpanel/reports/"
            class="bottom-tab flex-1 flex flex-col items-center justify-center gap-0.5 py-2.5 text-[#9AA2AA]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3v18h18"></path>
                <path d="M18.7 8 13 13.7l-4-4L3.7 15.7"></path>
            </svg>
            <span class="text-[10px] font-medium">Reports</span>
        </a>

        <a href="<?= BASE_URL ?>/controlpanel/account/"
            class="bottom-tab flex-1 flex flex-col items-center justify-center gap-0.5 py-2.5 text-[#9AA2AA]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span class="text-[10px] font-medium">Account</span>
        </a>

    </div>
</div>

<style>
    /* active state, applied via JS below */
    .bottom-tab.active {
        color: #A9822C;
    }

    .nav-link.active {
        color: #0B2540;
        background-color: rgba(169, 130, 44, 0.08);
        border-left-color: #A9822C;
    }
</style>

<script>
    // Guard against this include running more than once on a page
    if (!window.__navBound) {
        window.__navBound = true;

        const path = window.location.pathname.replace(/\/+$/, '') || '/';

        document.querySelectorAll('.bottom-tab, .nav-link').forEach(function (link) {
            const linkPath = new URL(link.href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
            if (linkPath === path) {
                link.classList.add('active');
            }
        });
    }
</script>
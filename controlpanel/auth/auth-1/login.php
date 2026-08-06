<?php
//login.php

include ROOT_PATH . '/network/connect.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <?php include ROOT_PATH . '/link/top.php'; ?>
</head>

<body class="min-h-screen flex font-['Inter'] bg-[#FAFAF7] text-[#1B2733]">

    <!-- LEFT: brand / blueprint panel -->
    <div class="hidden lg:flex lg:w-[46%] relative flex-col justify-between text-[#EDEFEF] p-12 overflow-hidden bg-cover bg-center"
        style="background-image: linear-gradient(rgba(3, 53, 104, 0.6), rgba(0, 0, 0, 0.6)), url('<?= BASE_URL ?>/icon/background/building2.png');">
        <div class="relative z-10 flex items-center gap-4">
            <img src="<?= BASE_URL ?>/icon/logo/logo.png" alt="Noblehome Construction" class="w-9 h-9 object-contain">
            <span
                class="font-['Barlow_Condensed'] font-semibold text-base tracking-[0.1em] uppercase text-[#EDEFEF]/90">
                Noblehome Construction
            </span>
        </div>

        <div class="relative z-10 max-w-sm">
            <p class="font-['Barlow_Condensed'] text-[13px] tracking-[0.16em] uppercase text-[#A9822C] mb-3">
                Admin portal
            </p>
            <h1 class="font-['Barlow_Condensed'] font-bold text-[38px] leading-[1.1] uppercase mb-4">
                Manage the system,<br>oversee every site.
            </h1>
            <p class="text-sm leading-relaxed text-[#EDEFEF]/60">
                Full administrative access to projects, personnel, and records. Restricted to authorized
                administrators of Noblehome Construction.
            </p>
        </div>

        <div class="relative z-10 font-mono text-[11px] tracking-[0.05em] text-[#EDEFEF]/40">
            REF&nbsp;NH-2026&nbsp;/&nbsp;ADMIN
        </div>
    </div>

    <!-- RIGHT: form panel -->
    <div class="flex-1 flex items-center justify-center p-8 bg-gray-100">
        <div class="w-full max-w-[380px]">

            <!-- mobile-only brand mark -->
            <div class="flex lg:hidden items-center gap-2.5 mb-10">
                <img src="<?= BASE_URL ?>/icon/logo/logo.png" alt="Noblehome Construction"
                    class="w-9 h-9 object-contain">
                <span
                    class="font-['Barlow_Condensed'] font-bold text-lg tracking-[0.1em] uppercase text-black/80">
                    Noblehome Construction
                </span>
            </div>

            <p
                class="font-['Barlow_Condensed'] font-semibold text-[13px] tracking-[0.16em] uppercase text-black mb-1.5">
                Admin access
            </p>
            <h2 class="font-['Barlow_Condensed'] font-bold text-[28px] uppercase leading-[1.1] mb-8 text-amber-600">
                Log in to continue
            </h2>

            <?php if (isset($_GET['error'])): ?>
                <div class="mb-5 bg-red-50 border border-red-200 text-red-600 text-sm px-4 py-2.5 rounded-md">
                    Invalid email or password.
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>/admin-handler" method="post" class="flex flex-col gap-5">
                <div>
                    <label for="email"
                        class="block text-[11px] font-semibold tracking-[0.1em] uppercase text-[#6B7785] mb-1.5">
                        Email
                    </label>
                    <input type="email" name="email" id="email" placeholder="Enter your admin email"
                        autocomplete="username" required autofocus
                        class="w-full bg-white border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[15px] text-[#1B2733] placeholder-[#9AA2AA] outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password"
                            class="block text-[11px] font-semibold tracking-[0.1em] uppercase text-[#6B7785]">
                            Password
                        </label>
                    </div>
                    <input type="password" name="password" id="password" placeholder="Enter your password"
                        autocomplete="current-password" required
                        class="w-full bg-white border border-[#D8DBDE] rounded-md px-3.5 py-2.5 text-[15px] text-[#1B2733] placeholder-[#9AA2AA] outline-none focus:border-[#0B2540] focus:ring-2 focus:ring-[#0B2540]/10 transition-colors">
                </div>

                <button type="submit"
                    class="mt-2 w-full py-3 bg-black text-[#FAFAF7] font-['Barlow_Condensed'] font-bold text-[15px] tracking-[0.08em] uppercase rounded-md transition-colors hover:bg-[#0B2540]">
                    Log in
                </button>
            </form>

            <p class="mt-8 text-xs text-[#9AA2AA] text-center">Restricted to authorized administrators</p>
        </div>
    </div>

</body>

</html>
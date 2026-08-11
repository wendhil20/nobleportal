<?php
//termandpolicy.php
include ROOT_PATH . "/network/connect.php";

$sections = [
    [
        'title' => 'Admin',
        'items' => [
            'Keep all employee information confidential.',
            'Use the system only for authorized HR purposes.',
            'Maintain accurate employee records.',
            'Do not share or misuse employee information.',
            'Keep your admin account and password secure.',
        ],
    ],
    [
        'title' => 'User / Employee',
        'items' => [
            'Provide accurate and updated information.',
            'Keep your account and password confidential.',
            'Use the HRIS only for official company purposes.',
            "Do not access another employee's account or information.",
            'Report any errors or unauthorized access to HR immediately.',
        ],
    ],
    [
        'title' => 'General Terms',
        'items' => [
            'All information in the HRIS is confidential.',
            'Unauthorized access, falsification, or misuse of the system is prohibited.',
            'The company may suspend or terminate access for violations of these terms.',
            'The company may update these Terms and Conditions when necessary.',
        ],
    ],
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions</title>
    <?php include ROOT_PATH . "/link/top.php"; ?>

</head>

<body class="bg-[#EDEAE1] font-['Inter']">

    <?php include ROOT_PATH . "/ui/navigation/top.php"; ?>

    <!-- ==== PAGE CONTENT ==== -->
    <main class="md:pl-64 pt-6 pb-24 md:pb-10 px-4 md:px-8">
        <div class="max-w-3xl mx-auto">

            <p class="font-['Barlow_Condensed'] font-semibold text-[13px] tracking-[0.16em] uppercase text-[#A9822C] mb-1">
                Legal
            </p>
            <h1 class="font-['Barlow_Condensed'] font-bold text-[26px] uppercase text-[#0B2540] mb-6">
                Terms and Conditions
            </h1>

            <div class="bg-white border border-[#D8DBDE] rounded-md">

                <!-- letterhead -->
                <div class="border-b-2 border-[#0B2540] px-6 md:px-8 pt-6 pb-4">
                    <p class="font-mono text-[11px] tracking-[0.15em] uppercase text-[#9AA2AA]">
                        Human Resources Information System
                    </p>
                    <h2 class="font-['Barlow_Condensed'] font-bold text-[18px] uppercase tracking-wide text-[#0B2540]">
                        NobleHome Construction Corporation
                    </h2>
                </div>

                <!-- intro -->
                <div class="px-6 md:px-8 pt-6">
                    <p class="text-[14px] text-[#4B5866] leading-relaxed">
                        Welcome to the NobleHome Construction Corporation HRIS. By accessing and
                        using this system, you agree to follow these Terms and Conditions.
                    </p>
                </div>

                <!-- sections -->
                <div class="px-6 md:px-8 py-6 space-y-7">
                    <?php foreach ($sections as $section): ?>
                        <div>
                            <h3 class="font-['Barlow_Condensed'] font-bold text-[15px] uppercase tracking-wide text-[#0B2540] mb-3 pb-2 border-b border-[#EDEFF1]">
                                <?= htmlspecialchars($section['title']) ?>
                            </h3>
                            <ul class="space-y-2.5">
                                <?php foreach ($section['items'] as $item): ?>
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-2 w-1.5 h-1.5 rounded-full bg-[#A9822C] shrink-0"></span>
                                        <span class="text-[13.5px] text-[#1B2733] leading-relaxed">
                                            <?= htmlspecialchars($item) ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- acknowledgment -->
                <div class="border-t border-[#EDEFF1] px-6 md:px-8 py-5 bg-[#F9F7F2] rounded-b-md">
                    <p class="text-[13px] font-medium text-[#0B2540] leading-relaxed">
                        By using this HRIS, you acknowledge that you have read, understood, and
                        agreed to these Terms and Conditions.
                    </p>
                </div>

            </div>

        </div>
    </main>

</body>

</html>
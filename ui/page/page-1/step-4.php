<?php
// step-4.php — kasama sa /ui/page/page-1/
// Locked/read-only view ng 201 File (PENDING / APPROVED / REJECTED)
// Umaasa sa mga variables na galing sa main.php: $existingInfo, $uploadedDocs, $documentTypes
?>
<div>

    <?php if ($existingInfo['status'] === 'APPROVED'): ?>
        <!-- ==== Formal Verification Stamp ==== -->
        <div class="flex items-start justify-between gap-4 mb-6 pb-6 border-b border-dashed border-[#D9D4C6]">
            <div class="min-w-0">
                <p class="text-[10.5px] font-semibold tracking-[0.14em] uppercase text-[#8B8371] mb-1">
                    Status
                </p>
                <p class="text-[13px] text-[#4A4636] leading-relaxed max-w-md">
                    This record has been reviewed and confirmed accurate by Human Resources. It is now part
                    of the employee's official 201 File on record.
                </p>
                <?php if (!empty($existingInfo['review_notes'])): ?>
                    <p class="text-[12px] text-[#8B8371] mt-2 italic">
                        HR note: <?= htmlspecialchars($existingInfo['review_notes']) ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="border-2 text-[#1F6B3A] border-[#1F6B3A] rounded-sm px-4 py-1.5 rotate-[-2deg] select-none shrink-0">
                <p class="font-serif font-bold text-[12px] tracking-[0.1em] uppercase leading-tight text-center">Verified<br>&amp; Approved</p>
            </div>
        </div>
    <?php elseif ($existingInfo['status'] === 'REJECTED'): ?>
        <p class="text-sm text-[#6B6350] mb-6">
            Some of your submitted documents were flagged by HR for re-upload (see above). Your other
            information remains on file and does not need to be re-entered.
        </p>
    <?php elseif ($existingInfo['status'] === 'PENDING' && !empty($flaggedDocs)): ?>
        <p class="text-sm text-[#6B6350] mb-6">
            One or more of your submitted documents were flagged by HR for re-upload (see above). Your
            other information remains on file and does not need to be re-entered.
        </p>
    <?php else: ?>
        <p class="text-sm text-[#6B6350] mb-6">
            Your submitted information is being reviewed by HR. You'll be able to make changes again once it has
            been approved or rejected.
        </p>
    <?php endif; ?>

    <div class="flex flex-col gap-6">

        <!-- SECTION: Personal Details -->
        <div>
            <p class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C] mb-3 pb-2 border-b-2 border-[#0B2540]">
                I. Personal Details
            </p>
            <div class="flex flex-col">
                <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                    <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Full Name</p>
                    <p class="text-[13.5px] text-[#241F14] leading-snug">
                        <?= htmlspecialchars(trim(($existingInfo['first_name'] ?? '') . ' ' . ($existingInfo['middle_name'] ?? '') . ' ' . ($existingInfo['last_name'] ?? '') . ' ' . ($existingInfo['extension_name'] ?? ''))) ?>
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                    <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Birthdate / Age</p>
                    <p class="text-[13.5px] text-[#241F14] leading-snug">
                        <?= htmlspecialchars($existingInfo['birthdate'] ?? '') ?> (<?= htmlspecialchars($existingInfo['age'] ?? '') ?>)
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                    <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Gender</p>
                    <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($existingInfo['gender'] ?? '') ?></p>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                    <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Marital Status</p>
                    <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($existingInfo['marital_status'] ?? '') ?></p>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8] last:border-b-0">
                    <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Birthplace</p>
                    <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($existingInfo['birthplace'] ?? '') ?></p>
                </div>
            </div>
        </div>

        <!-- SECTION: Background -->
        <div>
            <p class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C] mb-3 pb-2 border-b-2 border-[#0B2540]">
                II. Background
            </p>
            <div class="flex flex-col">
                <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8]">
                    <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Religion</p>
                    <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($existingInfo['religion'] ?? '') ?></p>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-[#E4E1D8] last:border-b-0">
                    <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Citizenship</p>
                    <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($existingInfo['citizenship'] ?? '') ?></p>
                </div>
            </div>
        </div>

        <!-- SECTION: Address -->
        <div>
            <p class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C] mb-3 pb-2 border-b-2 border-[#0B2540]">
                III. Address
            </p>
            <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2">
                <p class="text-[10.5px] font-semibold tracking-[0.1em] uppercase text-[#8B8371] sm:w-[38%] shrink-0">Present Complete Address</p>
                <p class="text-[13.5px] text-[#241F14] leading-snug"><?= htmlspecialchars($existingInfo['present_address'] ?? '') ?></p>
            </div>
        </div>

    </div>

    <?php if (!empty($uploadedDocs)): ?>
        <div class="mt-6 pt-5 border-t-2 border-[#0B2540]">
            <details class="group/main">
                <summary
                    class="flex items-center justify-between gap-2 cursor-pointer select-none list-none mb-3">
                    <span class="text-[10.5px] font-bold tracking-[0.2em] uppercase text-[#A9822C]">
                        IV. Submitted Documents
                        <span
                            class="text-[#8B8371] normal-case font-medium tracking-normal">(<?= array_sum(array_map('count', $uploadedDocs)) ?>
                            files)</span>
                    </span>
                    <span class="flex items-center gap-1 text-[11px] font-semibold text-[#0B2540]">
                        <span class="group-open/main:hidden">Show all</span>
                        <span class="hidden group-open/main:inline">Hide</span>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-3.5 h-3.5 transition-transform group-open/main:rotate-180" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </span>
                </summary>

                <div class="space-y-2">
                    <?php foreach ($uploadedDocs as $docKey => $docList): ?>
                        <details class="group border border-[#E4E1D8] rounded-sm overflow-hidden">
                            <summary
                                class="flex items-center justify-between gap-2 px-3.5 py-2.5 text-[13px] font-semibold text-[#241F14] bg-[#F5F3EC] cursor-pointer select-none list-none">
                                <span class="flex items-center gap-2">
                                    <?= htmlspecialchars($documentTypes[$docKey]['label'] ?? ucwords(str_replace('_', ' ', $docKey))) ?>
                                    <span
                                        class="text-[10px] font-mono font-semibold text-[#8B8371] bg-white border border-[#D9D4C6] rounded-full px-2 py-0.5">
                                        <?= count($docList) ?>
                                    </span>
                                </span>
                                <svg xmlns="http://www.w3.org/2000/svg"
                                    class="w-4 h-4 text-[#8B8371] transition-transform group-open:rotate-180 shrink-0"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </summary>
                            <ul class="space-y-1.5 px-3.5 py-3 bg-white">
                                <?php foreach ($docList as $file): ?>
                                    <li
                                        class="flex items-center justify-between gap-2 text-[13px] text-[#4A4636] bg-[#F5F3EC] rounded-sm px-2.5 py-1.5">
                                        <span
                                            class="truncate"><?= htmlspecialchars($file['original_filename']) ?></span>
                                        <a href="<?= BASE_URL ?>/page-1-viewdocument?id=<?= (int) $file['id'] ?>"
                                            target="_blank"
                                            class="text-[#0B2540] font-semibold hover:text-[#A9822C] underline underline-offset-2 shrink-0">View</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    <?php endforeach; ?>
                </div>
            </details>
        </div>
    <?php endif; ?>
</div>
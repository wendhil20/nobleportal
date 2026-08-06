<?php
// step-3.php — kasama sa /ui/page/page-1/
// Umaasa sa mga variables na galing sa main.php: $documentTypes, $currentMarital, $uploadedDocs
?>
<!-- ==== STEP 3: DOCUMENT UPLOADS ==== -->
<div data-step="3" class="hidden flex-col gap-3">
    <p class="text-[11px] font-semibold tracking-[0.08em] uppercase text-[#9AA2AA] mb-3"> PDF AND SCANNE IMAGE & IMAGE ARE ALLOWED. </p>

    <?php foreach ($documentTypes as $key => $doc):
        if ($key === 'marriage_certificate'): ?>
            <div data-marriage-doc
                class="<?= $currentMarital === 'MARRIED' ? '' : 'hidden' ?>">
            <?php endif; ?>

            <?php $already = $uploadedDocs[$key] ?? []; ?>
            <div class="border border-[#E8EAEC] rounded-md p-4">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-[13px] font-semibold text-[#1B2733]">
                        <?= htmlspecialchars($doc['label']) ?>
                        <?php if ($doc['required']): ?>
                            <span class="text-red-500">*</span>
                        <?php else: ?>
                            <span class="text-[#9AA2AA] normal-case font-normal">(optional)</span>
                        <?php endif; ?>
                    </label>
                    <?php if (!empty($already)): ?>
                        <span
                            class="text-[11px] font-semibold text-green-700 bg-green-50 border border-green-200 rounded-full px-2.5 py-1">
                            ✓ Uploaded (<?= count($already) ?>)
                        </span>
                    <?php endif; ?>
                </div>

                <input type="file"
                    name="documents[<?= $key ?>]<?= !empty($doc['multiple']) ? '[]' : '' ?>"
                    data-doc-input data-doc-key="<?= $key ?>"
                    data-doc-required="<?= $doc['required'] ? '1' : '0' ?>"
                    accept=".jpg,.jpeg,.png,.webp,.pdf"
                    <?= !empty($doc['multiple']) ? 'multiple' : '' ?>
                    class="w-full text-sm text-[#4B5866] file:mr-3 file:py-2 file:px-3.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:uppercase file:tracking-[0.06em] file:bg-[#0B2540] file:text-white hover:file:bg-[#0B2540]/90">

                <!-- Live preview ng bagong pinili na file, bago pa i-submit -->
                <div data-doc-preview class="mt-2 flex flex-wrap gap-2"></div>

                <?php if (!empty($doc['multiple'])): ?>
                    <p class="text-[11px] text-[#9AA2AA] mt-1">Up to <?= $doc['max'] ?> files.
                        Pwede mag-upload
                        ng bago para dagdag.</p>
                <?php endif; ?>

                <?php if (!empty($already)): ?>
                    <ul class="mt-2 space-y-1">
                        <?php foreach ($already as $file): ?>
                            <li
                                class="flex items-center justify-between gap-2 text-[11px] text-[#6B7785] bg-[#F5F6F7] rounded px-2.5 py-1.5">
                                <span
                                    class="truncate"><?= htmlspecialchars($file['original_filename']) ?></span>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button" data-view-doc
                                        data-doc-url="<?= BASE_URL ?>/page-1-viewdocument?id=<?= (int) $file['id'] ?>"
                                        data-doc-mime="<?= htmlspecialchars($file['mime_type']) ?>"
                                        data-doc-name="<?= htmlspecialchars($file['original_filename']) ?>"
                                        class="text-[#0B2540] font-semibold hover:underline">
                                        View
                                    </button>
                                    <a href="<?= BASE_URL ?>/page-1-viewdocument?id=<?= (int) $file['id'] ?>&download=1"
                                        class="text-[#A9822C] font-semibold hover:underline">
                                        Download
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <?php if ($key === 'marriage_certificate'): ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <p class="text-[11px] text-[#9AA2AA]">
        Accepted file types: JPG, PNG, WEBP, PDF. Max 5MB per file. Images will be automatically
        converted to WebP.
    </p>
    <!-- ==== Certification / Agreement ==== -->
    <div class="border border-[#D8DBDE] rounded-md p-4 bg-[#F5F6F7]">
        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" name="agreement" id="agreement" required
                class="mt-0.5 w-4 h-4 accent-[#0B2540] shrink-0">
            <span class="text-[12.5px] text-[#4B5866] leading-relaxed">
                I certify that all the information and documents I have provided above are true, complete,
                and accurate to the best of my knowledge. I understand that any false statement or
                falsified document may result in the rejection of my 201 File, disciplinary action, or
                termination in accordance with company policy.
            </span>
        </label>
        <p id="agreementError" class="hidden text-[11px] text-red-600 mt-2 ml-7">
            Please check this box to confirm before submitting.
        </p>
    </div>

    <div class="flex justify-between pt-2">
        <button type="button" data-back
            class="px-6 py-2.5 bg-white border border-[#D8DBDE] text-[#4B5866] font-['Barlow_Condensed'] font-bold text-sm tracking-[0.06em] uppercase rounded-md hover:bg-[#F5F6F7] transition-colors">
            Back
        </button>
        <button type="submit" id="submitBtn"
            class="px-6 py-2.5 bg-black text-white font-['Barlow_Condensed'] font-bold text-sm tracking-[0.06em] uppercase rounded-md hover:bg-[#A9822C]/90 transition-colors">
            Submit File
        </button>
    </div>
</div>
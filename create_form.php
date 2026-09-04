<?php
session_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

// require_once 'PermissionChecking.php';

// -------- If editing an existing form, load it for the JS to pre-fill --------
$edit_form_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$existing_form = null;

if ($edit_form_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM forms WHERE id = ?");
    $stmt->bind_param("i", $edit_form_id);
    $stmt->execute();
    $existing_form = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing_form) {
        $q_stmt = $conn->prepare("SELECT * FROM form_questions WHERE form_id = ? ORDER BY order_index ASC");
        $q_stmt->bind_param("i", $edit_form_id);
        $q_stmt->execute();
        $questions = $q_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $q_stmt->close();

        foreach ($questions as &$q) {
            $o_stmt = $conn->prepare("SELECT option_text FROM question_options WHERE question_id = ? ORDER BY order_index ASC");
            $o_stmt->bind_param("i", $q['id']);
            $o_stmt->execute();
            $opts = $o_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $q['options'] = array_map(fn($o) => $o['option_text'], $opts);
            $o_stmt->close();
        }
        unset($q);
        $existing_form['questions'] = $questions;
    }
}



// -------- Fetch all programs --------
$programs = [];

$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($user_id > 0) {

    $program_stmt = $conn->prepare("
        SELECT DISTINCT
            p.program_code,
            p.program_name,
            p.prog_code
        FROM program_allocation_user pau
        INNER JOIN program_table p
            ON p.program_code = pau.program_code
        WHERE pau.user_id = ?
        ORDER BY p.program_name ASC
    ");

    $program_stmt->bind_param("i", $user_id);
    $program_stmt->execute();

    $program_result = $program_stmt->get_result();

    while ($program_row = $program_result->fetch_assoc()) {
        $programs[] = $program_row;
    }

    $program_stmt->close();
}


?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    ink: {
                        DEFAULT: '#042d5c',
                        dark: '#021f40'
                    },
                    paper: '#F8FAFC',
                    gold: {
                        DEFAULT: '#E11D48',
                        light: '#FFE4E6'
                    },
                    line: '#E2E8F0',
                },
                fontFamily: {
                    body: ['Inter', 'sans-serif'],
                },
            }
        }
    }
</script>
<style>
    /* Prevent parent containers from breaking position: sticky */
    #wrapper,
    #content-wrapper,
    #content {
        overflow: visible !important;
    }
</style>

<style>
    body {
        background-color: #F8FAFC;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
    }

    .custom-input {
        border: 1px solid #E2E8F0;
        transition: all 0.15s ease-in-out;
    }

    .custom-input:focus {
        outline: none;
        border-color: #042d5c;
        box-shadow: 0 0 0 2px rgba(4, 45, 92, 0.08);
    }

    .check-box {
        appearance: none;
        -webkit-appearance: none;
        width: 0.95rem;
        height: 0.95rem;
        border: 1px solid #CBD5E1;
        background: #fff;
        border-radius: 3px;
        cursor: pointer;
        position: relative;
        transition: all .15s ease;
    }

    .check-box:checked {
        border-color: #042d5c;
        background-color: #042d5c;
    }

    .check-box:checked::after {
        content: '';
        position: absolute;
        left: 4px;
        top: 1px;
        width: 4px;
        height: 7px;
        border: solid #fff;
        border-width: 0 1.5px 1.5px 0;
        transform: rotate(45deg);
    }
</style>

<div id="wrapper">

    <?php include("nav.php"); ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <?php include("includes/topnav.php"); ?>

            <!-- Compact Action Header Bar -->
            <!-- Fixed Sticky Top Action Header Bar -->
            <div class="sticky top-0 z-[1050] bg-white/95 backdrop-blur border-b border-line shadow-sm mb-4">
                <div class="max-w-3xl mx-auto px-4 py-2.5 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <span class="p-1.5 bg-ink/5 text-ink rounded-md flex items-center justify-center">
                            <i class="fas fa-file-signature text-sm text-ink"></i>
                        </span>
                        <h1 class="font-semibold text-base text-ink m-0 tracking-tight leading-none">
                            <?= $existing_form ? 'Edit Form' : 'Create New Form' ?>
                        </h1>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="forms_dashboard.php" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 hover:text-ink px-3 py-1.5 rounded-md border border-line hover:border-slate-300 transition bg-white no-underline">
                            <i class="fas fa-arrow-left text-[10px]"></i> Dashboard
                        </a>
                        <button type="button" id="saveFormBtn" class="inline-flex items-center gap-1.5 bg-ink hover:bg-ink-dark text-white font-medium text-xs px-4 py-1.5 rounded-md transition shadow-xs active:scale-[0.98]">
                            <i class="fas fa-save text-[10px]"></i> Save Form
                        </button>
                    </div>
                </div>
            </div>

            <div class="container max-w-3xl mx-auto px-4 pb-10">

                <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
                <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


                <!-- Program Selection Card -->
                <!-- Program Selection Card -->
                <!-- Program Selection Card -->
                <div class="bg-white border border-line rounded-xl shadow-xs overflow-hidden mb-4">
                    <div class="p-4 sm:p-5">

                        <label
                            for="programCode"
                            class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            Program
                        </label>

                        <select id="programCode" class="custom-input w-full bg-white text-sm font-medium text-ink rounded-lg px-3 py-2 cursor-pointer">
                            <option value="">Select Program</option>

                            <?php foreach ($programs as $program): ?>

                                <option
                                    value="<?= (int)$program['program_code'] ?>"
                                    <?= (
                                        isset($existing_form['program_code']) &&
                                        (int)$existing_form['program_code'] === (int)$program['program_code']
                                    ) ? 'selected' : '' ?>>

                                    <?= htmlspecialchars($program['program_name']) ?>

                                  
                                </option>

                            <?php endforeach; ?>
                        </select>

                    </div>
                </div>


                <script>
                    $(document).ready(function() {

                        $('#programCode').select2({
                            placeholder: 'Select Program',
                            allowClear: true,
                            width: '100%'
                        });

                    });
                </script>

                <!-- Banner Image Card -->

                <!-- Banner Image Card -->


                <!-- Banner Image Card -->
                <div class="bg-white border border-line rounded-xl shadow-xs overflow-hidden mb-4">
                    <div id="bannerPreviewWrap" style="<?= !empty($existing_form['banner_image']) ? '' : 'display:none;' ?>" class="relative group border-b border-line">
                        <img id="bannerPreviewImg"
                            src="<?= !empty($existing_form['banner_image']) ? htmlspecialchars('bms-form/' . $existing_form['banner_image']) : '' ?>"
                            class="w-full max-h-36 object-cover block">
                    </div>
                    <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-ink text-xs flex items-center gap-1.5 m-0">
                                <i class="fas fa-image text-slate-400"></i> Banner Image
                            </h3>
                            <p class="text-[11px] text-slate-500 mt-0.5 mb-0">Header graphic displayed at top of form (up to 5MB).</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <input type="file" id="bannerFileInput" accept="image/png,image/jpeg,image/gif,image/webp" class="hidden">
                            <button type="button" id="bannerUploadBtn" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-md transition">
                                <i class="fas fa-upload text-[11px]"></i> <span id="bannerBtnLabel"><?= !empty($existing_form['banner_image']) ? 'Change' : 'Upload' ?></span>
                            </button>
                            <button type="button" id="bannerRemoveBtn" class="inline-flex items-center gap-1 text-xs font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 px-2.5 py-1.5 rounded-md transition"
                                style="<?= !empty($existing_form['banner_image']) ? '' : 'display:none;' ?>">
                                <i class="fas fa-trash text-[11px]"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Form Metadata Card -->
                <div class="bg-white border-t-2 border-t-ink border-x border-b border-line rounded-xl p-4 sm:p-5 shadow-xs mb-4 space-y-3.5">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Form Title</label>
                        <input type="text" id="formTitle" class="custom-input w-full text-lg font-semibold text-ink rounded-lg px-3 py-2 placeholder-slate-300"
                            placeholder="Untitled Form"
                            value="<?= $existing_form ? htmlspecialchars($existing_form['title']) : '' ?>">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Description</label>
                        <textarea id="formDescription" class="custom-input w-full text-xs text-slate-700 rounded-lg px-3 py-2 placeholder-slate-300 resize-y" rows="2"
                            placeholder="Provide description or instructions..."><?= $existing_form ? htmlspecialchars($existing_form['description']) : '' ?></textarea>
                    </div>

                    <div class="pt-3 border-t border-line flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-4">
                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-slate-600 m-0">
                                <input type="checkbox" id="collectEmail" class="check-box"
                                    <?= ($existing_form['collect_email'] ?? 0) ? 'checked' : '' ?>>
                                <span>Collect emails</span>
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-slate-600 m-0">
                                <input type="checkbox" id="oneResponse" class="check-box"
                                    <?= ($existing_form['one_response_per_user'] ?? 0) ? 'checked' : '' ?>>
                                <span>Limit to 1 response</span>
                            </label>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status:</span>
                            <select id="formStatus" class="custom-input bg-slate-50 text-xs font-medium text-ink rounded-md px-2.5 py-1 cursor-pointer">
                                <?php $st = $existing_form['status'] ?? 'draft'; ?>
                                <option value="draft" <?= $st === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $st === 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="closed" <?= $st === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Questions Container -->
                <div id="questionsContainer" class="space-y-3"></div>

                <!-- Add Question Trigger -->
                <div class="mt-5 text-center">
                    <button type="button" id="addQuestionBtn" class="inline-flex items-center gap-1.5 bg-white hover:bg-slate-50 text-ink border border-line font-medium text-xs px-4 py-2 rounded-lg transition shadow-xs hover:border-slate-300 active:scale-95">
                        <i class="fas fa-plus text-slate-400 text-[10px]"></i> Add Question
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Compact Question Template -->
<template id="questionTemplate">
    <div class="question-card bg-white border-l-2 border-l-ink border-y border-r border-line rounded-xl p-4 shadow-xs hover:border-slate-300 transition-all mb-3">
        <div class="flex items-start gap-2.5">
            <span class="drag-handle text-slate-300 hover:text-slate-500 cursor-move pt-1.5 text-xs">
                <i class="fas fa-grip-vertical"></i>
            </span>

            <div class="flex-1 space-y-3">
                <!-- Question Title & Select Type Row -->
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                    <div class="sm:col-span-8">
                        <input type="text" class="q-text custom-input w-full rounded-lg px-3 py-1.5 text-xs font-medium text-slate-800 placeholder-slate-400" placeholder="Question Title">
                    </div>
                    <div class="sm:col-span-4">
                        <select class="q-type custom-input w-full bg-slate-50 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 cursor-pointer">
                            <option value="short_text">Short answer</option>
                            <option value="paragraph">Paragraph</option>
                            <option value="multiple_choice">Multiple choice</option>
                            <option value="checkbox">Checkboxes</option>
                            <option value="dropdown">Dropdown</option>
                            <option value="linear_scale">Linear scale</option>
                            <option value="date">Date</option>
                            <option value="time">Time</option>
                            <option value="file_upload">File Upload</option>
                        </select>
                    </div>
                </div>

                <!-- Image Attachment Preview -->
                <div class="q-image-preview-wrap relative inline-block group" style="display:none;">
                    <img class="q-image-preview rounded-md border border-line max-h-28 w-auto object-cover">
                </div>

                <!-- Multiple Options Container -->
                <div class="q-options-area space-y-1.5"></div>

                <!-- Linear Scale Settings Box -->
                <div class="q-scale-area p-2.5 bg-slate-50 rounded-lg border border-line" style="display:none;">
                    <div class="flex items-center gap-3 text-xs font-medium text-slate-600">
                        <div class="flex items-center gap-1.5">
                            <span>Min:</span>
                            <input type="number" class="scale-min custom-input w-12 bg-white rounded-md px-1.5 py-0.5 text-center text-xs" value="1">
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span>Max:</span>
                            <input type="number" class="scale-max custom-input w-12 bg-white rounded-md px-1.5 py-0.5 text-center text-xs" value="5">
                        </div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div class="pt-2.5 border-t border-line flex items-center justify-between">
                    <label class="inline-flex items-center gap-1.5 cursor-pointer text-[11px] font-medium text-slate-600 m-0">
                        <input type="checkbox" class="q-required check-box">
                        <span>Required</span>
                    </label>

                    <div class="flex items-center gap-1.5">
                        <input type="file" class="q-image-file-input hidden" accept="image/png,image/jpeg,image/gif,image/webp">
                        <button type="button" class="add-question-image-btn inline-flex items-center gap-1 text-[11px] text-slate-600 hover:text-ink bg-slate-100 hover:bg-slate-200 px-2.5 py-1 rounded-md transition">
                            <i class="fas fa-image text-slate-400 text-[10px]"></i> Image
                        </button>
                        <button type="button" class="remove-question-image-btn text-[11px] text-rose-600 bg-rose-50 hover:bg-rose-100 px-2 py-1 rounded-md transition" style="display:none;">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>
                        <div class="w-px h-3 bg-line mx-0.5"></div>
                        <button type="button" class="remove-question inline-flex items-center gap-1 text-[11px] font-medium text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-2.5 py-1 rounded-md transition">
                            <i class="fas fa-trash-alt text-[10px]"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    const EXISTING_FORM = <?= $existing_form ? json_encode($existing_form) : 'null' ?>;
    const EDIT_FORM_ID = <?= $edit_form_id ?>;

    let bannerImagePath = (EXISTING_FORM && EXISTING_FORM.banner_image) ? EXISTING_FORM.banner_image : '';

    const bannerFileInput = document.getElementById('bannerFileInput');
    const bannerUploadBtn = document.getElementById('bannerUploadBtn');
    const bannerRemoveBtn = document.getElementById('bannerRemoveBtn');
    const bannerPreviewWrap = document.getElementById('bannerPreviewWrap');
    const bannerPreviewImg = document.getElementById('bannerPreviewImg');
    const bannerBtnLabel = document.getElementById('bannerBtnLabel');

    function showBannerPreview(path) {
        if (path) {
            bannerPreviewImg.src = 'bms-form/' + path;
            bannerPreviewWrap.style.display = 'block';
            bannerRemoveBtn.style.display = 'inline-block';
            bannerBtnLabel.textContent = 'Change';
        } else {
            bannerPreviewWrap.style.display = 'none';
            bannerRemoveBtn.style.display = 'none';
            bannerBtnLabel.textContent = 'Upload';
        }
    }

    bannerUploadBtn.addEventListener('click', () => bannerFileInput.click());
    bannerFileInput.addEventListener('change', async () => {
        const file = bannerFileInput.files[0];
        if (!file) return;
        bannerUploadBtn.disabled = true;
        bannerBtnLabel.textContent = 'Uploading...';
        const path = await uploadImage(file, 'banner');
        bannerUploadBtn.disabled = false;
        if (path) {
            bannerImagePath = path;
            showBannerPreview(path);
        } else {
            showBannerPreview(bannerImagePath);
        }
        bannerFileInput.value = '';
    });

    bannerRemoveBtn.addEventListener('click', () => {
        bannerImagePath = '';
        showBannerPreview('');
    });

    function optionsHtml() {
        return `
            <div class="options-list space-y-1.5"></div>
            <button type="button" class="add-option-btn inline-flex items-center gap-1 text-[11px] font-medium text-ink hover:underline mt-1">
                <i class="fas fa-plus text-[9px]"></i> Add Option
            </button>
        `;
    }

    function optionRowHtml(value) {
        const row = document.createElement('div');
        row.className = 'option-row flex items-center gap-1.5';
        row.innerHTML = `
            <i class="far fa-circle text-slate-300 text-[10px]"></i>
            <input type="text" class="option-input custom-input flex-1 rounded-md px-2.5 py-1 text-xs text-slate-700 placeholder-slate-400" placeholder="Option" value="${value ? value.replace(/"/g,'&quot;') : ''}">
            <button type="button" class="remove-option text-slate-300 hover:text-rose-600 p-1 transition"><i class="fas fa-times text-[10px]"></i></button>
        `;
        row.querySelector('.remove-option').addEventListener('click', () => row.remove());
        return row;
    }

    async function uploadImage(file, type) {
        const fd = new FormData();
        fd.append('image', file);
        fd.append('type', type);
        const res = await fetch('bms-form/upload_image.php', {
            method: 'POST',
            body: fd
        });
        const result = await res.json();
        if (!result.success) {
            alert('Image upload failed: ' + (result.message || 'Unknown error'));
            return null;
        }
        return result.path;
    }

    function addQuestion(data) {
        data = data || {};
        const tpl = document.getElementById('questionTemplate');
        const clone = tpl.content.cloneNode(true);
        const card = clone.querySelector('.question-card');

        card.querySelector('.q-text').value = data.question_text || '';
        card.querySelector('.q-type').value = data.question_type || 'short_text';
        card.querySelector('.q-required').checked = !!(data.is_required && data.is_required != 0);

        const optionsArea = card.querySelector('.q-options-area');
        const scaleArea = card.querySelector('.q-scale-area');

        card.dataset.questionImage = data.question_image || '';
        const imgPreviewWrap = card.querySelector('.q-image-preview-wrap');
        const imgPreview = card.querySelector('.q-image-preview');
        const imgFileInput = card.querySelector('.q-image-file-input');
        const addImgBtn = card.querySelector('.add-question-image-btn');
        const removeImgBtn = card.querySelector('.remove-question-image-btn');

        function showImagePreview(path) {
            if (path) {
                imgPreview.src = 'bms-form/' + path;
                imgPreviewWrap.style.display = 'inline-block';
                removeImgBtn.style.display = 'inline-block';
                addImgBtn.innerHTML = '<i class="fas fa-image text-ink text-[10px]"></i> Change';
            } else {
                imgPreviewWrap.style.display = 'none';
                removeImgBtn.style.display = 'none';
                addImgBtn.innerHTML = '<i class="fas fa-image text-slate-400 text-[10px]"></i> Image';
            }
        }
        if (data.question_image) showImagePreview(data.question_image);

        addImgBtn.addEventListener('click', () => imgFileInput.click());
        imgFileInput.addEventListener('change', async () => {
            const file = imgFileInput.files[0];
            if (!file) return;
            addImgBtn.disabled = true;
            addImgBtn.innerHTML = '<i class="fas fa-spinner fa-spin text-[10px]"></i> Uploading...';
            const path = await uploadImage(file, 'question');
            addImgBtn.disabled = false;
            if (path) {
                card.dataset.questionImage = path;
                showImagePreview(path);
            } else {
                showImagePreview(card.dataset.questionImage || '');
            }
            imgFileInput.value = '';
        });

        removeImgBtn.addEventListener('click', () => {
            card.dataset.questionImage = '';
            showImagePreview('');
        });

        function refreshVisibility() {
            const type = card.querySelector('.q-type').value;
            const needsOptions = ['multiple_choice', 'checkbox', 'dropdown'].includes(type);
            optionsArea.style.display = needsOptions ? 'block' : 'none';
            scaleArea.style.display = type === 'linear_scale' ? 'block' : 'none';
        }

        optionsArea.innerHTML = optionsHtml();
        const optionsList = optionsArea.querySelector('.options-list');

        if (data.options && data.options.length) {
            data.options.forEach(opt => optionsList.appendChild(optionRowHtml(opt)));
        } else {
            optionsList.appendChild(optionRowHtml(''));
        }

        optionsArea.querySelector('.add-option-btn').addEventListener('click', () => {
            optionsList.appendChild(optionRowHtml(''));
        });

        if (data.scale_min) card.querySelector('.scale-min').value = data.scale_min;
        if (data.scale_max) card.querySelector('.scale-max').value = data.scale_max;

        card.querySelector('.q-type').addEventListener('change', refreshVisibility);
        card.querySelector('.remove-question').addEventListener('click', () => card.remove());

        refreshVisibility();
        document.getElementById('questionsContainer').appendChild(card);
    }

    document.getElementById('addQuestionBtn').addEventListener('click', () => addQuestion());

    if (EXISTING_FORM && EXISTING_FORM.questions) {
        EXISTING_FORM.questions.forEach(q => addQuestion(q));
    } else {
        addQuestion();
    }

    document.getElementById('saveFormBtn').addEventListener('click', async () => {
        const title = document.getElementById('formTitle').value.trim();
        if (!title) {
            alert('Please enter a form title.');
            return;
        }

        const questions = [];
        document.querySelectorAll('#questionsContainer .question-card').forEach(card => {
            const type = card.querySelector('.q-type').value;
            const question_text = card.querySelector('.q-text').value.trim();
            const is_required = card.querySelector('.q-required').checked ? 1 : 0;

            let options = [];
            if (['multiple_choice', 'checkbox', 'dropdown'].includes(type)) {
                card.querySelectorAll('.option-input').forEach(inp => {
                    if (inp.value.trim()) options.push(inp.value.trim());
                });
            }

            let scale_min = null,
                scale_max = null;
            if (type === 'linear_scale') {
                scale_min = parseInt(card.querySelector('.scale-min').value) || 1;
                scale_max = parseInt(card.querySelector('.scale-max').value) || 5;
            }

            const question_image = card.dataset.questionImage || null;

            if (question_text) {
                questions.push({
                    question_text,
                    question_image,
                    question_type: type,
                    is_required,
                    options,
                    scale_min,
                    scale_max
                });
            }
        });

        if (questions.length === 0) {
            alert('Add at least one question.');
            return;
        }

        // const payload = {
        //     form_id: EDIT_FORM_ID || null,
        //     title,
        //     description: document.getElementById('formDescription').value.trim(),
        //     banner_image: bannerImagePath || null,
        //     status: document.getElementById('formStatus').value,
        //     collect_email: document.getElementById('collectEmail').checked ? 1 : 0,
        //     one_response_per_user: document.getElementById('oneResponse').checked ? 1 : 0,
        //     questions
        // };

        const payload = {
            form_id: EDIT_FORM_ID || null,
            program_code: document.getElementById('programCode').value || null,
            title,
            description: document.getElementById('formDescription').value.trim(),
            banner_image: bannerImagePath || null,
            status: document.getElementById('formStatus').value,
            collect_email: document.getElementById('collectEmail').checked ? 1 : 0,
            one_response_per_user: document.getElementById('oneResponse').checked ? 1 : 0,
            questions
        };

        const res = await fetch('bms-form/save_form.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const result = await res.json();

        if (result.success) {
            window.location.href = 'forms_dashboard.php';
        } else {
            alert('Error saving form: ' + (result.message || 'Unknown error'));
        }
    });
</script>

<?php
if (file_exists("includes/footer.php")) {
    include("includes/footer.php");
}
$conn->close();
?>
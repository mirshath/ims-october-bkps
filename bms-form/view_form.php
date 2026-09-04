<?php
session_start();
include("../database/connection.php");
include("../external_redirect.php"); // defines EXTERNAL_REDIRECT_URL

// Validation for missing/invalid/non-numeric ID
if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    header("Location: " . EXTERNAL_REDIRECT_URL);
    exit();
}

$form_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM forms WHERE id = ?");
$stmt->bind_param("i", $form_id);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$form || $form['status'] !== 'published' || !$form['accepting_responses']) {
    header("Location: " . EXTERNAL_REDIRECT_URL);
    exit();
}

$q_stmt = $conn->prepare("SELECT * FROM form_questions WHERE form_id = ? ORDER BY order_index ASC");
$q_stmt->bind_param("i", $form_id);
$q_stmt->execute();
$questions = $q_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$q_stmt->close();

foreach ($questions as &$q) {
    $o_stmt = $conn->prepare("SELECT * FROM question_options WHERE question_id = ? ORDER BY order_index ASC");
    $o_stmt->bind_param("i", $q['id']);
    $o_stmt->execute();
    $q['options'] = $o_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $o_stmt->close();
}
unset($q);
$total_questions = count($questions) + ($form['collect_email'] ? 1 : 0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($form['title']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: {
                            DEFAULT: '#042d5c',
                            dark: '#021f40'
                        },
                        paper: '#FAF7F1',
                        gold: {
                            DEFAULT: '#e01e1e',
                            light: '#f5b8b8'
                        },
                        line: '#E6E1D4',
                    },
                    fontFamily: {
                        display: ['Fraunces', 'serif'],
                        body: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #FAF7F1;
            background-image: url('BG-LOGO.png');
            background-size: 300px auto;
            background-attachment: fixed;
            font-family: 'Inter', sans-serif;
        }

        .radio-dot,
        .check-box {
            appearance: none;
            -webkit-appearance: none;
            width: 1.15rem;
            height: 1.15rem;
            border: 1.5px solid #C9C2AE;
            background: #fff;
            cursor: pointer;
            flex-shrink: 0;
            position: relative;
            transition: all .15s ease;
        }

        .radio-dot {
            border-radius: 50%;
        }

        .check-box {
            border-radius: 4px;
        }

        .radio-dot:checked,
        .check-box:checked {
            border-color: #042d5c;
        }

        .radio-dot:checked::after {
            content: '';
            position: absolute;
            inset: 3px;
            border-radius: 50%;
            background: #e01e1e;
        }

        .check-box:checked::after {
            content: '';
            position: absolute;
            left: 5px;
            top: 1px;
            width: 5px;
            height: 9px;
            border: solid #e01e1e;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .radio-dot:focus-visible,
        .check-box:focus-visible {
            outline: 2px solid #e01e1e;
            outline-offset: 2px;
        }

        input[type=text]:focus,
        input[type=email]:focus,
        input[type=date]:focus,
        input[type=time]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #042d5c;
            box-shadow: 0 0 0 3px rgba(30, 42, 69, 0.08);
        }

        .scale-radio {
            appearance: none;
            -webkit-appearance: none;
            width: 1.35rem;
            height: 1.35rem;
            border-radius: 50%;
            border: 1.5px solid #C9C2AE;
            cursor: pointer;
            transition: all .15s ease;
        }

        .scale-radio:checked {
            background: #042d5c;
            border-color: #042d5c;
            box-shadow: 0 0 0 3px rgba(184, 145, 47, 0.35);
        }

        .field-error {
            border-color: #e01e1e !important;
            box-shadow: 0 0 0 3px rgba(224, 30, 30, 0.15) !important;
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
                animation: none !important;
            }
        }
    </style>
</head>

<body class="text-[#2E3340]">

    <!-- Progress rail -->
    <div class="sticky top-0 z-30 bg-paper/95 backdrop-blur border-b border-line">
        <div class="max-w-2xl mx-auto px-5 sm:px-0 py-3 flex items-center gap-4">
            <span id="progressLabel" class="font-display italic text-sm text-ink/70 whitespace-nowrap">0% complete</span>
            <div class="flex-1 h-[3px] bg-line rounded-full overflow-hidden">
                <div id="progressFill" class="h-full bg-gold rounded-full transition-all duration-300 ease-out" style="width:0%"></div>
            </div>
            <!-- scantron-style dot tracker -->
            <div id="dotTracker" class="hidden sm:flex items-center gap-1"></div>
        </div>
    </div>

    <div class="max-w-2xl mx-auto px-5 sm:px-0 pb-24">

        <!-- Banner & Form Header -->
        <!-- Banner & Form Header -->
        <div class="mt-10 mb-8 bg-white border border-line rounded-2xl shadow-sm overflow-hidden">

            <?php if (!empty($form['banner_image'])): ?>

                <img
                    src="/ims-hosted-last/bms-form/<?= htmlspecialchars($form['banner_image'], ENT_QUOTES, 'UTF-8') ?>"
                    alt="<?= htmlspecialchars($form['title'], ENT_QUOTES, 'UTF-8') ?>"
                    class="w-full object-cover">

            <?php else: ?>

                <div class="h-2 bg-gradient-to-r from-ink via-gold to-ink"></div>

            <?php endif; ?>

            <div class="px-7 sm:px-10 py-8">

                <p class="uppercase tracking-[0.2em] text-[11px] font-semibold text-gold mb-3">
                    Form Response
                </p>

                <h1 class="font-display text-3xl sm:text-[2.15rem] leading-tight text-ink mb-3">
                    <?= htmlspecialchars($form['title'], ENT_QUOTES, 'UTF-8') ?>
                </h1>

                <?php if (!empty($form['description'])): ?>

                    <p class="text-[#5B6070] leading-relaxed">
                        <?= nl2br(htmlspecialchars($form['description'], ENT_QUOTES, 'UTF-8')) ?>
                    </p>

                <?php endif; ?>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-5 pt-5 border-t border-line text-xs text-[#8B8F9C]">

                    <span>
                        <?= $total_questions ?>
                        question<?= $total_questions === 1 ? '' : 's' ?>
                    </span>

                    <span class="w-1 h-1 rounded-full bg-[#C9C2AE]"></span>

                    <span>
                        <span class="text-gold">*</span>
                        indicates a required question
                    </span>

                </div>

            </div>

        </div>

        <form action="submit_response.php" method="post" id="studentForm" class="space-y-4" enctype="multipart/form-data">
            <input type="hidden" name="form_id" value="<?= (int)$form['id'] ?>">

            <?php $qNum = 0; ?>

            <!-- Respondent Email -->
            <?php if ($form['collect_email']): ?>
                <?php $qNum++; ?>
                <div class="field-block bg-white border border-line rounded-2xl px-7 sm:px-10 py-6 transition-shadow hover:shadow-md" data-required="true" data-label="Your email address">
                    <div class="flex items-start gap-3 mb-3">
                        <span class="font-display text-xs text-white bg-ink rounded-full w-6 h-6 flex items-center justify-center flex-shrink-0 mt-0.5"><?= $qNum ?></span>
                        <label class="font-medium text-ink leading-snug pt-0.5">
                            Your email address <span class="text-gold">*</span>
                        </label>
                    </div>
                    <input type="email" name="respondent_email" required
                        class="field-input w-full border border-line rounded-lg px-4 py-2.5 text-[15px] transition"
                        placeholder="you@example.com">
                    <p class="error-msg text-xs text-gold mt-1.5 hidden">Please provide a valid email address.</p>
                </div>
            <?php endif; ?>

            <!-- Form Questions -->
            <?php foreach ($questions as $q): $qNum++;
                $name = "q_{$q['id']}";
                $is_req = (bool)$q['is_required'];
            ?>
                <div class="field-block bg-white border border-line rounded-2xl px-7 sm:px-10 py-6 transition-shadow hover:shadow-md" data-required="<?= $is_req ? 'true' : 'false' ?>" data-label="<?= htmlspecialchars($q['question_text']) ?>">
                    <div class="flex items-start gap-3 mb-4">
                        <span class="font-display text-xs text-white bg-ink rounded-full w-6 h-6 flex items-center justify-center flex-shrink-0 mt-0.5"><?= $qNum ?></span>
                        <label class="font-medium text-ink leading-snug pt-0.5">
                            <?= htmlspecialchars($q['question_text']) ?>
                            <?php if ($is_req): ?><span class="text-gold">*</span><?php endif; ?>
                        </label>
                    </div>

                    <?php if (!empty($q['question_image'])): ?>
                        <div class="pl-9 mb-4">
                            <img src="<?= htmlspecialchars($q['question_image']) ?>" alt="" class="rounded-lg border border-line max-h-72 w-auto">
                        </div>
                    <?php endif; ?>

                    <div class="pl-9">
                        <!-- SHORT TEXT -->
                        <?php if ($q['question_type'] === 'short_text'): ?>
                            <input type="text" name="<?= $name ?>" <?= $is_req ? 'required' : '' ?>
                                class="field-input w-full border border-line rounded-lg px-4 py-2.5 text-[15px] transition"
                                placeholder="Your answer">

                            <!-- PARAGRAPH -->
                        <?php elseif ($q['question_type'] === 'paragraph'): ?>
                            <textarea name="<?= $name ?>" rows="3" <?= $is_req ? 'required' : '' ?>
                                class="field-input w-full border border-line rounded-lg px-4 py-2.5 text-[15px] transition resize-y"
                                placeholder="Your answer"></textarea>

                            <!-- MULTIPLE CHOICE -->
                        <?php elseif ($q['question_type'] === 'multiple_choice'): ?>
                            <div class="space-y-2.5">
                                <?php foreach ($q['options'] as $opt): ?>
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="radio" name="<?= $name ?>" class="field-input radio-dot"
                                            value="<?= htmlspecialchars($opt['option_text']) ?>" <?= $is_req ? 'required' : '' ?>>
                                        <span class="text-[15px] text-[#3A3F4B] group-hover:text-ink transition"><?= htmlspecialchars($opt['option_text']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <!-- CHECKBOXES -->
                        <?php elseif ($q['question_type'] === 'checkbox'): ?>
                            <div class="space-y-2.5 checkbox-group">
                                <?php foreach ($q['options'] as $opt): ?>
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="checkbox" name="<?= $name ?>[]" class="field-input check-box"
                                            value="<?= htmlspecialchars($opt['option_text']) ?>">
                                        <span class="text-[15px] text-[#3A3F4B] group-hover:text-ink transition"><?= htmlspecialchars($opt['option_text']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <!-- DROPDOWN -->
                        <?php elseif ($q['question_type'] === 'dropdown'): ?>
                            <select name="<?= $name ?>" <?= $is_req ? 'required' : '' ?>
                                class="field-input w-full border border-line rounded-lg px-4 py-2.5 text-[15px] bg-white transition">
                                <option value="">Choose an option</option>
                                <?php foreach ($q['options'] as $opt): ?>
                                    <option value="<?= htmlspecialchars($opt['option_text']) ?>"><?= htmlspecialchars($opt['option_text']) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <!-- LINEAR SCALE -->
                        <?php elseif ($q['question_type'] === 'linear_scale'): ?>
                            <div class="flex items-center justify-between max-w-sm">
                                <?php for ($i = $q['scale_min']; $i <= $q['scale_max']; $i++): ?>
                                    <label class="flex flex-col items-center gap-2 cursor-pointer text-xs text-[#8B8F9C]">
                                        <span><?= $i ?></span>
                                        <input type="radio" name="<?= $name ?>" value="<?= $i ?>" <?= $is_req ? 'required' : '' ?>
                                            class="field-input scale-radio">
                                    </label>
                                <?php endfor; ?>
                            </div>
                            <div class="flex justify-between max-w-sm mt-1 text-[11px] text-[#8B8F9C] italic">
                                <span>Low</span><span>High</span>
                            </div>

                            <!-- DATE -->
                        <?php elseif ($q['question_type'] === 'date'): ?>
                            <input type="date" name="<?= $name ?>" <?= $is_req ? 'required' : '' ?>
                                class="field-input border border-line rounded-lg px-4 py-2.5 text-[15px] transition">

                            <!-- TIME -->
                        <?php elseif ($q['question_type'] === 'time'): ?>
                            <input type="time" name="<?= $name ?>" <?= $is_req ? 'required' : '' ?>
                                class="field-input border border-line rounded-lg px-4 py-2.5 text-[15px] transition">

                            <!-- FILE UPLOAD -->
                        <?php elseif ($q['question_type'] === 'file_upload'): ?>
                            <input type="file" name="<?= $name ?>" <?= $is_req ? 'required' : '' ?>
                                accept="image/png,image/jpeg,image/gif,image/webp,.pdf,.doc,.docx"
                                class="field-input w-full border border-line rounded-lg px-4 py-2.5 text-[15px] bg-white transition">
                            <p class="text-xs text-[#8B8F9C] mt-1">Images or documents, up to 5MB.</p>
                        <?php endif; ?>
                    </div>
                    <p class="error-msg text-xs text-gold mt-2 pl-9 hidden">This is a required question.</p>
                </div>
            <?php endforeach; ?>

            <div class="flex items-center justify-between pt-4 pb-10">
                <p class="text-xs text-[#8B8F9C]">Your response is recorded once you submit.</p>
                <button type="submit"
                    class="bg-ink hover:bg-ink-dark text-white font-medium text-sm px-8 py-3 rounded-full transition shadow-sm hover:shadow-md active:scale-[0.98]">
                    Submit response
                </button>
            </div>
        </form>
    </div>

    <script>
        const form = document.getElementById('studentForm');
        const fields = Array.from(form.querySelectorAll('.field-block'));
        const progressFill = document.getElementById('progressFill');
        const progressLabel = document.getElementById('progressLabel');
        const dotTracker = document.getElementById('dotTracker');

        // Build scantron tracker dots
        fields.forEach((_, i) => {
            const dot = document.createElement('span');
            dot.className = 'w-1.5 h-1.5 rounded-full bg-line transition-colors duration-200';
            dot.dataset.index = i;
            dotTracker.appendChild(dot);
        });
        const dots = Array.from(dotTracker.children);

        function isFieldAnswered(block) {
            const inputs = block.querySelectorAll('input, textarea, select');
            for (const el of inputs) {
                if (el.type === 'radio' || el.type === 'checkbox') {
                    if (el.checked) return true;
                } else if (el.value && el.value.trim() !== '') {
                    return true;
                }
            }
            return false;
        }

        function updateProgress() {
            let answered = 0;
            fields.forEach((block, i) => {
                const done = isFieldAnswered(block);
                if (done) answered++;
                dots[i].classList.toggle('bg-gold', done);
                dots[i].classList.toggle('bg-line', !done);
            });
            const pct = fields.length ? Math.round((answered / fields.length) * 100) : 0;
            progressFill.style.width = pct + '%';
            progressLabel.textContent = pct + '% complete';
        }

        // Form Validation on Submit
        form.addEventListener('submit', function(e) {
            let isValid = true;
            let firstInvalidBlock = null;

            fields.forEach(block => {
                const isRequired = block.dataset.required === 'true';
                const errorMsg = block.querySelector('.error-msg');
                const answered = isFieldAnswered(block);

                if (isRequired && !answered) {
                    isValid = false;
                    block.classList.add('field-error');
                    if (errorMsg) errorMsg.classList.remove('hidden');
                    if (!firstInvalidBlock) firstInvalidBlock = block;
                } else {
                    block.classList.remove('field-error');
                    if (errorMsg) errorMsg.classList.add('hidden');
                }
            });

            if (!isValid) {
                e.preventDefault();
                if (firstInvalidBlock) {
                    firstInvalidBlock.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }
        });

        // Clear error highlights when the user inputs values
        form.addEventListener('input', function(e) {
            updateProgress();
            const block = e.target.closest('.field-block');
            if (block && isFieldAnswered(block)) {
                block.classList.remove('field-error');
                const errorMsg = block.querySelector('.error-msg');
                if (errorMsg) errorMsg.classList.add('hidden');
            }
        });

        form.addEventListener('change', updateProgress);
        updateProgress();
    </script>

</body>

</html>
<?php $conn->close(); ?>
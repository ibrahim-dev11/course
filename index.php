<?php
session_start();
require_once 'config/database.php';

$pdo = getDB();
$userIP = getUserIP();

// تۆماری ناو
$userName = '';
$showNameForm = true;
if (!empty($_SESSION['voter_name'])) {
    $userName = $_SESSION['voter_name'];
    $showNameForm = false;
}

// وەرگرتنی ڕاپرسی چالاک
$stmt = $pdo->prepare("SELECT * FROM polls WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$stmt->execute();
$poll = $stmt->fetch();

$options = [];
$totalVotes = 0;
$hasVoted = false;
$userVoteOption = null;

if ($poll) {
    // وەرگرتنی هەڵبژاردنەکان
    $stmt = $pdo->prepare("
        SELECT o.*, COUNT(v.id) as vote_count 
        FROM options o 
        LEFT JOIN votes v ON o.id = v.option_id 
        WHERE o.poll_id = ? 
        GROUP BY o.id 
        ORDER BY o.sort_order
    ");
    $stmt->execute([$poll['id']]);
    $options = $stmt->fetchAll();

    // ژمارەی کۆی دەنگەکان
    foreach ($options as $opt) {
        $totalVotes += $opt['vote_count'];
    }

    // ئایا بەکارهێنەر پێشتر دەنگی داوە؟
    $stmt = $pdo->prepare("SELECT option_id FROM votes WHERE poll_id = ? AND ip_address = ?");
    $stmt->execute([$poll['id'], $userIP]);
    $voteRecord = $stmt->fetch();
    if ($voteRecord) {
        $hasVoted = true;
        $userVoteOption = $voteRecord['option_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="ckb" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - کۆرسی مۆبایل ئەپلیکەیشن</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/devicons/devicon@latest/devicon.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
</head>
<body>
    <!-- خشتەی شعاعی پاشزەمین -->
    <div class="bg-radial"></div>
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>

    <div class="container">
        <!-- کارتی مامۆستا -->
        <div class="instructor-card">
            <img src="assets/img/instructor.jpg" alt="Ibrahim" class="instructor-photo" onerror="this.style.display='none'">
            <div class="instructor-info">
                <div class="instructor-name-row">
                    <span class="instructor-name">Ibrahim Tech</span>
                </div>
                <div class="instructor-badges">
                    <span class="ibadge"><i class="devicon-flutter-plain colored"></i> Flutter</span>
                    <span class="ibadge"><i class="devicon-laravel-plain colored"></i> Laravel</span>
                </div>
                <p class="instructor-desc">
                       ٤ ساڵ ئەزموون لە کۆدینگ · چەندان پرۆژەی ڕاستەقینەم دروستکردووە
                </p>
            </div>
        </div>

        <!-- کارتی ئاگادارکردنەوە -->
        <div class="announce-card">
            <div class="announce-icon">📢</div>
            <div class="announce-body">
                <h3 class="announce-title">پەیامێک بۆ هەموو بەشدارەکان</h3>
                <p class="announce-text">
                    ئەگەر رێژەی بەشداریکردن و دەنگدان باش بوو، کۆرسێکی تەواو و ئادڤانس دامەزرێنم —
                    لە <strong>ئاستی سەرەتا</strong> تا <strong>ئاستی ئادڤانس</strong>، بەپێی رێژەی بەشداریکردن بری پارەکەش دیاری دەکرێت.
                </p>
                <p class="announce-text">
                    کۆرسەکە بە <strong>شێوازی ئۆنلاین راستەوخۆ</strong> خۆم پێشکەشی دەکەم — Flutter بۆ فرۆنت ئێند، Laravel بۆ باک ئێند.
                </p>
                <div class="announce-footer">🎯 دەنگت گرنگترین هەنگاوە</div>
            </div>
        </div>

        <?php if ($showNameForm): ?>
        <!-- فۆرمی ناو -->
        <div class="name-card" id="nameCard">
            <div class="name-card-icon">👤</div>
            <h2 class="name-card-title">پێش دەنگدان، ناوت بنووسە</h2>
            <form class="name-form" id="nameForm" onsubmit="submitName(event)">
                <input
                    type="text"
                    id="nameInput"
                    class="name-input"
                    placeholder="ناوی خۆت بنووسە..."
                    maxlength="60"
                    autocomplete="name"
                    required
                />
                <button type="submit" class="btn-results">
                    <span>✅</span>
                    <span>بەردەوام بە</span>
                </button>
            </form>
        </div>
        <?php elseif ($poll): ?>
        <!-- کارتی ڕاپرسی -->
        <main class="poll-card" id="pollCard">

            <!-- پرسیار -->
            <div class="poll-header">
                <h2 class="poll-title"><?= sanitize($poll['title']) ?></h2>
                <p class="poll-sub">
                    <i class="devicon-flutter-plain colored"></i> Flutter بۆ فرۆنت ئێند
                    &nbsp;·&nbsp;
                    <i class="devicon-laravel-plain colored"></i> Laravel بۆ باک ئێند
                </p>
            </div>

            <!-- ژمارەی دەنگ -->
            <div class="vote-counter-row">
                <span class="vote-counter-num" id="totalVotes"><?= $totalVotes ?></span>
                <span class="vote-counter-label">کەس بەشداربوون</span>
                <span class="vote-counter-dot"></span>
                <span class="vote-status <?= $poll['is_active'] ? 'status-live' : '' ?>"><?= $poll['is_active'] ? '🟢 چالاک' : '🔴 داخراو' ?></span>
            </div>

            <?php if (!$hasVoted): ?>
            <p class="vote-cta">دەنگت گرنگە — ئێستا بەشداربە 👇</p>
            <?php endif; ?>

            <!-- هەڵبژاردنەکان -->
            <div class="options-grid" id="optionsGrid">
                <?php foreach ($options as $index => $option): 
                    $percentage = $totalVotes > 0 ? round(($option['vote_count'] / $totalVotes) * 100) : 0;
                    $isUserVote = ($userVoteOption == $option['id']);
                    $isYes = $option['option_icon'] === 'yes';
                ?>
                <div class="option-card <?= $isYes ? 'option-yes' : 'option-no' ?> <?= $isUserVote ? 'user-voted' : '' ?> <?= $hasVoted ? 'voted-state' : '' ?> <?= ($isYes && !$hasVoted) ? 'option-featured' : '' ?>"
                     data-option-id="<?= $option['id'] ?>"
                     data-poll-id="<?= $poll['id'] ?>"
                     <?= !$hasVoted ? 'onclick="castVote(this)"' : '' ?>>

                    <div class="option-content">
                        <div class="option-icon-wrap">
                            <span class="option-icon"><?= $isYes ? '🔥' : '🙅' ?></span>
                        </div>
                        <div class="option-text-wrap">
                            <p class="option-text"><?= sanitize($option['option_text']) ?></p>
                            <?php if ($isUserVote): ?>
                            <span class="your-vote-badge">دەنگەکەت ✓</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="option-stats <?= $hasVoted ? 'show' : '' ?>">
                        <div class="progress-wrap">
                            <div class="progress-bar <?= $isYes ? 'bar-yes' : 'bar-no' ?>" 
                                 style="width: <?= $percentage ?>%"
                                 data-width="<?= $percentage ?>">
                            </div>
                        </div>
                        <div class="vote-numbers">
                            <span class="vote-count"><?= number_format($option['vote_count']) ?> دەنگ</span>
                            <span class="vote-percent"><?= $percentage ?>%</span>
                        </div>
                    </div>

                    <?php if (!$hasVoted): ?>
                    <div class="hover-label">کلیک بکە بۆ دەنگدان</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- پەیامی دوای دەنگدان -->
            <div class="voted-message <?= $hasVoted ? 'show' : '' ?>" id="votedMsg">
                <span class="voted-icon">🎉</span>
                <div>
                    <strong>سوپاس بۆ بەشداربوونت!</strong>
                    <p>دەنگەکەت تۆمار کرا.</p>
                </div>
            </div>

        </main>

        <!-- مۆدالی تێبینی -->
        <div class="comment-overlay" id="commentOverlay">
            <div class="comment-modal" id="commentModal">
                <div class="comment-modal-icon" id="commentModalIcon">🔥</div>
                <h3 class="comment-modal-title" id="commentModalTitle">بەڵێ هەڵبژاردیت</h3>
                <p class="comment-modal-sub">تێبینییەکت هەیە؟ (ئارەزوومەندانە)</p>
                <textarea id="commentInput" class="comment-textarea" placeholder="تێبینی یان پێشنیارت بنووسە..." maxlength="300" rows="3"></textarea>
                <div class="comment-modal-actions">
                    <button class="btn-comment-skip" onclick="submitVoteWithComment(false)">بەبێ تێبینی</button>
                    <button class="btn-comment-send" onclick="submitVoteWithComment(true)">
                        <span>📨</span> ناردن
                    </button>
                </div>
            </div>
        </div>

        <?php else: ?>
        <div class="no-poll">
            <span>😔</span>
            <h2>ئێستا ڕاپرسییەک نییە</h2>
            <p>تکایە دواتر هەوڵبدەرەوە</p>
        </div>
        <?php endif; ?>


    </div>

    <!-- لۆدەر -->
    <div class="loader-overlay" id="loaderOverlay">
        <div class="loader-box">
            <div class="loader-spinner"></div>
            <p>دەنگەکەت دادەنرێت...</p>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>

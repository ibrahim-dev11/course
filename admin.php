<?php
session_start();
require_once 'config/database.php';

define('ADMIN_PASSWORD', 'ibrahim2026');

// لۆگین
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_auth'] = true;
    } else {
        $loginError = true;
    }
}

// لۆگ-ئاوت
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_auth']);
    header('Location: admin.php');
    exit;
}

$isAdmin = !empty($_SESSION['admin_auth']);

$pdo = getDB();
$poll = null;
$options = [];
$totalVotes = 0;
$voters = [];

if ($isAdmin) {
    $stmt = $pdo->prepare("SELECT * FROM polls WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $poll = $stmt->fetch();

    if ($poll) {
        $stmt = $pdo->prepare("
            SELECT o.*, COUNT(v.id) as vote_count
            FROM options o
            LEFT JOIN votes v ON o.id = v.option_id
            WHERE o.poll_id = ?
            GROUP BY o.id ORDER BY vote_count DESC
        ");
        $stmt->execute([$poll['id']]);
        $options = $stmt->fetchAll();
        foreach ($options as $opt) { $totalVotes += $opt['vote_count']; }

        $stmt = $pdo->prepare("
            SELECT v.voter_name, v.ip_address, o.option_text, o.option_icon, v.voted_at, v.comment
            FROM votes v
            JOIN options o ON v.option_id = o.id
            WHERE v.poll_id = ?
            ORDER BY v.voted_at DESC
        ");
        $stmt->execute([$poll['id']]);
        $voters = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="ckb" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبۆردی ئەدمین - Ibrahim Tech</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
</head>
<body>
<div class="container">

    <header class="site-header">
        <h1 class="site-title"><span class="title-gradient">Ibrahim Tech</span></h1>
        <?php if ($isAdmin): ?>
        <p class="welcome-name">داشبۆردی ئەدمین 🔐</p>
        <?php endif; ?>
    </header>

    <?php if (!$isAdmin): ?>
    <!-- فۆرمی لۆگین -->
    <div class="name-card">
        <div class="name-card-icon">🔐</div>
        <h2 class="name-card-title">تەنها بۆ ئەدمین</h2>
        <?php if (!empty($loginError)): ?>
        <p style="color:#ef4444;font-size:0.85rem;margin-bottom:1rem;">پاسوۆردەکە هەڵەیە، دووبارە هەوڵبدە</p>
        <?php endif; ?>
        <form class="name-form" method="POST">
            <input type="password" name="password" class="name-input" placeholder="پاسوۆرد..." required autofocus />
            <button type="submit" class="btn-results"><span>🔓</span><span>چوونەژوورەوە</span></button>
        </form>
    </div>

    <?php else: ?>

    <!-- ستاتیستیکی گشتی -->
    <div class="results-overview">
        <div class="overview-card">
            <div class="overview-icon">🗳️</div>
            <div class="overview-num"><?= $totalVotes ?></div>
            <div class="overview-label">کۆی دەنگەکان</div>
        </div>
        <?php
        $namedVoters = array_filter($voters, fn($v) => !empty($v['voter_name']));
        $unnamedVoters = count($voters) - count($namedVoters);
        ?>
        <div class="overview-card">
            <div class="overview-icon">👤</div>
            <div class="overview-num"><?= count($namedVoters) ?></div>
            <div class="overview-label">لەگەڵ ناو</div>
        </div>
        <div class="overview-card">
            <div class="overview-icon">📅</div>
            <div class="overview-num"><?= date('m/d') ?></div>
            <div class="overview-label">ئەمڕۆ</div>
        </div>
    </div>

    <!-- بارەکانی نێتیجە -->
    <?php if ($poll): ?>
    <div class="results-poll-title">
        <h2><?= sanitize($poll['title']) ?></h2>
    </div>
    <div class="results-bars">
        <?php foreach ($options as $i => $opt):
            $pct = $totalVotes > 0 ? round(($opt['vote_count'] / $totalVotes) * 100) : 0;
            $isYes = $opt['option_icon'] === 'yes';
        ?>
        <div class="result-bar-card <?= $isYes ? 'result-yes' : 'result-no' ?>">
            <?php if ($i === 0 && $totalVotes > 0): ?><div class="winner-badge">🏆 بردەوە</div><?php endif; ?>
            <div class="result-bar-top">
                <div class="result-option-text">
                    <span class="result-emoji"><?= $isYes ? '🔥' : '🙅' ?></span>
                    <span><?= sanitize($opt['option_text']) ?></span>
                </div>
                <div class="result-numbers">
                    <span class="result-count"><?= $opt['vote_count'] ?> دەنگ</span>
                    <span class="result-pct"><?= $pct ?>%</span>
                </div>
            </div>
            <div class="result-progress">
                <div class="result-fill <?= $isYes ? 'fill-yes' : 'fill-no' ?>" style="width:0%" data-width="<?= $pct ?>%"></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- لیستی دەنگدەران -->
    <div class="chart-section">
        <h3 class="chart-title">👥 لیستی دەنگدەران (<?= count($voters) ?>)</h3>
        <?php if (empty($voters)): ?>
        <p style="text-align:center;color:#94a3b8;font-size:0.875rem;">هێشتا کەس دەنگی نەداوە</p>
        <?php else: ?>
        <div class="voters-list">
            <?php foreach ($voters as $i => $v):
                $isYesVote = $v['option_icon'] === 'yes';
                $name = !empty($v['voter_name']) ? sanitize($v['voter_name']) : '<em style="color:#94a3b8">نەناسراو</em>';
            ?>
            <div class="voter-row">
                <div style="display:flex;align-items:center;gap:0.5rem">
                    <span style="color:#94a3b8;font-size:0.75rem;min-width:1.2rem"><?= count($voters) - $i ?></span>
                    <span class="voter-name"><?= $name ?></span>
                </div>
                <div style="display:flex;align-items:center;gap:0.75rem">
                    <span class="voter-choice <?= $isYesVote ? 'voter-yes' : 'voter-no' ?>">
                        <?= $isYesVote ? '🔥 بەڵێ' : '🙅 نەخێر' ?>
                    </span>
                    <span style="color:#94a3b8;font-size:0.7rem"><?= date('H:i', strtotime($v['voted_at'])) ?></span>
                </div>
            </div>
            <?php if (!empty($v['comment'])): ?>
            <div class="voter-comment">💬 <?= htmlspecialchars($v['comment'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- دەرچوون -->
    <div class="action-links" style="gap:0.75rem;flex-wrap:wrap">
        <a href="admin.php?logout=1" class="btn-results" style="background:#ef4444">
            <span>🚪</span><span>دەرچوون</span>
        </a>
    </div>

    <?php endif; ?>
</div>

<script src="assets/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        document.querySelectorAll('.result-fill').forEach(el => {
            el.style.transition = 'width 1.2s cubic-bezier(0.4,0,0.2,1)';
            el.style.width = el.dataset.width;
        });
    }, 300);
});
</script>
</body>
</html>

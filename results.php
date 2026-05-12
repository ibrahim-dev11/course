<?php
require_once 'config/database.php';
$pdo = getDB();

$stmt = $pdo->prepare("SELECT * FROM polls WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
$stmt->execute();
$poll = $stmt->fetch();

$options    = [];
$totalVotes = 0;
$winner     = null;
$voters     = [];

if ($poll) {
    $stmt = $pdo->prepare("
        SELECT o.*, COUNT(v.id) as vote_count 
        FROM options o 
        LEFT JOIN votes v ON o.id = v.option_id 
        WHERE o.poll_id = ? 
        GROUP BY o.id 
        ORDER BY vote_count DESC
    ");
    $stmt->execute([$poll['id']]);
    $options = $stmt->fetchAll();
    foreach ($options as $opt) { $totalVotes += $opt['vote_count']; }
    if ($totalVotes > 0) $winner = $options[0];

    // وەرگرتنی لیستی دەنگدەران
    $stmt = $pdo->prepare("
        SELECT v.voter_name, o.option_text, o.option_icon, v.voted_at
        FROM votes v
        JOIN options o ON v.option_id = o.id
        WHERE v.poll_id = ?
        ORDER BY v.voted_at DESC
    ");
    $stmt->execute([$poll['id']]);
    $voters = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ckb" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نێتیجەی ڕاپرسی - <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
</head>
<body>
    <div class="bg-radial"></div>
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <div class="container">
        <header class="site-header">
            <h1 class="site-title"><span class="title-gradient">Ibrahim Tech</span></h1>
        </header>

        <?php if ($poll && $totalVotes > 0): ?>

        <!-- کارتی کۆی دەنگ -->
        <div class="results-overview">
            <div class="overview-card">
                <div class="overview-icon">🗳️</div>
                <div class="overview-num"><?= number_format($totalVotes) ?></div>
                <div class="overview-label">کۆی دەنگەکان</div>
            </div>
            <?php if ($winner): 
                $winnerPct = round(($winner['vote_count'] / $totalVotes) * 100);
            ?>
            <div class="overview-card winner-card">
             
                <div class="overview-num"><?= $winnerPct ?>%</div>
                <div class="overview-label">
            
                </div>
            </div>
            <?php endif; ?>
            <div class="overview-card">
                <div class="overview-icon">📅</div>
                <div class="overview-num"><?= date('Y/m/d') ?></div>
                <div class="overview-label">ئەمڕۆ</div>
            </div>
        </div>

        <!-- سەردێڕی پرسیار -->
        <div class="results-poll-title">
            <h2><?= sanitize($poll['title']) ?></h2>
        </div>

        <!-- بارەکانی نێتیجە -->
        <div class="results-bars">
            <?php foreach ($options as $index => $option):
                $pct = $totalVotes > 0 ? round(($option['vote_count'] / $totalVotes) * 100) : 0;
                $isYes = $option['option_icon'] === 'yes';
                $isWinner = ($index === 0 && $totalVotes > 0);
            ?>
            <div class="result-bar-card <?= $isYes ? 'result-yes' : 'result-no' ?>">
                <?php if ($isWinner): ?>
                <div class="winner-badge">🏆 بردەوە</div>
                <?php endif; ?>
                <div class="result-bar-top">
                    <div class="result-option-text">
                        <span class="result-emoji"><?= $isYes ? '🔥' : '🙅' ?></span>
                        <span><?= sanitize($option['option_text']) ?></span>
                    </div>
                    <div class="result-numbers">
                        <span class="result-count"><?= number_format($option['vote_count']) ?> دەنگ</span>
                        <span class="result-pct"><?= $pct ?>%</span>
                    </div>
                </div>
                <div class="result-progress">
                    <div class="result-fill <?= $isYes ? 'fill-yes' : 'fill-no' ?>" 
                         style="width:0%" 
                         data-width="<?= $pct ?>%">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- چارتی دایەرەیی (SVG خاڵی) -->
        <div class="chart-section">
            <h3 class="chart-title">📈 پەخشکردنی دەنگەکان</h3>
            <div class="donut-chart-wrap">
                <?php
                $yesCount  = 0; $noCount = 0; $yesPct = 0; $noPct = 0;
                foreach ($options as $opt) {
                    if ($opt['option_icon'] === 'yes') { $yesCount = $opt['vote_count']; $yesPct = $totalVotes > 0 ? round(($yesCount/$totalVotes)*100) : 0; }
                    if ($opt['option_icon'] === 'no')  { $noCount  = $opt['vote_count']; $noPct  = $totalVotes > 0 ? round(($noCount /$totalVotes)*100) : 0; }
                }
                $r = 80; $c = 2 * M_PI * $r;
                $yesDash = ($yesPct / 100) * $c;
                $noDash  = ($noPct  / 100) * $c;
                ?>
                <svg viewBox="0 0 220 220" class="donut-svg">
                    <circle cx="110" cy="110" r="<?= $r ?>" fill="none" stroke="#1e2337" stroke-width="28"/>
                    <?php if ($yesPct > 0): ?>
                    <circle cx="110" cy="110" r="<?= $r ?>" fill="none" stroke="#22c55e" stroke-width="28"
                        stroke-dasharray="<?= $yesDash ?> <?= $c - $yesDash ?>"
                        stroke-dashoffset="<?= $c * 0.25 ?>"
                        transform="rotate(-90 110 110)" class="donut-segment"/>
                    <?php endif; ?>
                    <?php if ($noPct > 0): ?>
                    <circle cx="110" cy="110" r="<?= $r ?>" fill="none" stroke="#ef4444" stroke-width="28"
                        stroke-dasharray="<?= $noDash ?> <?= $c - $noDash ?>"
                        stroke-dashoffset="<?= $c * 0.25 - $yesDash ?>"
                        transform="rotate(-90 110 110)" class="donut-segment"/>
                    <?php endif; ?>
                    <text x="110" y="105" text-anchor="middle" class="donut-center-num"><?= $totalVotes ?></text>
                    <text x="110" y="125" text-anchor="middle" class="donut-center-lbl">کۆی دەنگ</text>
                </svg>
                <div class="donut-legend">
                    <div class="legend-item"><span class="legend-dot dot-yes"></span><span>بەڵێ: <strong><?= $yesPct ?>%</strong></span></div>
                    <div class="legend-item"><span class="legend-dot dot-no"></span><span>نەخێر: <strong><?= $noPct ?>%</strong></span></div>
                </div>
            </div>
        </div>

        <!-- لیستی دەنگدەران -->
        <?php if (!empty($voters)): ?>
        <div class="chart-section">
            <h3 class="chart-title">👥 دەنگدەران</h3>
            <div class="voters-list">
                <?php foreach ($voters as $v):
                    $isYesVote = $v['option_icon'] === 'yes';
                ?>
                <div class="voter-row">
                    <span class="voter-name"><?= $v['voter_name'] ? sanitize($v['voter_name']) : 'نەناسراو' ?></span>
                    <span class="voter-choice <?= $isYesVote ? 'voter-yes' : 'voter-no' ?>">
                        <?= $isYesVote ? '🔥 بەڵێ' : '🙅 نەخێر' ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php elseif ($poll): ?>
        <div class="no-poll">
            <span>📭</span>
            <h2>هێشتا کەس دەنگی نەداوە</h2>
            <p>یەکەمین بە و دەنگت بدە!</p>
            <a href="index.php" class="btn-results" style="display:inline-flex;margin-top:1.5rem">🗳️ دەنگبدە</a>
        </div>
        <?php else: ?>
        <div class="no-poll"><span>😔</span><h2>ڕاپرسییەک نییە</h2></div>
        <?php endif; ?>

        <div class="action-links">
            <a href="index.php" class="btn-results">
                <span>🗳️</span><span>گەڕانەوە بۆ دەنگدان</span>
            </a>
        </div>


    </div>

    <script src="assets/js/app.js"></script>
    <script>
    // ئەنیمیشنی بارەکان
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

<?php
/**
 * 페이지 공용 레이아웃: <html> .. <header> 까지의 prelude 와
 * 페이지 본문 다음의 GNB / </body> 까지의 postlude.
 *
 * 사용:
 *   require __DIR__ . '/lib/util.php';
 *   require __DIR__ . '/lib/layout.php';
 *   render_prelude([
 *       'title' => '바른케어플러스',
 *       'show_back' => true,
 *       'back_url' => 'find.php?s=2',
 *       'active' => 'find',  // GNB highlight: home|about|find|reg|claim
 *   ]);
 *   ...본문...
 *   render_postlude();
 */
declare(strict_types=1);

function render_prelude(array $opts = []): void {
    $title    = $opts['title']    ?? '바른케어플러스';
    $showBack = $opts['show_back'] ?? false;
    $backUrl  = $opts['back_url']  ?? 'index.php';
    $active   = $opts['active']    ?? '';
    ?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1"/>
<title><?= h($title) ?></title>
<link rel="stylesheet" href="styles.css"/>
</head>
<body>
<div id="root">
<div class="shell">
<header class="hdr">
  <?php if ($showBack): ?>
  <div class="hdr-left">
    <a class="back-btn" href="<?= attr($backUrl) ?>">
      <svg width="20" height="20" fill="none"><path d="M13 4L7 10l6 6" stroke="#626262" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>뒤로
    </a>
  </div>
  <?php endif; ?>
  <a href="index.php" aria-label="홈으로" style="padding:0;display:flex;align-items:center">
    <img src="./ui/project/assets/logo.png" alt="바른케어플러스" class="hdr-logo"/>
  </a>
  <div class="hdr-right">
    <button class="icon-btn" type="button" onclick="document.getElementById('gnb').classList.add('open')">
      <svg width="22" height="15" fill="none"><rect y="0" width="22" height="2" rx="1" fill="#222"/><rect y="6.5" width="22" height="2" rx="1" fill="#222"/><rect y="13" width="22" height="2" rx="1" fill="#222"/></svg>
    </button>
  </div>
</header>
<?php
}

function render_postlude(string $active = ''): void {
    $items = [
        ['key'=>'about', 'label'=>'바른케어플러스 소개', 'url'=>'about.php'],
        ['key'=>'find',  'label'=>'간병인 찾기',        'url'=>'find.php?s=intro'],
        ['key'=>'reg',   'label'=>'간병인 등록',        'url'=>'reg.php?s=intro'],
        ['key'=>'claim', 'label'=>'간병비 보험금 청구', 'url'=>'claim.php?s=intro'],
    ];
    ?>
<div id="gnb" class="gnb-overlay">
  <div class="gnb-back" onclick="document.getElementById('gnb').classList.remove('open')"></div>
  <div class="gnb-panel">
    <div class="gnb-hdr">
      <img src="./ui/project/assets/logo.png" alt="" style="height:28px"/>
      <button class="icon-btn" type="button" onclick="document.getElementById('gnb').classList.remove('open')">
        <svg width="20" height="20" fill="none"><path d="M3 3l14 14M17 3L3 17" stroke="#222" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <?php foreach ($items as $it):
        $isActive = $it['key'] === $active;
        $cls = $isActive ? 'gnb-item active-item' : 'gnb-item';
        $color = $isActive ? '#EF7F4E' : '#ADADAD';
    ?>
    <a class="<?= $cls ?>" href="<?= attr($it['url']) ?>">
      <span><?= h($it['label']) ?></span>
      <svg width="16" height="16" fill="none"><path d="M3 8h10M10 4l4 4-4 4" stroke="<?= $color ?>" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
    <?php endforeach; ?>
  </div>
</div>
</div><!-- /shell -->
</div><!-- /root -->
</body>
</html>
<?php
}

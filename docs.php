<?php
require __DIR__ . '/lib/util.php';
require __DIR__ . '/lib/layout.php';
render_prelude(['title'=>'필수 서류 안내', 'show_back'=>true, 'back_url'=>'index.php']);

$docs = [
    ['간병인등록증',    '간병인 본인이 발급받은 등록 증빙'],
    ['간병비계좌이체내역','간병비를 지급한 계좌이체 영수증'],
    ['간병일지',        '실제 간병 수행 내역을 기록한 일지'],
    ['입퇴원확인서',    '병원에서 발급한 입원·퇴원 확인 서류'],
    ['진단서',          '담당의가 발급한 진단 내역'],
    ['보험사양식',      '가입한 보험사가 요구하는 청구 양식'],
];
?>
<div class="content" style="background:#fff">
  <div style="padding:24px 24px 12px">
    <h2 style="font-size:22px;font-weight:700;color:#111;letter-spacing:-.5px">필수 서류 안내</h2>
    <hr style="margin-top:14px;border:none;border-top:1px solid #111"/>
  </div>
  <div style="padding:16px 24px 32px;display:flex;flex-direction:column;gap:10px">
    <?php foreach ($docs as [$title, $sub]): ?>
    <div style="background:#F5F5F5;border-radius:14px;padding:18px 20px;display:flex;align-items:center;gap:14px">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" style="flex-shrink:0">
        <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" stroke="#F08457" stroke-width="1.2" stroke-linejoin="round" fill="#fff"/>
        <path d="M14 3v5h5" stroke="#F08457" stroke-width="1.2" stroke-linejoin="round"/>
        <path d="M8 12h8M8 15h8M8 18h5" stroke="#F08457" stroke-width="1" stroke-linecap="round"/>
      </svg>
      <div style="flex:1">
        <div style="font-size:18px;font-weight:700;color:#111;margin-bottom:2px;letter-spacing:-.3px"><?= h($title) ?></div>
        <div style="font-size:13px;color:#626262;line-height:1.45"><?= h($sub) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<div class="cta-wrap">
  <a class="cta" href="index.php" style="background:#5CB85C;text-align:center;display:block">확인</a>
</div>
<?php render_postlude(); ?>

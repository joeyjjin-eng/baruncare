<?php
require __DIR__ . '/lib/util.php';
require __DIR__ . '/lib/layout.php';
render_prelude(['title'=>'청구 프로세스', 'show_back'=>true, 'back_url'=>'index.php']);

$steps = [
    ['간병인 등록 및 매칭', ['가족간병일 경우, 가족을 간병인으로 등록하고 환자와 매칭계약을 합니다.', '계약서를 반드시 작성하여 간병 기간과 비용을 명시합니다.'], null],
    ['간병일지 작성', ['실제 간병을 수행하며 간병일지를 꼭 작성해야 합니다.'], null],
    ['간병비 결제(퇴원 시)', ['환자(보험가입자)가 간병인에게 직접 간병비를 이체하거나 플랫폼을 통해 결제합니다.', '1일 간병비는 최소 10만원 이상으로 정산하여야 합니다.'], null],
    ['필수 서류 준비', ['보험사에 제출할 아래 서류들을 발급받습니다.'], ['간병인등록증빙','간병비계좌이체내역','간병일지','입퇴원확인서','진단서','보험금청구서']],
];
?>
<div class="content" style="background:#fff">
  <div style="padding:24px 24px 12px">
    <h2 style="font-size:22px;font-weight:700;color:#111;letter-spacing:-.5px">청구 프로세스</h2>
    <hr style="margin-top:14px;border:none;border-top:1px solid #111"/>
  </div>
  <div style="padding:8px 24px 24px;display:flex;flex-direction:column;gap:22px">
    <?php foreach ($steps as $i => [$title, $lines, $docs]): ?>
    <div>
      <div style="font-size:18px;font-weight:700;color:#111;margin-bottom:10px;letter-spacing:-.3px"><?= $i+1 ?>. <?= h($title) ?></div>
      <?php foreach ($lines as $line): ?>
      <p style="font-size:16px;color:#333;line-height:1.55;margin-bottom:4px"><?= h($line) ?></p>
      <?php endforeach; ?>
      <?php if ($docs): ?>
      <div style="margin-top:12px;background:#F5F5F5;border-radius:10px;padding:16px 18px;display:flex;flex-direction:column;gap:8px">
        <?php foreach ($docs as $d): ?>
        <div style="font-size:16px;color:#444;display:flex;align-items:center;gap:8px">
          <span style="color:#5CB85C;font-weight:700">✓</span>
          <span><?= h($d) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<div class="cta-wrap">
  <a class="cta" href="index.php" style="background:#5CB85C;text-align:center;display:block">확인</a>
</div>
<?php render_postlude(); ?>

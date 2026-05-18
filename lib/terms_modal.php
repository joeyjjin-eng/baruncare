<?php
/**
 * 약관 동의 레이어(모달) 마크업 + 토글 JS.
 *
 * render_terms_modal($terms, $formAction)
 *   $terms      - util.php 의 find_terms()/reg_terms()/claim_terms() 결과
 *   $formAction - "다음" 클릭 시 POST 되는 URL (보통 같은 페이지 + ?action=submit)
 */
declare(strict_types=1);

function render_terms_modal(array $terms, string $formAction): void {
    ?>
<div id="terms-overlay" class="terms-overlay">
  <div class="terms-back" onclick="termsClose()"></div>
  <div class="terms-sheet">
    <form id="terms-form" method="post" action="<?= attr($formAction) ?>">
      <div class="terms-body">
        <button id="terms-all-btn" type="button" class="terms-all" onclick="termsToggleAll()">
          <svg id="terms-all-check" class="terms-check-svg" width="22" height="22" viewBox="0 0 22 22" fill="none">
            <path d="M5 11.5l4 4 8-9" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <span class="terms-all-text">약관 전체동의</span>
        </button>
        <div class="terms-list">
          <?php foreach ($terms as $t): ?>
          <div class="terms-row" onclick="termsToggleOne('<?= attr($t['key']) ?>', event)">
            <svg id="terms-check-<?= attr($t['key']) ?>" class="terms-check-svg" width="22" height="22" viewBox="0 0 22 22" fill="none">
              <path d="M5 11.5l4 4 8-9" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <input type="checkbox" name="agree[<?= attr($t['key']) ?>]" id="agree-<?= attr($t['key']) ?>"
              value="1" <?= !empty($t['required']) ? 'data-required="1"' : '' ?>
              style="position:absolute;opacity:0;pointer-events:none"
              onchange="termsSyncFromCheckbox()"/>
            <span class="terms-row-text">
              <?= h($t['label']) ?><span class="req"><?= !empty($t['required']) ? '[필수]' : '[선택]' ?></span>
            </span>
            <?php if (!empty($t['link'])): ?>
            <a class="terms-chevron" href="<?= attr($t['link']) ?>" target="_blank" rel="noopener noreferrer"
               aria-label="<?= attr($t['label']) ?> 상세보기" onclick="event.stopPropagation()">
              <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M7 4l5 5-5 5" stroke="#9A9A9A" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <?php else: ?>
            <span class="terms-chevron"><svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M7 4l5 5-5 5" stroke="#9A9A9A" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="terms-cta-wrap">
        <button type="button" class="terms-back-btn" onclick="termsClose()">뒤로</button>
        <button id="terms-submit" type="submit" class="terms-next-btn" disabled>다음</button>
      </div>
    </form>
  </div>
</div>
<script>
function termsOpen(){document.getElementById('terms-overlay').classList.add('open');}
function termsClose(){document.getElementById('terms-overlay').classList.remove('open');}
function termsToggleOne(key, evt){
  if(evt && evt.target && evt.target.closest && evt.target.closest('.terms-chevron')) return;
  var cb = document.getElementById('agree-'+key);
  cb.checked = !cb.checked;
  termsSyncFromCheckbox();
}
function termsToggleAll(){
  var cbs = document.querySelectorAll('#terms-form input[type=checkbox]');
  var anyOff = false;
  cbs.forEach(function(c){if(!c.checked) anyOff = true;});
  cbs.forEach(function(c){c.checked = anyOff;});
  termsSyncFromCheckbox();
}
function termsSyncFromCheckbox(){
  var cbs = document.querySelectorAll('#terms-form input[type=checkbox]');
  var allOn = true, reqAllOn = true;
  cbs.forEach(function(c){
    var key = c.id.replace('agree-','');
    var svg = document.getElementById('terms-check-'+key);
    if(svg) svg.classList.toggle('on', c.checked);
    if(!c.checked) allOn = false;
    if(c.dataset.required==='1' && !c.checked) reqAllOn = false;
  });
  var allBtn = document.getElementById('terms-all-btn');
  var allCheck = document.getElementById('terms-all-check');
  if(allBtn) allBtn.classList.toggle('on', allOn);
  if(allCheck) allCheck.classList.toggle('on', allOn);
  document.getElementById('terms-submit').disabled = !reqAllOn;
}
document.getElementById('terms-form').addEventListener('submit', function(e){
  var btn = document.getElementById('terms-submit');
  btn.disabled = true; btn.textContent = '전송 중…';
});
</script>
<?php
}

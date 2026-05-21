<?php
require __DIR__ . '/lib/util.php';
require __DIR__ . '/lib/layout.php';
render_prelude(['title'=>'서비스 소개', 'show_back'=>true, 'back_url'=>'index.php', 'active'=>'about']);

$services = [
    ['icn_intro_01.png', '간병인 매칭 서비스'],
    ['icn_intro_02.png', '가족 간병인 / 전문 간병인 등록'],
    ['icn_intro_03.png', '간병비 보험금 청구 도우미'],
    ['icn_intro_04.png', '간병 관련 상담 및 정보 제공'],
];
$trust = [
    ['icn_intro_05.png', '철저한 간병인 인증 절차', '0'],
    ['icn_intro_06.png', '실제 이용자 후기 기반 매칭 시스템', '40px'],
    ['icn_intro_07.png', '개인정보 보호 및 안전한 매칭 구조', '14px'],
    ['icn_intro_08.png', '간병 관련 전문 상담 지원', '60px'],
];
$regList = ['가족 간병인일 경우!', '간병인으로 일하고 싶으신 분', '경력자 / 초보자 모두 지원 가능'];
?>
<div class="content home-bg" style="background:#fff">
  <img src="./ui/project/assets/img_intro_01.png" alt="" class="about-hero-img"/>

  <div class="about-section">
    <h2 class="about-section-title">바른케어서비스는</h2>
    <p class="about-section-sub">간병이 처음이라 막막한 분들도,
간병인이 필요하신 분들도
누구나 쉽고 편하게 이용할 수 있습니다.</p>
    <div class="about-services-list">
      <?php foreach ($services as [$icon, $text]): ?>
      <div class="about-service-row">
        <img src="./ui/project/assets/<?= h($icon) ?>" alt="" class="about-service-img"/>
        <div class="about-service-text"><?= h($text) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="about-section about-section--gray">
    <h2 class="about-section-title">간병은 신뢰가 가장 중요합니다.</h2>
    <p class="about-section-sub">저희는 검증된 간병인만 연결하며
이용자의 안전과 만족을 최우선으로 생각합니다.</p>
    <div class="about-trust-list">
      <?php foreach ($trust as [$icon, $text, $offset]): ?>
      <div class="about-trust-pill" style="margin-left:<?= h($offset) ?>">
        <img src="./ui/project/assets/<?= h($icon) ?>" alt="" class="about-trust-img"/>
        <span><?= h($text) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <img src="./ui/project/assets/img_intro_02.png" alt="" class="about-hero-img"/>

  <div class="about-section">
    <div class="about-reg-head">
      <img src="./ui/project/assets/icn_intro_09.png" alt="" width="28" height="28"/>
      <h2 class="about-section-title" style="margin:0">간병인 등록은 누가 하나요?</h2>
    </div>
    <div class="about-reg-card">
      <?php foreach ($regList as $t): ?>
      <div class="about-reg-item">
        <span style="color:#5CB85C;font-weight:700;font-size:16px">✓</span>
        <span><?= h($t) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="about-reg-closing">누구나 자신의 상황에 맞게<br/>아주 쉽고 간단한 절차만으로<br/>간병인 활동을 시작할 수 있습니다.</p>
  </div>

  <div class="about-contact">
    <p class="about-contact-title">고민하지말고<br/>궁금한 것이 있다면 바로 문의주세요!</p>
    <a class="about-contact-btn" href="http://pf.kakao.com/_xgmTbX" target="_blank" rel="noopener noreferrer">
      <span class="about-contact-btn-left">
        <img src="./ui/project/assets/icn_kakao.png" alt="카카오톡" width="34" height="34"/>
        <span>카카오톡 문의</span>
      </span>
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M7 17L17 7M17 7H9M17 7v8" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
  </div>

  <div class="home-footer">
    <img src="./ui/project/assets/logo.png" alt="바른케어플러스" class="home-footer-logo"/>
    <p class="home-footer-text">(주)천호인터내셔널 | 대구광역시 중구 달구벌대로 1929, 2층<br/>대표 이승엽 | 사업자등록번호 342-81-00261<br/>개인정보보호 담당자 이승엽 | 고객센터 cs@weplat.co.kr</p>
    <p class="home-footer-copy">Copyright ⓒ 천호인터내셔널</p>
  </div>
</div>
<?php render_postlude('about'); ?>

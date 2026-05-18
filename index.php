<?php
require __DIR__ . '/lib/util.php';
require __DIR__ . '/lib/layout.php';
render_prelude(['title'=>'바른케어플러스', 'active'=>'home']);
?>
<div class="content home-bg" style="background:#F2F2F2">
  <div class="home-top">
    <p class="home-headline">간병인찾기부터
간병비 보험 청구까지
간병을 위한 모든 것!</p>
    <a class="home-svc-btn" href="about.php">
      서비스 소개
      <span class="home-svc-arrow">
        <svg width="14" height="12" viewBox="0 0 14 12" fill="none"><path d="M1 6h12M8 1l5 5-5 5" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
    </a>
  </div>

  <div class="home-main-card">
    <div class="home-main-card-top" style="padding:30px 18px 0 28px">
      <p class="home-main-card-text" style="line-height:1.4;font-weight:400;font-size:16px">간병인이 필요하신가요?
필요하신가요.
간병인을 매칭해드립니다!</p>
      <span class="home-share-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
          <circle cx="18" cy="5" r="3" stroke="#ADADAD" stroke-width="1.8"/>
          <circle cx="6" cy="12" r="3" stroke="#ADADAD" stroke-width="1.8"/>
          <circle cx="18" cy="19" r="3" stroke="#ADADAD" stroke-width="1.8"/>
          <path d="M8.5 10.5l7-4M8.5 13.5l7 4" stroke="#ADADAD" stroke-width="1.8"/>
        </svg>
      </span>
    </div>
    <img src="./ui/project/assets/illustration.png" alt="간병 서비스 일러스트" class="home-illustration" style="object-fit:fill;padding:0;width:342px;height:241px;margin:8px 0 0 24px"/>
    <div style="padding:0 18px 22px">
      <a class="home-find-btn" href="find.php?s=intro" style="margin:0;width:100%;background:#F08457">
        <span>간병인 찾기</span>
        <span>→</span>
      </a>
    </div>
  </div>

  <div class="home-mini-cards">
    <a class="home-mini-card" href="reg.php?s=intro" style="padding:22px 22px 18px;width:190px;height:192px">
      <div class="home-mini-icon"><img src="./ui/project/assets/icon-register.png" alt="간병인 등록" width="65" height="65"/></div>
      <p class="home-mini-sub">간병인으로 일하고 싶거나
가족간병인인 경우</p>
      <p class="home-mini-title" style="color:#11B3A8">간병인 등록</p>
    </a>
    <a class="home-mini-card" href="claim.php?s=intro" style="padding:22px;width:188px">
      <div class="home-mini-icon"><img src="./ui/project/assets/icon-insurance.png" alt="간병비 보험 청구" width="65" height="65"/></div>
      <p class="home-mini-sub">간병비 보험금 청구가
어렵다면</p>
      <p class="home-mini-title" style="color:#008CFF">간병비 보험 청구</p>
    </a>
  </div>

  <p class="home-info-label">궁금한 점이 더 있으신가요?</p>
  <div class="home-info-card">
    <a class="home-info-row" href="process.php">
      <div style="display:flex;align-items:center">
        <div class="home-info-icon-wrap" style="background:#FFF4D3"><img src="./ui/project/assets/icon-process.png" alt="" width="32" height="32"/></div>
        <span class="home-info-text">간병비 보험 청구 프로세스</span>
      </div>
    </a>
    <hr class="home-info-divider"/>
    <a class="home-info-row" href="docs.php">
      <div style="display:flex;align-items:center">
        <div class="home-info-icon-wrap" style="background:#FAEAFE"><img src="./ui/project/assets/icon-docs.png" alt="" width="32" height="32"/></div>
        <span class="home-info-text">간병비 보험 청구 필수서류</span>
      </div>
    </a>
  </div>

  <a class="home-kakao" href="https://pf.kakao.com/_uAxhxaG" target="_blank" rel="noopener noreferrer">
    <div class="home-kakao-left">
      <div class="home-kakao-icon"><img src="./ui/project/assets/icn_kakao.png" alt="카카오톡" width="46" height="46"/></div>
      <span class="home-kakao-label">카카오톡 문의</span>
    </div>
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M7 17L17 7M17 7H9M17 7v8" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
  </a>

  <div class="home-footer">
    <img src="./ui/project/assets/logo.png" alt="바른케어플러스" class="home-footer-logo"/>
    <p class="home-footer-text">(주)천호인터내셔널 | 대구광역시 중구 달구벌대로 1929, 2층<br/>대표 이승엽 | 사업자등록번호 342-81-00261<br/>개인정보보호 담당자 이승엽 | 고객센터 cs@weplat.co.kr</p>
    <p class="home-footer-copy">Copyright ⓒ 천호인터내셔널</p>
  </div>
</div>
<?php render_postlude('home'); ?>

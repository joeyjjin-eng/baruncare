<?php
/**
 * 간병인 등록 — 다단계 폼.
 * GET ?s=intro|0|1|2|review|done
 */
declare(strict_types=1);
require __DIR__ . '/lib/util.php';
require __DIR__ . '/lib/layout.php';
require __DIR__ . '/lib/terms_modal.php';

$FLOW = 'reg';
$s = $_GET['s'] ?? 'intro';
$edit = !empty($_GET['edit']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_GET['action'] ?? '') === 'submit') {
        $agree = $_POST['agree'] ?? [];
        foreach (['tos','privacy','thirdparty'] as $k) {
            if (empty($agree[$k])) redirect('reg.php?s=review&err=terms');
        }
        insert_reg();
        flow_clear($FLOW);
        redirect('reg.php?s=done');
    }
    save_step((int)$s, !empty($_POST['_edit']));
    $next = $edit || !empty($_POST['_edit']) ? 'review' : ((int)$s + 1);
    redirect('reg.php?s=' . $next);
}

if ($s === 'intro') render_intro();
elseif ($s === 'done') render_done();
elseif ($s === 'review') render_review();
elseif (in_array($s, ['0','1','2'], true)) render_step((int)$s, $edit);
else redirect('reg.php?s=intro');


function save_step(int $s, bool $edit): void {
    global $FLOW;
    $cur = flow_get($FLOW, 'data');
    if ($s === 0) {
        $cur['type'] = in_enum($_POST['type'] ?? '', ['일반간병인','가족간병인']);
    } elseif ($s === 1) {
        $cur['name']     = in_name($_POST['name']   ?? '');
        $cur['gender']   = in_enum($_POST['gender'] ?? '', ['남성','여성']);
        $cur['phone']    = in_phone($_POST['phone']  ?? '');
        $cur['dob']      = in_dob($_POST['dob']      ?? '');
        $cur['city']     = in_str($_POST['city']     ?? '', 50);
        $cur['district'] = in_str($_POST['district'] ?? '', 50);
    } elseif ($s === 2) {
        $cur['patientName']     = in_name($_POST['patientName']     ?? '');
        $cur['patientGender']   = in_enum($_POST['patientGender']   ?? '', ['남성','여성']);
        $cur['patientDob']      = in_dob($_POST['patientDob']       ?? '');
        $cur['patientPhone']    = in_phone($_POST['patientPhone']    ?? '');
        $cur['patientCity']     = in_str($_POST['patientCity']      ?? '', 50);
        $cur['patientDistrict'] = in_str($_POST['patientDistrict']  ?? '', 50);
    }
    flow_set($FLOW, 'data', $cur);
}

function insert_reg(): void {
    global $FLOW;
    $d = flow_get($FLOW, 'data');
    try {
        $sql = "INSERT INTO caregiver_registrations
          (cg_type, cg_name, cg_gender, cg_dob, cg_phone, cg_city, cg_district,
           pt_name, pt_gender, pt_dob, pt_phone, pt_city, pt_district,
           user_agent, ip_addr) VALUES
          (:cg_type, :cg_name, :cg_gender, :cg_dob, :cg_phone, :cg_city, :cg_district,
           :pt_name, :pt_gender, :pt_dob, :pt_phone, :pt_city, :pt_district,
           :user_agent, :ip_addr)";
        $stmt = db()->prepare($sql);
        $stmt->execute([
            ':cg_type'=>$d['type']??'', ':cg_name'=>$d['name']??'',
            ':cg_gender'=>$d['gender']??'', ':cg_dob'=>$d['dob']??'',
            ':cg_phone'=>$d['phone']??'', ':cg_city'=>$d['city']??null,
            ':cg_district'=>$d['district']??null,
            ':pt_name'=>$d['patientName']??null,
            ':pt_gender'=>($d['patientGender']??'')!==''?$d['patientGender']:null,
            ':pt_dob'=>($d['patientDob']??'')!==''?$d['patientDob']:null,
            ':pt_phone'=>($d['patientPhone']??'')!==''?$d['patientPhone']:null,
            ':pt_city'=>$d['patientCity']??null,
            ':pt_district'=>$d['patientDistrict']??null,
            ':user_agent'=>user_agent(), ':ip_addr'=>client_ip(),
        ]);
    } catch (PDOException $e) {
        error_log('[reg] '.$e->getMessage());
        redirect('reg.php?s=review&err=db');
    }
}


function render_intro(): void {
    render_prelude(['title'=>'간병인 등록','show_back'=>true,'back_url'=>'index.php','active'=>'reg']);
    $features = [
        ['icn_condition.png', '경력자 / 초보자 모두 지원 가능'],
        ['icn_match.png',     "원하는 시간과 지역, 급여 조건에 맞춰\n나에게 꼭 맞는 일자리"],
        ['icn_staff.png',     '꼼꼼한 검증을 거쳐 누구나 안심할 수 있는 매칭'],
    ];
    ?>
    <div class="content ri-content">
      <div class="ri-illust-wrap"><img src="./ui/project/assets/intro_illust_02.png" alt="간병인 등록 일러스트"/></div>
      <h2 class="ri-title">간병인 등록,<br/>목적에 맞게 쉽고 빠르게 진행하세요.</h2>
      <div class="ri-bullets">
        <p class="ri-bullet">· 전문적인 돌봄 일자리를 찾고 계시나요?</p>
        <p class="ri-bullet">· 혹은 가족을 직접 돌보고
   간병비 보험을 청구하실 계획이신가요?</p>
      </div>
      <div class="ri-info">💡 복잡한 서류 작업 없이, 모바일에서 간편하게 정식 간병인 등록을 시작해 보세요.</div>
      <div class="ri-feature-list">
        <?php foreach ($features as [$icon, $text]): ?>
        <div class="ri-feature">
          <div class="ri-feature-icon"><img src="./ui/project/assets/<?= h($icon) ?>" alt=""/></div>
          <div class="ri-feature-text"><?= h($text) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <div style="height:12px"></div>
    </div>
    <div class="cta-wrap"><a class="cta" href="reg.php?s=0" style="text-align:center;display:block">간병인 등록하기</a></div>
    <?php
    render_postlude('reg');
}

function render_step(int $s, bool $edit): void {
    global $FLOW;
    $back = $s === 0 ? 'reg.php?s=intro' : ('reg.php?s=' . ($s-1));
    if ($edit) $back = 'reg.php?s=review';
    render_prelude(['title'=>'간병인 등록','show_back'=>true,'back_url'=>$back,'active'=>'reg']);
    echo pr_seg(3, $s);
    $d = flow_get($FLOW, 'data');
    $editIn = $edit ? '<input type="hidden" name="_edit" value="1">' : '';
    ?>
    <form method="post" action="reg.php?s=<?= $s ?>" style="display:contents">
    <?= $editIn ?>
    <div class="content">
    <?php if ($s === 0): ?>
      <div class="section-label">간병인 유형</div>
      <div class="toggle-wrap">
        <?php foreach (['일반간병인','가족간병인'] as $t): ?>
        <label style="flex:1;position:relative">
          <input class="toggle-radio" type="radio" name="type" value="<?= attr($t) ?>" required <?= ($d['type']??'')===$t?'checked':'' ?>/>
          <span class="toggle-btn" style="display:flex;align-items:center;justify-content:center;width:100%;height:48px"><?= h($t) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    <?php elseif ($s === 1): ?>
      <div class="section-label with-sub">간병인 정보</div>
      <div class="section-sub">정보를 입력해주세요</div>
      <div class="field-group">
        <label class="field-label">이름</label>
        <input class="field-input" name="name" required maxlength="50" placeholder="성함을 입력해주세요" value="<?= attr($d['name']??'') ?>"/>
      </div>
      <div class="field-group">
        <label class="field-label">성별</label>
        <div style="display:flex;gap:8px">
          <?php foreach (['남성','여성'] as $g): ?>
          <label style="flex:1;position:relative">
            <input class="toggle-radio" type="radio" name="gender" value="<?= attr($g) ?>" required <?= ($d['gender']??'')===$g?'checked':'' ?>/>
            <span class="toggle-btn" style="display:flex;align-items:center;justify-content:center;width:100%"><?= h($g) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="field-group">
        <label class="field-label">연락처</label>
        <input class="field-input" name="phone" type="tel" inputmode="numeric" maxlength="11" required pattern="\d{10,11}" placeholder="01000000000" value="<?= attr($d['phone']??'') ?>"/>
      </div>
      <div class="field-group">
        <label class="field-label">생년월일</label>
        <input class="field-input" name="dob" type="tel" inputmode="numeric" maxlength="8" required pattern="\d{8}" placeholder="예) 19850615" value="<?= attr($d['dob']??'') ?>"/>
      </div>
      <div class="field-group">
        <label class="field-label">주소</label>
        <div class="field-row">
          <select class="field-input" name="city">
            <option value="">시/도 선택</option>
            <?php foreach (KOREAN_CITIES as $c): ?>
              <option value="<?= attr($c) ?>" <?= ($d['city']??'')===$c?'selected':'' ?>><?= h($c) ?></option>
            <?php endforeach; ?>
          </select>
          <input class="field-input" name="district" placeholder="시/군/구" value="<?= attr($d['district']??'') ?>"/>
        </div>
      </div>
    <?php elseif ($s === 2): ?>
      <div class="section-label with-sub">간병 대상자 정보 (3/3)</div>
      <div class="section-sub">간병 받을 분의 기본 정보를 입력해주세요</div>
      <div class="field-group">
        <label class="field-label">대상자 이름</label>
        <input class="field-input" name="patientName" maxlength="50" placeholder="성함을 입력해주세요" value="<?= attr($d['patientName']??'') ?>"/>
      </div>
      <div class="field-group">
        <label class="field-label">성별</label>
        <div style="display:flex;gap:8px">
          <?php foreach (['남성','여성'] as $g): ?>
          <label style="flex:1;position:relative">
            <input class="toggle-radio" type="radio" name="patientGender" value="<?= attr($g) ?>" <?= ($d['patientGender']??'')===$g?'checked':'' ?>/>
            <span class="toggle-btn" style="display:flex;align-items:center;justify-content:center;width:100%"><?= h($g) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="field-group">
        <label class="field-label">생년월일</label>
        <input class="field-input" name="patientDob" type="tel" inputmode="numeric" maxlength="8" placeholder="예) 19400322" value="<?= attr($d['patientDob']??'') ?>"/>
      </div>
      <div class="field-group">
        <label class="field-label">연락처</label>
        <input class="field-input" name="patientPhone" type="tel" inputmode="numeric" maxlength="11" placeholder="01000000000" value="<?= attr($d['patientPhone']??'') ?>"/>
      </div>
      <div class="field-group">
        <label class="field-label">주소</label>
        <div class="field-row">
          <input class="field-input" name="patientCity" placeholder="시/도" value="<?= attr($d['patientCity']??'') ?>"/>
          <input class="field-input" name="patientDistrict" placeholder="시/군/구" value="<?= attr($d['patientDistrict']??'') ?>"/>
        </div>
      </div>
    <?php endif; ?>
      <div style="height:8px"></div>
    </div>
    <div class="cta-wrap"><button class="cta" type="submit">다음</button></div>
    </form>
    <?php
    render_postlude('reg');
}

function render_review(): void {
    global $FLOW;
    $d = flow_get($FLOW, 'data');
    $err = $_GET['err'] ?? '';
    render_prelude(['title'=>'입력 확인','show_back'=>true,'back_url'=>'reg.php?s=2','active'=>'reg']);
    $row = function($label, $value) {
        $empty = $value === '' || $value === null;
        echo '<div class="review-row"><span class="review-label">'.h($label).'</span>';
        echo '<span class="review-value'.($empty?' empty':'').'">'.($empty?'미입력':h((string)$value)).'</span></div>';
    };
    $cgAddr = trim(($d['city']??'').' '.($d['district']??''));
    $ptAddr = trim(($d['patientCity']??'').' '.($d['patientDistrict']??''));
    ?>
    <div class="content review-content">
      <div class="section-label" style="padding:24px 24px 4px;background:#F6F6F6">입력 내용 확인</div>
      <div class="section-sub" style="padding:4px 24px 16px;background:#F6F6F6">내용이 맞는지 확인하시고, 등록을 완료해주세요</div>
      <?php if ($err === 'terms'): ?><div style="margin:0 18px 10px;padding:12px 16px;background:#FFF0F0;border:1px solid #FFD0D0;border-radius:10px;color:#B30000;font-size:13px">필수 약관에 모두 동의해주세요.</div><?php elseif ($err === 'db'): ?><div style="margin:0 18px 10px;padding:12px 16px;background:#FFF0F0;border:1px solid #FFD0D0;border-radius:10px;color:#B30000;font-size:13px">저장 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.</div><?php endif; ?>
      <div class="review-section">
        <div class="review-section-head"><div class="review-section-title">간병인 유형</div><a class="review-edit" href="reg.php?s=0&edit=1">수정</a></div>
        <?php $row('유형', $d['type']??''); ?>
      </div>
      <div class="review-section">
        <div class="review-section-head"><div class="review-section-title">간병인 정보</div><a class="review-edit" href="reg.php?s=1&edit=1">수정</a></div>
        <?php $row('이름',$d['name']??''); $row('성별',$d['gender']??''); $row('생년월일',$d['dob']??''); $row('연락처',$d['phone']??''); $row('주소',$cgAddr); ?>
      </div>
      <div class="review-section">
        <div class="review-section-head"><div class="review-section-title">간병 대상자 정보</div><a class="review-edit" href="reg.php?s=2&edit=1">수정</a></div>
        <?php $row('이름',$d['patientName']??''); $row('성별',$d['patientGender']??''); $row('생년월일',$d['patientDob']??''); $row('연락처',$d['patientPhone']??''); $row('주소',$ptAddr); ?>
      </div>
      <div style="height:12px"></div>
    </div>
    <div class="cta-wrap" style="background:#F6F6F6"><button class="cta" type="button" onclick="termsOpen()">등록 신청하기</button></div>
    <?php
    render_terms_modal(reg_terms(), 'reg.php?action=submit');
    render_postlude('reg');
}

function render_done(): void {
    render_prelude(['title'=>'등록 완료','active'=>'reg']);
    ?>
    <div class="content done-body" style="width:430px">
      <div class="done-circle" style="background:none"><img src="./ui/project/assets/Completion.png" alt="완료"/></div>
      <div class="done-title">신청완료!</div>
      <div class="done-desc">진행상황을 알림톡으로 알려드립니다.</div>
    </div>
    <div class="cta-wrap"><a class="cta" href="index.php" style="background:#111;text-align:center;display:block">홈으로</a></div>
    <?php
    render_postlude('reg');
}

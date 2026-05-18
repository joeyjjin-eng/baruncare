<?php
/**
 * 간병비 보험금 청구 — 다단계 폼.
 * GET ?s=intro|0|1|2|3|review|done
 */
declare(strict_types=1);
require __DIR__ . '/lib/util.php';
require __DIR__ . '/lib/layout.php';
require __DIR__ . '/lib/terms_modal.php';

$FLOW = 'claim';
$s = $_GET['s'] ?? 'intro';
$edit = !empty($_GET['edit']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_GET['action'] ?? '') === 'submit') {
        $agree = $_POST['agree'] ?? [];
        foreach (['tos','privacy','sensitive','thirdparty'] as $k) {
            if (empty($agree[$k])) redirect('claim.php?s=review&err=terms');
        }
        insert_claim();
        flow_clear($FLOW);
        redirect('claim.php?s=done');
    }
    save_step((int)$s, !empty($_POST['_edit']));
    $next = $edit || !empty($_POST['_edit']) ? 'review' : ((int)$s + 1);
    redirect('claim.php?s=' . $next);
}

if ($s === 'intro') render_intro();
elseif ($s === 'done') render_done();
elseif ($s === 'review') render_review();
elseif (in_array($s, ['0','1','2','3'], true)) render_step((int)$s, $edit);
else redirect('claim.php?s=intro');


function save_step(int $s, bool $edit): void {
    global $FLOW;
    if ($s === 0) {
        flow_set($FLOW, 'cg', [
            'type'     => in_enum($_POST['type'] ?? '', ['일반간병인','가족간병인']),
            'name'     => in_name($_POST['name'] ?? ''),
            'gender'   => in_enum($_POST['gender'] ?? '', ['남성','여성']),
            'dob'      => in_dob($_POST['dob'] ?? ''),
            'phone'    => in_phone($_POST['phone'] ?? ''),
            'city'     => in_str($_POST['city'] ?? '', 50),
            'district' => in_str($_POST['district'] ?? '', 50),
        ]);
    } elseif ($s === 1) {
        flow_set($FLOW, 'pt', [
            'patientName'     => in_name($_POST['patientName'] ?? ''),
            'patientGender'   => in_enum($_POST['patientGender'] ?? '', ['남성','여성']),
            'patientDob'      => in_dob($_POST['patientDob'] ?? ''),
            'patientPhone'    => in_phone($_POST['patientPhone'] ?? ''),
            'patientCity'     => in_str($_POST['patientCity'] ?? '', 50),
            'patientDistrict' => in_str($_POST['patientDistrict'] ?? '', 50),
        ]);
    } elseif ($s === 2) {
        flow_set_root_field($FLOW, 'ins', in_str($_POST['ins'] ?? '', 50));
    } elseif ($s === 3) {
        flow_set($FLOW, 'per', [
            'start'    => in_date($_POST['start'] ?? ''),
            'end'      => in_date($_POST['end'] ?? ''),
            'hospital' => in_str($_POST['hospital'] ?? '', 100),
            'room'     => in_str($_POST['room'] ?? '', 50),
        ]);
    }
}
function flow_set_root_field(string $flow, string $key, $value): void {
    $_SESSION['flow'][$flow][$key] = $value;
}

function insert_claim(): void {
    global $FLOW;
    $cg = flow_get($FLOW, 'cg'); $pt = flow_get($FLOW, 'pt'); $per = flow_get($FLOW, 'per');
    $ins = $_SESSION['flow'][$FLOW]['ins'] ?? '';
    try {
        $sql = "INSERT INTO claim_requests
          (cg_type, cg_name, cg_gender, cg_dob, cg_phone, cg_city, cg_district,
           pt_name, pt_gender, pt_dob, pt_phone, pt_city, pt_district,
           insurer, care_start, care_end, hospital_name, room_no,
           user_agent, ip_addr) VALUES
          (:cg_type,:cg_name,:cg_gender,:cg_dob,:cg_phone,:cg_city,:cg_district,
           :pt_name,:pt_gender,:pt_dob,:pt_phone,:pt_city,:pt_district,
           :insurer,:care_start,:care_end,:hospital_name,:room_no,
           :user_agent,:ip_addr)";
        $stmt = db()->prepare($sql);
        $stmt->execute([
            ':cg_type'=>$cg['type']??'', ':cg_name'=>$cg['name']??'',
            ':cg_gender'=>$cg['gender']??'', ':cg_dob'=>$cg['dob']??'',
            ':cg_phone'=>$cg['phone']??'', ':cg_city'=>$cg['city']??'',
            ':cg_district'=>$cg['district']??'',
            ':pt_name'=>$pt['patientName']??'', ':pt_gender'=>$pt['patientGender']??'',
            ':pt_dob'=>$pt['patientDob']??'', ':pt_phone'=>$pt['patientPhone']??'',
            ':pt_city'=>$pt['patientCity']??'', ':pt_district'=>$pt['patientDistrict']??'',
            ':insurer'=>$ins, ':care_start'=>$per['start']??null, ':care_end'=>$per['end']??null,
            ':hospital_name'=>$per['hospital']??'', ':room_no'=>$per['room']??'',
            ':user_agent'=>user_agent(), ':ip_addr'=>client_ip(),
        ]);
    } catch (PDOException $e) {
        error_log('[claim] '.$e->getMessage());
        redirect('claim.php?s=review&err=db');
    }
}


function render_intro(): void {
    render_prelude(['title'=>'간병비 보험금 청구','show_back'=>true,'back_url'=>'index.php','active'=>'claim']);
    ?>
    <div class="content ri-content">
      <div class="ri-illust-wrap"><img src="./ui/project/assets/intro_illust_03.png" alt="간병비 청구 도우미 일러스트"/></div>
      <h2 class="ri-title" style="margin-top:24px">간병비 청구 도우미</h2>
      <div class="ri-bullets">
        <p class="ri-bullet">복잡한 간병비 보험 청구!
헤매지 않도록 처음부터 끝까지 안내해 드립니다.
청구 진행 절차부터 내게 맞는 필수 서류 준비까지
꼼꼼하게 가이드해 드려요.</p>
      </div>
      <div class="ci-callout">
        <div class="ci-callout-title">어려운 보험금 청구, 이제 쉽게 준비하세요.</div>
        <ul class="ci-callout-list">
          <li>복잡한 청구 진행 과정 알기 쉽게 안내</li>
          <li>내 조건에 맞는 필수 증빙 서류 확인</li>
          <li>빠짐없이 준비할 수 있도록 꼼꼼한 가이드 지원</li>
        </ul>
      </div>
      <div style="margin:0 24px 16px">
        <a class="home-info-row" style="padding:18px 0" href="process.php">
          <div style="display:flex;align-items:center">
            <div class="home-info-icon-wrap" style="background:#FFF4D3"><img src="./ui/project/assets/icon-process.png" alt="" width="32" height="32"/></div>
            <span class="home-info-text">간병비 보험 청구 프로세스</span>
          </div>
        </a>
        <hr style="height:1px;border:none;border-top:1px dashed #CCC;margin:0"/>
        <a class="home-info-row" style="padding:18px 0" href="docs.php">
          <div style="display:flex;align-items:center">
            <div class="home-info-icon-wrap" style="background:#FAEAFE"><img src="./ui/project/assets/icon-docs.png" alt="" width="32" height="32"/></div>
            <span class="home-info-text">간병비 보험 청구 필수서류</span>
          </div>
        </a>
      </div>
      <div style="height:12px"></div>
    </div>
    <div class="cta-wrap"><a class="cta" href="claim.php?s=0" style="background:#E8875D;text-align:center;display:block">청구도우미 시작</a></div>
    <?php
    render_postlude('claim');
}

function render_step(int $s, bool $edit): void {
    global $FLOW;
    $back = $s === 0 ? 'claim.php?s=intro' : ('claim.php?s=' . ($s-1));
    if ($edit) $back = 'claim.php?s=review';
    render_prelude(['title'=>'간병비 보험금 청구','show_back'=>true,'back_url'=>$back,'active'=>'claim']);
    echo pr_seg(4, $s);
    $cg = flow_get($FLOW,'cg'); $pt = flow_get($FLOW,'pt'); $per = flow_get($FLOW,'per');
    $ins = $_SESSION['flow'][$FLOW]['ins'] ?? '';
    $editIn = $edit ? '<input type="hidden" name="_edit" value="1">' : '';
    ?>
    <form method="post" action="claim.php?s=<?= $s ?>" style="display:contents">
    <?= $editIn ?>
    <div class="content">
    <?php if ($s === 0): ?>
      <div class="section-label">간병인 유형</div>
      <div class="toggle-wrap">
        <?php foreach (['일반간병인','가족간병인'] as $t): ?>
        <label style="flex:1;position:relative">
          <input class="toggle-radio" type="radio" name="type" value="<?= attr($t) ?>" required <?= ($cg['type']??'')===$t?'checked':'' ?>/>
          <span class="toggle-btn" style="display:flex;align-items:center;justify-content:center;width:100%;height:48px"><?= h($t) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
      <div class="section-label with-sub" style="padding-top:8px">간병인 정보 (1/4)</div>
      <div class="section-sub">간병을 제공한 분의 정보를 입력해주세요</div>
      <div class="field-group">
        <label class="field-label">이름</label>
        <input class="field-input" name="name" required maxlength="50" placeholder="성함을 입력해주세요" value="<?= attr($cg['name']??'') ?>"/>
      </div>
      <div class="field-group">
        <label class="field-label">성별</label>
        <div style="display:flex;gap:8px">
          <?php foreach (['남성','여성'] as $g): ?>
          <label style="flex:1;position:relative">
            <input class="toggle-radio" type="radio" name="gender" value="<?= attr($g) ?>" required <?= ($cg['gender']??'')===$g?'checked':'' ?>/>
            <span class="toggle-btn" style="display:flex;align-items:center;justify-content:center;width:100%"><?= h($g) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="field-group"><label class="field-label">생년월일</label>
        <input class="field-input" name="dob" type="tel" inputmode="numeric" maxlength="8" required pattern="\d{8}" placeholder="예) 19750820" value="<?= attr($cg['dob']??'') ?>"/></div>
      <div class="field-group"><label class="field-label">연락처</label>
        <input class="field-input" name="phone" type="tel" inputmode="numeric" maxlength="11" required pattern="\d{10,11}" placeholder="01000000000" value="<?= attr($cg['phone']??'') ?>"/></div>
      <div class="field-group">
        <label class="field-label">주소</label>
        <div class="field-row">
          <input class="field-input" name="city" required placeholder="시/도" value="<?= attr($cg['city']??'') ?>"/>
          <input class="field-input" name="district" required placeholder="시/군/구" value="<?= attr($cg['district']??'') ?>"/>
        </div>
      </div>
    <?php elseif ($s === 1): ?>
      <div class="section-label with-sub">간병 대상자 정보 (2/4)</div>
      <div class="section-sub">보험금을 청구할 피보험자 정보를 입력해주세요</div>
      <div class="field-group"><label class="field-label">이름</label>
        <input class="field-input" name="patientName" required maxlength="50" placeholder="성함을 입력해주세요" value="<?= attr($pt['patientName']??'') ?>"/></div>
      <div class="field-group">
        <label class="field-label">성별</label>
        <div style="display:flex;gap:8px">
          <?php foreach (['남성','여성'] as $g): ?>
          <label style="flex:1;position:relative">
            <input class="toggle-radio" type="radio" name="patientGender" value="<?= attr($g) ?>" required <?= ($pt['patientGender']??'')===$g?'checked':'' ?>/>
            <span class="toggle-btn" style="display:flex;align-items:center;justify-content:center;width:100%"><?= h($g) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="field-group"><label class="field-label">생년월일</label>
        <input class="field-input" name="patientDob" type="tel" inputmode="numeric" maxlength="8" required pattern="\d{8}" placeholder="예) 19500110" value="<?= attr($pt['patientDob']??'') ?>"/></div>
      <div class="field-group"><label class="field-label">연락처</label>
        <input class="field-input" name="patientPhone" type="tel" inputmode="numeric" maxlength="11" required pattern="\d{10,11}" placeholder="01000000000" value="<?= attr($pt['patientPhone']??'') ?>"/></div>
      <div class="field-group">
        <label class="field-label">주소</label>
        <div class="field-row">
          <input class="field-input" name="patientCity" required placeholder="시/도" value="<?= attr($pt['patientCity']??'') ?>"/>
          <input class="field-input" name="patientDistrict" required placeholder="시/군/구" value="<?= attr($pt['patientDistrict']??'') ?>"/>
        </div>
      </div>
    <?php elseif ($s === 2): ?>
      <div class="section-label with-sub">보험사 선택 (3/4)</div>
      <div class="section-sub">가입하신 보험사를 선택해주세요</div>
      <div class="ins-grid">
        <?php foreach (INSURERS as $name): ?>
        <label style="position:relative">
          <input class="ins-radio" type="radio" name="ins" value="<?= attr($name) ?>" required <?= $ins===$name?'checked':'' ?>/>
          <span class="ins-item"><?= h($name) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    <?php elseif ($s === 3): ?>
      <div class="section-label with-sub">간병 정보 (4/4)</div>
      <div class="section-sub">간병 기간과 병원 정보를 입력해주세요</div>
      <div class="field-group"><label class="field-label">간병 시작일</label>
        <input class="field-input" name="start" type="date" required value="<?= attr($per['start']??'') ?>"/></div>
      <div class="field-group"><label class="field-label">간병 종료일</label>
        <input class="field-input" name="end" type="date" required value="<?= attr($per['end']??'') ?>"/></div>
      <div class="field-group"><label class="field-label">병원명</label>
        <input class="field-input" name="hospital" required maxlength="100" placeholder="예) 서울아산병원" value="<?= attr($per['hospital']??'') ?>"/></div>
      <div class="field-group"><label class="field-label">병동 / 호실</label>
        <input class="field-input" name="room" required maxlength="50" placeholder="예) 내과 3병동 302호" value="<?= attr($per['room']??'') ?>"/></div>
    <?php endif; ?>
      <div style="height:8px"></div>
    </div>
    <div class="cta-wrap"><button class="cta" type="submit"><?= $s===3 ? '신청하기' : '다음' ?></button></div>
    </form>
    <?php
    render_postlude('claim');
}

function render_review(): void {
    global $FLOW;
    $cg = flow_get($FLOW,'cg'); $pt = flow_get($FLOW,'pt'); $per = flow_get($FLOW,'per');
    $ins = $_SESSION['flow'][$FLOW]['ins'] ?? '';
    $err = $_GET['err'] ?? '';
    render_prelude(['title'=>'입력 확인','show_back'=>true,'back_url'=>'claim.php?s=3','active'=>'claim']);
    $row = function($label, $value) {
        $empty = $value === '' || $value === null;
        echo '<div class="review-row"><span class="review-label">'.h($label).'</span>';
        echo '<span class="review-value'.($empty?' empty':'').'">'.($empty?'미입력':h((string)$value)).'</span></div>';
    };
    $cgAddr = trim(($cg['city']??'').' '.($cg['district']??''));
    $ptAddr = trim(($pt['patientCity']??'').' '.($pt['patientDistrict']??''));
    $period = ($per['start']??'')&&($per['end']??'') ? "{$per['start']} ~ {$per['end']}" : (($per['start']??'') ?: ($per['end']??''));
    ?>
    <div class="content review-content">
      <div class="section-label" style="padding:24px 24px 4px;background:#F6F6F6">입력 내용 확인</div>
      <div class="section-sub" style="padding:4px 24px 16px;background:#F6F6F6">내용이 맞는지 확인하시고, 신청을 완료해주세요</div>
      <?php if ($err === 'terms'): ?><div style="margin:0 18px 10px;padding:12px 16px;background:#FFF0F0;border:1px solid #FFD0D0;border-radius:10px;color:#B30000;font-size:13px">필수 약관에 모두 동의해주세요.</div><?php elseif ($err === 'db'): ?><div style="margin:0 18px 10px;padding:12px 16px;background:#FFF0F0;border:1px solid #FFD0D0;border-radius:10px;color:#B30000;font-size:13px">저장 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.</div><?php endif; ?>
      <div class="review-section">
        <div class="review-section-head"><div class="review-section-title">간병인 정보</div><a class="review-edit" href="claim.php?s=0&edit=1">수정</a></div>
        <?php $row('유형',$cg['type']??''); $row('이름',$cg['name']??''); $row('성별',$cg['gender']??''); $row('생년월일',$cg['dob']??''); $row('연락처',$cg['phone']??''); $row('주소',$cgAddr); ?>
      </div>
      <div class="review-section">
        <div class="review-section-head"><div class="review-section-title">간병 대상자 정보</div><a class="review-edit" href="claim.php?s=1&edit=1">수정</a></div>
        <?php $row('이름',$pt['patientName']??''); $row('성별',$pt['patientGender']??''); $row('생년월일',$pt['patientDob']??''); $row('연락처',$pt['patientPhone']??''); $row('주소',$ptAddr); ?>
      </div>
      <div class="review-section">
        <div class="review-section-head"><div class="review-section-title">보험사</div><a class="review-edit" href="claim.php?s=2&edit=1">수정</a></div>
        <?php $row('보험사', $ins); ?>
      </div>
      <div class="review-section">
        <div class="review-section-head"><div class="review-section-title">간병 정보</div><a class="review-edit" href="claim.php?s=3&edit=1">수정</a></div>
        <?php $row('기간',$period); $row('병원명',$per['hospital']??''); $row('병동/호실',$per['room']??''); ?>
      </div>
      <div style="height:12px"></div>
    </div>
    <div class="cta-wrap" style="background:#F6F6F6"><button class="cta" type="button" onclick="termsOpen()">신청 완료</button></div>
    <?php
    render_terms_modal(claim_terms(), 'claim.php?action=submit');
    render_postlude('claim');
}

function render_done(): void {
    render_prelude(['title'=>'신청 완료','active'=>'claim']);
    ?>
    <div class="content done-body" style="width:430px">
      <div class="done-circle" style="background:none"><img src="./ui/project/assets/Completion.png" alt="완료"/></div>
      <div class="done-title">청구도우미 신청완료!</div>
      <div class="done-desc">청구도우미가 배정되면 카톡을 드립니다.
첨부서류는 배정된 후 카카오톡을 통해 제출해 주시면 됩니다.</div>
    </div>
    <div class="cta-wrap"><a class="cta" href="index.php" style="background:#111;text-align:center;display:block">홈으로</a></div>
    <?php
    render_postlude('claim');
}

<?php
/**
 * 간병인 찾기 — 다단계 폼.
 *  GET  ?s=intro|0|1|2|3|4|5|review|done   화면 표시
 *  POST ?s=0..5                              단계 데이터 저장 → 다음 단계
 *  POST ?action=submit                       약관 동의 + DB INSERT → done
 */
declare(strict_types=1);
require __DIR__ . '/lib/util.php';
require __DIR__ . '/lib/layout.php';
require __DIR__ . '/lib/terms_modal.php';

$FLOW = 'find';
$s = $_GET['s'] ?? 'intro';
$edit = !empty($_GET['edit']);

/* ─────────── POST 처리 ─────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_GET['action'] ?? '') === 'submit') {
        // 약관 동의 + 최종 저장
        $agree = $_POST['agree'] ?? [];
        foreach (['tos','privacy','sensitive','thirdparty'] as $k) {
            if (empty($agree[$k])) { redirect('find.php?s=review&err=terms'); }
        }
        insert_find_request();
        flow_clear($FLOW);
        redirect('find.php?s=done');
    }
    save_step((int)$s, !empty($_POST['_edit']));
    $next = $edit || !empty($_POST['_edit']) ? 'review' : ((int)$s + 1);
    redirect('find.php?s=' . $next);
}

/* ─────────── 화면 분기 ─────────── */
if ($s === 'intro') render_intro();
elseif ($s === 'done') render_done();
elseif ($s === 'review') render_review();
elseif (in_array($s, ['0','1','2','3','4','5'], true)) render_step((int)$s, $edit);
else redirect('find.php?s=intro');


/* ═══════════ 저장 핸들러 ═══════════ */
function save_step(int $s, bool $edit): void {
    global $FLOW;
    if ($s === 0) {
        flow_set($FLOW, 'req', [
            'reqName'  => in_name($_POST['reqName']  ?? ''),
            'reqPhone' => in_phone($_POST['reqPhone'] ?? ''),
        ]);
    } elseif ($s === 1) {
        flow_set($FLOW, 'pt', [
            'name'   => in_name($_POST['name']   ?? ''),
            'gender' => in_enum($_POST['gender'] ?? '', ['남성','여성']),
            'dob'    => in_dob($_POST['dob']     ?? ''),
            'phone'  => in_phone($_POST['phone']  ?? ''),
        ]);
    } elseif ($s === 2) {
        flow_set($FLOW, 'place', [
            'place'    => in_enum($_POST['place']    ?? '', ['집','병원']),
            'hospital' => in_str($_POST['hospital']  ?? '', 100),
            'city'     => in_str($_POST['city']      ?? '', 50),
            'district' => in_str($_POST['district']  ?? '', 50),
        ]);
    } elseif ($s === 3) {
        // 시간은 HH/MM 분리 select 로 받음
        $sh = preg_replace('/\D/', '', (string)($_POST['startH'] ?? ''));
        $sm = preg_replace('/\D/', '', (string)($_POST['startM'] ?? ''));
        $eh = preg_replace('/\D/', '', (string)($_POST['endH']   ?? ''));
        $em = preg_replace('/\D/', '', (string)($_POST['endM']   ?? ''));
        $startT = ($sh !== '' && $sm !== '') ? str_pad($sh,2,'0',STR_PAD_LEFT).':'.str_pad($sm,2,'0',STR_PAD_LEFT) : '';
        $endT   = ($eh !== '' && $em !== '') ? str_pad($eh,2,'0',STR_PAD_LEFT).':'.str_pad($em,2,'0',STR_PAD_LEFT) : '';
        flow_set($FLOW, 'when', [
            'start'     => in_date($_POST['start'] ?? ''),
            'end'       => in_date($_POST['end']   ?? ''),
            'startTime' => $startT,
            'endTime'   => $endT,
            'allDay'    => !empty($_POST['allDay']),
            'note'      => in_str($_POST['note']   ?? '', 2000),
        ]);
    } elseif ($s === 4) {
        $cur = flow_get($FLOW, 'state');
        $cur['height']         = in_digits($_POST['height'] ?? '', 3);
        $cur['weight']         = in_digits($_POST['weight'] ?? '', 3);
        $cur['mobility']       = in_enum($_POST['mobility'] ?? '', MOBILITY);
        $cur['special']        = in_arr($_POST['special']   ?? []);
        $cur['cognitive']      = in_arr($_POST['cognitive'] ?? []);
        $cur['cognitiveOther'] = in_str($_POST['cognitiveOther'] ?? '', 255);
        flow_set($FLOW, 'state', $cur);
    } elseif ($s === 5) {
        $cur = flow_get($FLOW, 'state');
        $cur['extra'] = in_arr($_POST['extra'] ?? []);
        $cur['note']  = in_str($_POST['note']  ?? '', 2000);
        flow_set($FLOW, 'state', $cur);
    }
}

function insert_find_request(): void {
    global $FLOW;
    $req = flow_get($FLOW,'req'); $pt = flow_get($FLOW,'pt');
    $pl = flow_get($FLOW,'place'); $wh = flow_get($FLOW,'when');
    $st = flow_get($FLOW,'state');
    try {
        $sql = "INSERT INTO find_requests
          (req_name, req_phone, pt_name, pt_gender, pt_dob, pt_phone,
           place_type, hospital_name, city, district,
           care_start, care_end, start_time, end_time, all_day, schedule_note,
           height_cm, weight_kg, mobility, special_care, cognitive, cognitive_other,
           extra_requests, extra_note, user_agent, ip_addr)
          VALUES
          (:req_name, :req_phone, :pt_name, :pt_gender, :pt_dob, :pt_phone,
           :place_type, :hospital_name, :city, :district,
           :care_start, :care_end, :start_time, :end_time, :all_day, :schedule_note,
           :height_cm, :weight_kg, :mobility, :special_care, :cognitive, :cognitive_other,
           :extra_requests, :extra_note, :user_agent, :ip_addr)";
        $stmt = db()->prepare($sql);
        $stmt->execute([
            ':req_name'=>$req['reqName']??'', ':req_phone'=>$req['reqPhone']??'',
            ':pt_name'=>$pt['name']??'', ':pt_gender'=>$pt['gender']??'',
            ':pt_dob'=>$pt['dob']??'', ':pt_phone'=>$pt['phone']??'',
            ':place_type'=>$pl['place']??'', ':hospital_name'=>($pl['place']??'')==='병원'?($pl['hospital']??null):null,
            ':city'=>$pl['city']??'', ':district'=>$pl['district']??'',
            ':care_start'=>$wh['start']??null, ':care_end'=>$wh['end']??null,
            ':start_time'=>!empty($wh['allDay'])?null:($wh['startTime']??null),
            ':end_time'=>!empty($wh['allDay'])?null:($wh['endTime']??null),
            ':all_day'=>!empty($wh['allDay'])?1:0, ':schedule_note'=>$wh['note']??null,
            ':height_cm'=>($st['height']??'')!==''?(int)$st['height']:null,
            ':weight_kg'=>($st['weight']??'')!==''?(int)$st['weight']:null,
            ':mobility'=>$st['mobility']??'',
            ':special_care'=>json_encode($st['special']??[], JSON_UNESCAPED_UNICODE),
            ':cognitive'=>json_encode($st['cognitive']??[], JSON_UNESCAPED_UNICODE),
            ':cognitive_other'=>$st['cognitiveOther']??null,
            ':extra_requests'=>json_encode($st['extra']??[], JSON_UNESCAPED_UNICODE),
            ':extra_note'=>$st['note']??null,
            ':user_agent'=>user_agent(), ':ip_addr'=>client_ip(),
        ]);
    } catch (PDOException $e) {
        error_log('[find] '.$e->getMessage());
        // 실패 시 review 로 돌려보내며 에러 표시
        redirect('find.php?s=review&err=db');
    }
}

/* ═══════════ 렌더링 ═══════════ */
function render_intro(): void {
    $steps = [
        ['신청인정보', '연락받을 분의 기본 정보 입력', '#E6EFFF', 'person'],
        ['환자정보', '환자분의 기본 정보 및 신체 정보', '#E2F4E8', 'check'],
        ['간병환경', '간병 장소 및 일정 선택', '#F2EAFC', 'pin'],
        ['환자상태', '환자분의 현재 상태 체크리스트', '#FFF0E0', 'pulse'],
        ['추가요청사항', '특별히 요청하실 사항 입력', '#FFE6EE', 'doc'],
    ];
    $icons = [
        'person' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="#5B8FE8" stroke-width="2"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6" stroke="#5B8FE8" stroke-width="2" stroke-linecap="round"/></svg>',
        'check'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="10" cy="8" r="4" stroke="#5CB87A" stroke-width="2"/><path d="M3 20c0-4 3.5-6 7-6 1 0 1.9.15 2.7.4" stroke="#5CB87A" stroke-width="2" stroke-linecap="round"/><path d="M15 17.5l2 2 4-4" stroke="#5CB87A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'pin'    => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 22s7-7.5 7-13a7 7 0 1 0-14 0c0 5.5 7 13 7 13z" stroke="#A57AD9" stroke-width="2" stroke-linejoin="round"/><circle cx="12" cy="9" r="2.5" stroke="#A57AD9" stroke-width="2"/></svg>',
        'pulse'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 12h4l2-5 4 10 2-5h6" stroke="#F2A05D" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'doc'    => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" stroke="#E85D8E" stroke-width="2" stroke-linejoin="round"/><path d="M14 3v6h6M8 13h8M8 17h6" stroke="#E85D8E" stroke-width="2" stroke-linecap="round"/></svg>',
    ];
    render_prelude(['title'=>'간병인 찾기', 'show_back'=>true, 'back_url'=>'index.php', 'active'=>'find']);
    ?>
    <div class="content" style="background:#fff">
      <div class="fi-hero">
        <div class="fi-illust-wrap"><img src="./ui/project/assets/intro_illust_01.png" alt="간병인 일러스트"/></div>
        <h2 class="fi-title">믿을 수 있는 간병인,<br/>바른케어플러스가 함께 찾겠습니다.</h2>
      </div>
      <div class="fi-section-title" style="padding-top:24px">간병인 신청 프로세스</div>
      <div class="fi-steps">
        <?php foreach ($steps as $i => [$t, $sub, $bg, $ic]): ?>
        <div class="fi-step">
          <div class="fi-step-icon" style="background:<?= h($bg) ?>"><?= $icons[$ic] ?></div>
          <div class="fi-step-text"><strong><?= ($i+1) ?>. <?= h($t) ?></strong><span><?= h($sub) ?></span></div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="fi-info">💡 간단한 정보만 입력하시면 맞춤 간병인을 매칭해드립니다.<br/>5분이면 신청이 완료됩니다.</div>
    </div>
    <div class="cta-wrap"><a class="cta" href="find.php?s=0" style="text-align:center;display:block">간병인 신청하기</a></div>
    <?php
    render_postlude('find');
}

function render_step(int $s, bool $edit): void {
    global $FLOW;
    $back = $s === 0 ? 'find.php?s=intro' : ('find.php?s=' . ($s-1));
    if ($edit) $back = 'find.php?s=review';
    render_prelude(['title'=>'간병인 찾기', 'show_back'=>true, 'back_url'=>$back, 'active'=>'find']);
    echo pr_seg(5, min($s, 4));

    $req = flow_get($FLOW,'req'); $pt = flow_get($FLOW,'pt');
    $pl = flow_get($FLOW,'place'); $wh = flow_get($FLOW,'when');
    $st = flow_get($FLOW,'state');
    $editIn = $edit ? '<input type="hidden" name="_edit" value="1">' : '';
    ?>
    <form method="post" action="find.php?s=<?= $s ?>" style="display:contents">
    <?php echo $editIn; ?>
    <div class="content">
    <?php
    if ($s === 0): // 신청인 정보
    ?>
      <div class="section-label with-sub">신청인 정보 (1/5)</div>
      <div class="section-sub">연락 받으실 분의 정보를 입력해주세요</div>
      <div class="field-group">
        <label class="field-label">신청인 이름</label>
        <input class="field-input" name="reqName" required maxlength="50" placeholder="성함을 입력해주세요" value="<?= attr($req['reqName']??'') ?>"/>
      </div>
      <div class="field-group">
        <label class="field-label">신청인 연락처</label>
        <input class="field-input" name="reqPhone" type="tel" inputmode="numeric" maxlength="11" required pattern="\d{10,11}" placeholder="01000000000" value="<?= attr($req['reqPhone']??'') ?>"/>
      </div>
      <div style="margin:0 24px 20px;padding:14px;background:#F0FFFE;border-radius:10px;font-size:13px;color:#0a7a74;line-height:1.65">💬 신청인 연락처로 매칭 결과를 안내해드립니다</div>
    <?php elseif ($s === 1): // 환자
    ?>
      <div class="section-label with-sub">누구에게 필요한가요? (2/5)</div>
      <div class="section-sub">간병 받으실 환자 정보를 입력해주세요</div>
      <div class="field-group">
        <label class="field-label">환자 이름</label>
        <input class="field-input" name="name" required maxlength="50" placeholder="성함을 입력해주세요" value="<?= attr($pt['name']??'') ?>"/>
      </div>
      <div class="field-group">
        <label class="field-label">성별</label>
        <div style="display:flex;gap:8px;position:relative">
          <?php foreach (['남성','여성'] as $g): ?>
          <label style="flex:1;position:relative">
            <input class="toggle-radio" type="radio" name="gender" value="<?= attr($g) ?>" required <?= ($pt['gender']??'')===$g?'checked':'' ?>/>
            <span class="toggle-btn" style="display:flex;align-items:center;justify-content:center;width:100%"><?= h($g) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="field-group">
        <label class="field-label">생년월일</label>
        <input class="field-input" name="dob" type="tel" inputmode="numeric" maxlength="8" required pattern="\d{8}" placeholder="예) 19450312" value="<?= attr($pt['dob']??'') ?>"/>
      </div>
      <div class="field-group">
        <label class="field-label">연락처</label>
        <input class="field-input" name="phone" type="tel" inputmode="numeric" maxlength="11" required pattern="\d{10,11}" placeholder="01000000000" value="<?= attr($pt['phone']??'') ?>"/>
      </div>
    <?php elseif ($s === 2): // 장소
    ?>
      <div class="section-label with-sub">어디서 필요한가요? (3/5)</div>
      <div class="section-sub">간병이 필요한 장소를 선택해주세요</div>
      <div class="field-group">
        <label class="field-label">간병 장소</label>
        <div style="display:flex;gap:8px">
          <?php foreach (['집','병원'] as $p): ?>
          <label style="flex:1;position:relative">
            <input class="toggle-radio" type="radio" name="place" value="<?= attr($p) ?>" required <?= ($pl['place']??'')===$p?'checked':'' ?> onchange="document.getElementById('hosp-wrap').style.display=this.value==='병원'?'block':'none'"/>
            <span class="toggle-btn" style="display:flex;align-items:center;justify-content:center;width:100%"><?= h($p) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div id="hosp-wrap" class="field-group" style="display:<?= ($pl['place']??'')==='병원'?'block':'none' ?>">
        <label class="field-label">병원명</label>
        <input class="field-input" name="hospital" maxlength="100" placeholder="예) 서울대학교병원" value="<?= attr($pl['hospital']??'') ?>"/>
      </div>
      <div class="field-group">
        <label class="field-label">주소</label>
        <div class="field-row">
          <input class="field-input" name="city" required placeholder="시/도" value="<?= attr($pl['city']??'') ?>"/>
          <input class="field-input" name="district" required placeholder="시/군/구" value="<?= attr($pl['district']??'') ?>"/>
        </div>
      </div>
    <?php elseif ($s === 3): // 일정
    ?>
      <div class="section-label with-sub">언제 필요한가요? (4/5)</div>
      <div class="section-sub">간병이 필요한 기간과 시간을 알려주세요</div>
      <div class="field-group">
        <label class="field-label">시작일</label>
        <input class="field-input" name="start" type="date" required value="<?= attr($wh['start']??'') ?>"/>
      </div>
      <div class="field-group">
        <label class="field-label">종료일 (예정)</label>
        <input class="field-input" name="end" type="date" required value="<?= attr($wh['end']??'') ?>"/>
      </div>
      <div class="field-group">
        <label class="field-label">간병 시간</label>
        <div style="display:flex;flex-direction:column;gap:10px">
          <?php
          $hours = range(0,23); $mins = ['00','10','20','30','40','50'];
          $startHM = explode(':', $wh['startTime']??'');
          $endHM   = explode(':', $wh['endTime']??'');
          ?>
          <div style="display:flex;align-items:center;gap:10px">
            <span style="min-width:36px;font-size:13px;color:#666">시작</span>
            <select class="field-input" name="startH" style="flex:1">
              <option value="">시</option>
              <?php foreach($hours as $hh){ $hs=str_pad((string)$hh,2,'0',STR_PAD_LEFT); ?>
                <option value="<?= $hs ?>" <?= ($startHM[0]??'')===$hs?'selected':'' ?>><?= $hs ?>시</option>
              <?php } ?>
            </select>
            <select class="field-input" name="startM" style="flex:1">
              <option value="">분</option>
              <?php foreach($mins as $mm){ ?>
                <option value="<?= $mm ?>" <?= ($startHM[1]??'')===$mm?'selected':'' ?>><?= $mm ?>분</option>
              <?php } ?>
            </select>
          </div>
          <div style="display:flex;align-items:center;gap:10px">
            <span style="min-width:36px;font-size:13px;color:#666">종료</span>
            <select class="field-input" name="endH" style="flex:1">
              <option value="">시</option>
              <?php foreach($hours as $hh){ $hs=str_pad((string)$hh,2,'0',STR_PAD_LEFT); ?>
                <option value="<?= $hs ?>" <?= ($endHM[0]??'')===$hs?'selected':'' ?>><?= $hs ?>시</option>
              <?php } ?>
            </select>
            <select class="field-input" name="endM" style="flex:1">
              <option value="">분</option>
              <?php foreach($mins as $mm){ ?>
                <option value="<?= $mm ?>" <?= ($endHM[1]??'')===$mm?'selected':'' ?>><?= $mm ?>분</option>
              <?php } ?>
            </select>
          </div>
        </div>
      </div>
      <div class="field-group">
        <label class="field-label" style="display:flex;align-items:center;gap:8px">
          <input type="checkbox" name="allDay" value="1" <?= !empty($wh['allDay'])?'checked':'' ?> style="width:18px;height:18px;accent-color:#11B3A8"/>
          종일 간병
        </label>
      </div>
      <div class="field-group">
        <label class="field-label">기타 요청사항</label>
        <textarea class="field-input" name="note" rows="3" placeholder="예) 월·수·금만 필요해요"><?= h($wh['note']??'') ?></textarea>
      </div>
    <?php elseif ($s === 4): // 환자 상태
        $special = $st['special'] ?? []; $cogn = $st['cognitive'] ?? [];
    ?>
      <div class="section-label with-sub">환자분의 현재 상태는 어떤가요? (5/6)</div>
      <div class="section-sub">(상태 체크리스트)</div>
      <div class="field-group">
        <label class="field-label" style="color:#111;margin-bottom:8px">환자상태</label>
        <div class="field-row">
          <input class="field-input" name="height" type="tel" inputmode="numeric" maxlength="3" placeholder="키(cm)" value="<?= attr($st['height']??'') ?>"/>
          <input class="field-input" name="weight" type="tel" inputmode="numeric" maxlength="3" placeholder="몸무게(kg)" value="<?= attr($st['weight']??'') ?>"/>
        </div>
      </div>
      <div class="field-group">
        <label class="field-label" style="margin-bottom:6px">1개 선택 필수</label>
        <?php foreach (MOBILITY as $m): ?>
        <label class="wf-check">
          <input type="radio" name="mobility" value="<?= attr($m) ?>" required <?= ($st['mobility']??'')===$m?'checked':'' ?>/>
          <span class="box"></span><span class="lbl"><?= h($m) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
      <div style="margin:0 24px 16px;border-top:1px solid #D5D5D5"></div>
      <div class="field-group">
        <label class="field-label" style="margin-bottom:6px">다중선택 가능</label>
        <div style="display:grid;grid-template-columns:1fr 1fr">
          <?php for ($i=0; $i<count(SPECIAL_LEFT); $i++): foreach ([SPECIAL_LEFT[$i], SPECIAL_RIGHT[$i]] as $opt): if ($opt === ''): ?>
            <div></div>
          <?php else: ?>
            <label class="wf-check">
              <input type="checkbox" name="special[]" value="<?= attr($opt) ?>" <?= in_array($opt, $special, true)?'checked':'' ?>/>
              <span class="box"></span><span class="lbl"><?= h($opt) ?></span>
            </label>
          <?php endif; endforeach; endfor; ?>
        </div>
      </div>
      <div class="field-group" style="padding-top:4px">
        <?php foreach (COGNITIVE_OPTS as $c): ?>
        <label class="wf-check">
          <input type="checkbox" name="cognitive[]" value="<?= attr($c) ?>" <?= in_array($c, $cogn, true)?'checked':'' ?> onchange="if(this.value==='기타(입력)') document.getElementById('cogn-other').style.display=this.checked?'block':'none'"/>
          <span class="box"></span><span class="lbl"><?= h($c) ?></span>
        </label>
        <?php endforeach; ?>
        <input id="cogn-other" class="field-input" name="cognitiveOther" maxlength="255"
               placeholder="기타 인지 상태를 입력해주세요"
               style="margin-top:8px;display:<?= in_array('기타(입력)', $cogn, true)?'block':'none' ?>"
               value="<?= attr($st['cognitiveOther']??'') ?>"/>
      </div>
    <?php elseif ($s === 5): // 추가 요청
        $extra = $st['extra'] ?? [];
    ?>
      <div class="section-label">추가로 요청하실 사항이 있나요? (6/6)</div>
      <div class="field-group">
        <label class="field-label" style="color:#111;margin-bottom:8px">간병 요청 사항</label>
        <?php foreach (EXTRA_REQ_OPTS as $opt): ?>
        <label class="wf-check">
          <input type="checkbox" name="extra[]" value="<?= attr($opt) ?>" <?= in_array($opt, $extra, true)?'checked':'' ?>/>
          <span class="box"></span><span class="lbl"><?= h($opt) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
      <div class="field-group">
        <textarea class="field-input" name="note" rows="4" placeholder="작성" style="background:#f9f9f9"><?= h($st['note']??'') ?></textarea>
      </div>
    <?php endif; ?>
      <div style="height:8px"></div>
    </div>
    <div class="cta-wrap"><button class="cta" type="submit"><?= $s===5 ? '신청하기' : '다음' ?></button></div>
    </form>
    <?php
    render_postlude('find');
}

function render_review(): void {
    global $FLOW;
    $req = flow_get($FLOW,'req'); $pt = flow_get($FLOW,'pt');
    $pl = flow_get($FLOW,'place'); $wh = flow_get($FLOW,'when');
    $st = flow_get($FLOW,'state');
    $err = $_GET['err'] ?? '';
    render_prelude(['title'=>'입력 확인', 'show_back'=>true, 'back_url'=>'find.php?s=5', 'active'=>'find']);

    $row = function($label, $value) {
        $empty = $value === '' || $value === null;
        echo '<div class="review-row"><span class="review-label">'.h($label).'</span>';
        echo '<span class="review-value'.($empty?' empty':'').'">'.($empty?'미입력':h((string)$value)).'</span></div>';
    };
    $tagRow = function($label, $arr) {
        echo '<div class="review-row"><span class="review-label">'.h($label).'</span>';
        if (empty($arr)) echo '<span class="review-value empty">미입력</span>';
        else { echo '<div class="review-tag-wrap">';
            foreach($arr as $t) echo '<span class="review-tag">'.h($t).'</span>';
            echo '</div>'; }
        echo '</div>';
    };
    $dateRange = ($wh['start']??'')&&($wh['end']??'') ? "{$wh['start']} ~ {$wh['end']}" : (($wh['start']??'') ?: ($wh['end']??''));
    $timeRange = !empty($wh['allDay']) ? '종일 간병' : ((($wh['startTime']??'')&&($wh['endTime']??'')) ? "{$wh['startTime']} ~ {$wh['endTime']}" : '');
    ?>
    <div class="content review-content">
      <div class="section-label" style="padding:24px 24px 4px;background:#F6F6F6">입력 내용 확인</div>
      <div class="section-sub" style="padding:4px 24px 16px;background:#F6F6F6">내용이 맞는지 확인하시고, 신청을 완료해주세요</div>
      <?php if ($err === 'terms'): ?>
        <div style="margin:0 18px 10px;padding:12px 16px;background:#FFF0F0;border:1px solid #FFD0D0;border-radius:10px;color:#B30000;font-size:13px">필수 약관에 모두 동의해주세요.</div>
      <?php elseif ($err === 'db'): ?>
        <div style="margin:0 18px 10px;padding:12px 16px;background:#FFF0F0;border:1px solid #FFD0D0;border-radius:10px;color:#B30000;font-size:13px">저장 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.</div>
      <?php endif; ?>

      <div class="review-section">
        <div class="review-section-head">
          <div class="review-section-title">신청인 정보</div>
          <a class="review-edit" href="find.php?s=0&edit=1">수정</a>
        </div>
        <?php $row('이름', $req['reqName']??''); $row('연락처', $req['reqPhone']??''); ?>
      </div>
      <div class="review-section">
        <div class="review-section-head">
          <div class="review-section-title">환자 정보</div>
          <a class="review-edit" href="find.php?s=1&edit=1">수정</a>
        </div>
        <?php $row('이름', $pt['name']??''); $row('성별', $pt['gender']??''); $row('생년월일', $pt['dob']??''); $row('연락처', $pt['phone']??''); ?>
      </div>
      <div class="review-section">
        <div class="review-section-head">
          <div class="review-section-title">간병 장소</div>
          <a class="review-edit" href="find.php?s=2&edit=1">수정</a>
        </div>
        <?php $row('장소', $pl['place']??''); if(($pl['place']??'')==='병원') $row('병원명',$pl['hospital']??''); $row('주소', trim(($pl['city']??'').' '.($pl['district']??''))); ?>
      </div>
      <div class="review-section">
        <div class="review-section-head">
          <div class="review-section-title">간병 일정</div>
          <a class="review-edit" href="find.php?s=3&edit=1">수정</a>
        </div>
        <?php $row('기간',$dateRange); $row('시간',$timeRange); $row('기타사항',$wh['note']??''); ?>
      </div>
      <div class="review-section">
        <div class="review-section-head">
          <div class="review-section-title">환자 상태</div>
          <a class="review-edit" href="find.php?s=4&edit=1">수정</a>
        </div>
        <?php
        $row('키', ($st['height']??'')!==''?$st['height'].'cm':'');
        $row('몸무게', ($st['weight']??'')!==''?$st['weight'].'kg':'');
        $row('거동상태', $st['mobility']??'');
        $tagRow('특수간호', $st['special']??[]);
        $tagRow('인지상태', $st['cognitive']??[]);
        if (in_array('기타(입력)', $st['cognitive']??[], true) && !empty($st['cognitiveOther'])) $row('기타', $st['cognitiveOther']);
        ?>
      </div>
      <div class="review-section">
        <div class="review-section-head">
          <div class="review-section-title">추가 요청사항</div>
          <a class="review-edit" href="find.php?s=5&edit=1">수정</a>
        </div>
        <?php $tagRow('요청사항', $st['extra']??[]); $row('메모', $st['note']??''); ?>
      </div>
      <div style="height:12px"></div>
    </div>
    <div class="cta-wrap" style="background:#F6F6F6">
      <button class="cta" type="button" onclick="termsOpen()">신청 완료</button>
    </div>
    <?php
    render_terms_modal(find_terms(), 'find.php?action=submit');
    render_postlude('find');
}

function render_done(): void {
    render_prelude(['title'=>'신청 완료', 'active'=>'find']);
    ?>
    <div class="content done-body" style="width:430px">
      <div class="done-circle" style="background:none">
        <img src="./ui/project/assets/Completion.png" alt="완료"/>
      </div>
      <div class="done-title">신청완료!</div>
      <div class="done-desc">간병인이 매칭되면 알림톡으로
알려드립니다.</div>
    </div>
    <div class="cta-wrap"><a class="cta" href="index.php" style="background:#111;text-align:center;display:block">홈으로</a></div>
    <?php
    render_postlude('find');
}

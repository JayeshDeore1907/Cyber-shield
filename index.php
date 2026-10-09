<?php
session_start();
$pdo = require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';
$config = require __DIR__ . '/config/config.php';
$page = $_GET['page'] ?? 'home';

function render(string $title, string $content): void { include __DIR__ . '/views/layout.php'; }
function h(string $title, string $body): void { render($title, $body); }

if ($page === 'logout') { unset($_SESSION['admin']); $_SESSION['flash']=['type'=>'ok','message'=>'Logged out successfully.']; header('Location:index.php'); exit; }

if ($page === 'admin-login') {
    if (!empty($_SESSION['admin'])) { header('Location:index.php?page=admin'); exit; }
    if ($_SERVER['REQUEST_METHOD']==='POST') { verify_csrf(); $u=trim($_POST['username']??''); $p=$_POST['password']??''; if($u===$config['admin_username'] && $p==='admin123'){$_SESSION['admin']=true;header('Location:index.php?page=admin');exit;} $error='Invalid demo admin credentials.'; }
    ob_start(); ?><section class="section"><div class="container" style="max-width:520px"><div class="card"><span class="eyebrow">Admin access</span><h1>Admin Login</h1><p class="muted">Demo credentials are listed in the project README.</p><?php if(!empty($error)):?><div class="alert error"><?=e($error)?></div><?php endif;?><form class="form" method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><div class="field"><label>Username</label><input name="username" value="admin" required></div><div class="field"><label>Password</label><div class="inline"><input id="adminPass" type="password" name="password" value="admin123" required style="flex:1"><button class="btn" type="button" data-toggle-password="adminPass">Show</button></div></div><button class="btn primary" type="submit">Sign in</button></form></div></div></section><?php $c=ob_get_clean(); h('Admin Login',$c); exit;
}

if ($page === 'admin') {
    if (empty($_SESSION['admin'])) { header('Location:index.php?page=admin-login'); exit; }
    if ($_SERVER['REQUEST_METHOD']==='POST') { verify_csrf(); $id=(int)($_POST['id']??0); $status=$_POST['status']??'Received'; $allowed=['Received','Under Review','Resolved']; if(in_array($status,$allowed,true)){ $st=$pdo->prepare('UPDATE reports SET status=? WHERE id=?');$st->execute([$status,$id]); $_SESSION['flash']=['type'=>'ok','message'=>'Report status updated.']; } header('Location:index.php?page=admin'); exit; }
    $rows=$pdo->query('SELECT * FROM reports ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC); $count=count($rows); $high=$pdo->query("SELECT COUNT(*) FROM reports WHERE fraud_type IN ('UPI Fraud','Phishing','Investment Scam')")->fetchColumn();
    ob_start(); ?><section class="section"><div class="container"><div class="section-head"><div><span class="eyebrow">Control room</span><h1>Admin Dashboard</h1><p class="muted">Manage and review submitted scam incidents.</p></div><a class="btn" href="index.php">Back to site</a></div><div class="kpis"><div class="kpi"><div class="small muted">Total reports</div><div class="stat"><?=$count?></div></div><div class="kpi"><div class="small muted">Priority categories</div><div class="stat"><?=$high?></div></div><div class="kpi"><div class="small muted">Received</div><div class="stat"><?=count(array_filter($rows,fn($r)=>$r['status']==='Received'))?></div></div><div class="kpi"><div class="small muted">Resolved</div><div class="stat"><?=count(array_filter($rows,fn($r)=>$r['status']==='Resolved'))?></div></div></div><div class="card" style="margin-top:20px"><div class="section-head"><h2>Incident reports</h2><input id="reportSearch" class="field search" placeholder="Search reports..."></div><div class="table-wrap"><table class="table"><thead><tr><th>Reference</th><th>Type</th><th>Platform</th><th>Amount</th><th>Date</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach($rows as $r):?><tr data-report-row><td><strong><?=e($r['reference_no'])?></strong></td><td><?=e($r['fraud_type'])?></td><td><?=e($r['platform'])?></td><td>₹<?=number_format((float)$r['amount'],2)?></td><td><?=e($r['incident_date'])?></td><td><span class="badge"><?=e($r['status'])?></span></td><td><form method="post" class="inline"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=$r['id']?>"><select name="status"><option>Received</option><option <?= $r['status']==='Under Review'?'selected':'' ?>>Under Review</option><option <?= $r['status']==='Resolved'?'selected':'' ?>>Resolved</option></select><button class="btn" type="submit">Update</button></form></td></tr><?php endforeach; if(!$rows):?><tr><td colspan="7" class="muted center">No reports yet.</td></tr><?php endif;?></tbody></table></div></div></div></section><?php $c=ob_get_clean(); h('Admin Dashboard',$c); exit;
}

if ($page === 'checker') {
    $result=null; $message='';
    if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$message=trim($_POST['message']??'');if($message!=='')$result=analyze_scam($message);}
    ob_start(); ?><section class="section"><div class="container"><div class="section-head"><div><span class="eyebrow">Rule-based analysis</span><h1>Scam Checker</h1><p class="muted">Paste suspicious text. The checker looks for common scam indicators; it is an awareness tool, not a fraud-proof detector.</p></div></div><div class="grid2"><div class="card"><form method="post" class="form"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><div class="field"><label>Suspicious message</label><textarea name="message" placeholder="Example: URGENT! Your KYC has expired. Click this link and share OTP to receive your refund..." required><?=e($message)?></textarea></div><button class="btn primary" type="submit">Analyze message</button></form></div><div class="card"><?php if($result):?><div class="small muted">Risk assessment</div><div class="stat"><?=$result['score']?>/100 <span class="badge <?=strtolower($result['level'])?>"><?=e($result['level'])?></span></div><div class="meter"><span style="width:<?=$result['score']?>%"></span></div><div class="hero-list"><?php foreach($result['matches'] as $m):?><div>⚠ <?=e($m)?></div><?php endforeach; if(!$result['matches']):?><div>✓ No strong scam markers detected</div><?php endif;?></div><p><strong>Recommended action:</strong><br><?=e($result['advice'])?></p><?php else:?><div class="center"><div class="icon">🔎</div><h3>Ready to check</h3><p class="muted">Your score and detected warning signs will appear here.</p></div><?php endif;?></div></div></div></section><?php $c=ob_get_clean(); h('Scam Checker',$c); exit;
}

if ($page === 'deepfake') {
    $tips=load_xml(__DIR__.'/data/tips.xml');
    ob_start(); ?><section class="section"><div class="container"><span class="eyebrow">AI-era awareness</span><h1>Deepfake &amp; Misinformation Guide</h1><p class="muted" style="max-width:780px">Generative AI can make convincing synthetic images, audio and video. Focus on source verification, context and independent confirmation rather than relying on one visual clue.</p><div class="grid2"><div class="card"><h3>🎭 Common deepfake clues</h3><div class="hero-list"><div>Unnatural lip-sync or mouth movements</div><div>Lighting/shadows that do not match the scene</div><div>Face edges, hair or earrings that distort between frames</div><div>Audio tone or room acoustics that do not match the speaker</div><div>Missing original source, date or context</div></div></div><div class="card"><h3>✅ Verification workflow</h3><div class="hero-list"><div><strong>1. Pause</strong><br><span class="muted">Do not forward it immediately.</span></div><div><strong>2. Trace</strong><br><span class="muted">Find the earliest credible source.</span></div><div><strong>3. Compare</strong><br><span class="muted">Check trusted official or reputable reporting.</span></div><div><strong>4. Context</strong><br><span class="muted">Confirm date, location and full statement.</span></div></div></div></div><div class="section-head" style="margin-top:34px"><div><h2>Cyber safety tips (XML-powered)</h2><p class="muted">These cards are loaded from <code>data/tips.xml</code>.</p></div></div><div class="grid3"><?php foreach($tips->tip as $tip):?><div class="card tip-card"><div class="icon"><?=e((string)$tip['icon'])?></div><div class="small muted"><?=e((string)$tip['category'])?></div><h3><?=e((string)$tip)?></h3></div><?php endforeach;?></div></div></section><?php $c=ob_get_clean(); h('Deepfake Guide',$c); exit;
}

if ($page === 'quiz') {
    // Load the quiz from XML and convert every question/option to regular
    // PHP arrays. SimpleXML foreach keys can be strings (for example "question"),
    // so using those keys in arithmetic such as $i + 1 can throw a TypeError.
    $xml = load_xml(__DIR__ . '/data/quiz.xml');
    $questions = [];

    foreach ($xml->question as $questionNode) {
        $options = [];
        foreach ($questionNode->option as $optionNode) {
            $options[] = (string) $optionNode;
        }

        $questions[] = [
            'text' => (string) $questionNode->text,
            'options' => $options,
            'answer' => (int) trim((string) $questionNode->answer),
        ];
    }

    $submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
    $score = 0;

    if ($submitted) {
        verify_csrf();

        foreach ($questions as $index => $question) {
            $postedAnswer = $_POST['q' . $index] ?? null;

            // Accept only numeric option indexes and compare as integers.
            if (
                $postedAnswer !== null &&
                is_scalar($postedAnswer) &&
                ctype_digit((string) $postedAnswer) &&
                (int) $postedAnswer === $question['answer']
            ) {
                $score++;
            }
        }
    }

    $questionCount = count($questions);

    ob_start();
    ?>
    <section class="section">
        <div class="container" style="max-width:900px">
            <span class="eyebrow">Interactive XML quiz</span>
            <h1>Cyber Safety Challenge</h1>
            <p class="muted">Questions are loaded from XML and evaluated by PHP.</p>

            <?php if ($submitted): ?>
                <div class="card">
                    <div class="center">
                        <div class="stat"><?= $score ?> / <?= $questionCount ?></div>
                        <h3>
                            <?= $score >= 4
                                ? 'Excellent awareness!'
                                : ($score >= 3
                                    ? 'Good job — review the missed topics.'
                                    : 'Keep learning — repeat the challenge after reviewing the guide.') ?>
                        </h3>
                        <a class="btn primary" href="index.php?page=quiz">Try again</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$submitted): ?>
                <form id="quizForm" method="post" class="form">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <div class="progress" id="quizProgress"><span></span></div>

                    <?php foreach ($questions as $index => $question): ?>
                        <div class="card question-block">
                            <div class="small muted">
                                Question <?= $index + 1 ?> of <?= $questionCount ?>
                            </div>
                            <h3><?= e($question['text']) ?></h3>

                            <?php foreach ($question['options'] as $optionIndex => $optionText): ?>
                                <label class="quiz-option">
                                    <input
                                        required
                                        type="radio"
                                        name="q<?= $index ?>"
                                        value="<?= $optionIndex ?>"
                                    >
                                    <?= e($optionText) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>

                    <button class="btn primary" type="submit">Submit quiz</button>
                </form>
            <?php endif; ?>
        </div>
    </section>
    <?php
    $content = ob_get_clean();
    h('Cyber Safety Quiz', $content);
    exit;
}

if ($page === 'report') {
    $errors=[]; $success=null;
    if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();$fields=['fraud_type','platform','incident_date','description'];foreach($fields as $f){if(trim($_POST[$f]??'')==='')$errors[]='Please complete all required fields.';break;}if(!$errors){$ref='CS-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));$stmt=$pdo->prepare('INSERT INTO reports(reference_no,fraud_type,platform,incident_date,amount,description,status,created_at) VALUES(?,?,?,?,?,?,?,?)');$stmt->execute([$ref,trim($_POST['fraud_type']),trim($_POST['platform']),trim($_POST['incident_date']),max(0,(float)($_POST['amount']??0)),trim($_POST['description']),'Received',date('Y-m-d H:i:s')]);$success=$ref;}}
    ob_start(); ?><section class="section"><div class="container" style="max-width:820px"><span class="eyebrow">Incident reporting</span><h1>Report a Scam</h1><p class="muted">Use this educational reporting module to practise form handling and server-side storage.</p><?php foreach($errors as $er):?><div class="alert error"><?=e($er)?></div><?php endforeach;?><?php if($success):?><div class="card center"><div class="small muted">Your reference number</div><div class="reference"><?=e($success)?></div><p>Save this reference to track the demo report.</p><a class="btn primary" href="index.php?page=track">Track report</a></div><?php else:?><div class="card"><form method="post" class="form"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><div class="row2"><div class="field"><label>Fraud type *</label><select name="fraud_type" required><option value="">Select</option><option>UPI Fraud</option><option>Phishing</option><option>Job Scam</option><option>Investment Scam</option><option>Social Media Scam</option><option>Identity Theft</option><option>Other</option></select></div><div class="field"><label>Platform *</label><input name="platform" placeholder="WhatsApp, SMS, Instagram, Website..." required></div></div><div class="row2"><div class="field"><label>Incident date *</label><input type="date" name="incident_date" max="<?=date('Y-m-d')?>" required></div><div class="field"><label>Amount involved (₹)</label><input type="number" min="0" step="0.01" name="amount" value="0"></div></div><div class="field"><label>Description *</label><textarea name="description" placeholder="What happened? Do not enter passwords, OTPs, PINs or card details." required></textarea></div><div class="notice"><strong>Privacy reminder:</strong> This college project stores demo data locally. Never enter real passwords, OTPs, card numbers or sensitive credentials.</div><button class="btn primary" type="submit">Submit incident report</button></form></div><?php endif;?></div></section><?php $c=ob_get_clean(); h('Report a Scam',$c); exit;
}

if ($page === 'track') {
    $row=null;$ref=trim($_GET['ref']??''); if($ref!==''){ $st=$pdo->prepare('SELECT * FROM reports WHERE reference_no=?');$st->execute([$ref]);$row=$st->fetch(PDO::FETCH_ASSOC); }
    ob_start(); ?><section class="section"><div class="container" style="max-width:760px"><span class="eyebrow">Reference lookup</span><h1>Track a Report</h1><form class="card" method="get"><input type="hidden" name="page" value="track"><div class="field"><label>Reference number</label><input name="ref" value="<?=e($ref)?>" placeholder="CS-20261008-ABC123" required></div><button class="btn primary" type="submit" style="margin-top:12px">Find report</button></form><?php if($ref):?><div class="card" style="margin-top:18px"><?php if($row):?><div class="small muted">Status</div><div class="stat"><?=e($row['status'])?></div><p><strong><?=e($row['reference_no'])?></strong> · <?=e($row['fraud_type'])?> · <?=e($row['platform'])?></p><p class="muted">Submitted <?=e($row['created_at'])?></p><div class="notice">Do not use the demo tracker as a substitute for the official National Cyber Crime Reporting Portal.</div><?php else:?><div class="alert error">No report found for that reference number.</div><?php endif;?></div><?php endif;?></div></section><?php $c=ob_get_clean(); h('Track Report',$c); exit;
}

ob_start(); ?><section class="hero"><div class="container hero-grid"><div><span class="eyebrow">2026 cyber awareness portal</span><h1>Think before you <span style="color:var(--accent)">click.</span></h1><p>CyberShield helps students understand deepfakes, misinformation and common online scams with practical awareness tools, an interactive checker, an XML-powered quiz and an incident-reporting workflow.</p><div class="cta-row"><a class="btn primary" href="index.php?page=checker">Check a suspicious message</a><a class="btn" href="index.php?page=deepfake">Learn about deepfakes</a></div></div><div class="hero-card"><div class="small muted">Community awareness interactions</div><div class="score" id="liveCount">1,284</div><div class="pulse"><span class="dot"></span> Live demo counter</div><div class="hero-list"><div>🔎 Rule-based scam checker</div><div>🎭 Deepfake verification guide</div><div>🧠 XML-powered safety quiz</div><div>📝 Incident report + tracking</div></div></div></div></section><section class="section"><div class="container"><div class="section-head"><div><span class="eyebrow">Core modules</span><h2>Everything in one portal</h2></div></div><div class="grid3"><div class="card"><div class="icon">🔍</div><h3>Scam Checker</h3><p class="muted">Identify common warning signs in suspicious text using PHP and JavaScript.</p><a href="index.php?page=checker" class="btn">Open checker</a></div><div class="card"><div class="icon">🎭</div><h3>Deepfake Guide</h3><p class="muted">Learn a practical workflow for source, context and media verification.</p><a href="index.php?page=deepfake" class="btn">Open guide</a></div><div class="card"><div class="icon">📝</div><h3>Report &amp; Track</h3><p class="muted">Submit a demo incident and practise CRUD/status management through the admin panel.</p><a href="index.php?page=report" class="btn">Report scam</a></div></div></div></section><section class="section"><div class="container"><div class="card"><div class="section-head"><div><span class="eyebrow">India context</span><h2>Use official channels for real incidents</h2></div></div><p class="muted">For real cyber-fraud incidents in India, use the official National Cyber Crime Reporting Portal and the national helpline 1930. CyberShield is an educational project and does not replace official reporting or investigation.</p><div class="cta-row"><a class="btn primary" href="https://www.cybercrime.gov.in/" target="_blank" rel="noopener">Open official portal ↗</a><a class="btn" href="index.php?page=quiz">Take the quiz</a></div></div></div></section><?php $c=ob_get_clean(); h('Home',$c);

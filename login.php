<?php
require_once 'includes/config.php';
if (isLoggedIn()) { redirect(SITE_URL . '/dashboard.php'); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'suspended') {
                $error = 'Your account has been suspended. Contact support.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['is_admin'] = $user['is_admin'];
                redirect(SITE_URL . '/dashboard.php', 'Welcome back, ' . explode(' ', $user['full_name'])[0] . '!', 'success');
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

// ── RESET PASSWORD HANDLER ──────────────────────────────────────────
$resetMsg   = '';
$resetError = '';
$resetStep  = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_step1'])) {
    $rEmail = trim($_POST['reset_email'] ?? '');
    $rPhone = trim($_POST['reset_phone'] ?? '');
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND phone = ? LIMIT 1");
    $stmt->execute([$rEmail, $rPhone]);
    $rUser = $stmt->fetch();
    if ($rUser) {
        $_SESSION['reset_user_id'] = $rUser['id'];
        $resetStep = 2;
    } else {
        $resetError = 'No account found with that email and phone combination.';
        $resetStep  = 1;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_step2'])) {
    $newPass     = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';
    $resetUid    = (int)($_SESSION['reset_user_id'] ?? 0);

    if (empty($resetUid)) {
        $resetError = 'Session expired. Please start again.';
        $resetStep  = 1;
    } elseif (strlen($newPass) < 6) {
        $resetError = 'Password must be at least 6 characters.';
        $resetStep  = 2;
    } elseif ($newPass !== $confirmPass) {
        $resetError = 'Passwords do not match.';
        $resetStep  = 2;
    } else {
        $db   = getDB();
        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $db->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $resetUid]);
        unset($_SESSION['reset_user_id']);
        $resetMsg  = 'Password reset successfully! You can now log in.';
        $resetStep = 1;
    }
}

// Keep step 2 open if session has a pending reset
if (isset($_SESSION['reset_user_id']) && $resetStep === 1 && empty($resetError)) {
    $resetStep = 2;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <style>
        .reset-modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.55);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .reset-modal-overlay.open { display: flex; animation: fadeIn .2s ease; }
        .reset-modal {
            background: #fff;
            border-radius: 16px;
            width: 100%; max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
            overflow: hidden;
            animation: scaleIn .2s ease;
        }
        .reset-modal-header {
            background: linear-gradient(135deg, #1a56db, #0ea5e9);
            color: white;
            padding: 24px;
            text-align: center;
        }
        .reset-modal-header .icon {
            width: 52px; height: 52px;
            background: rgba(255,255,255,.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            margin: 0 auto 10px;
        }
        .reset-modal-header h3 { font-family: var(--font); font-size: 1.2rem; font-weight: 800; margin: 0; }
        .reset-modal-header p  { opacity: .85; font-size: .85rem; margin: 4px 0 0; }
        .reset-modal-body   { padding: 24px; }
        .reset-modal-footer { padding: 0 24px 20px; }
        .step-indicator {
            display: flex; align-items: center; justify-content: center;
            gap: 8px; margin-bottom: 20px;
        }
        .step-dot {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .75rem; font-weight: 700;
        }
        .step-dot.active { background: var(--primary); color: white; }
        .step-dot.done   { background: var(--success);  color: white; }
        .step-dot.idle   { background: var(--border);   color: var(--gray); }
        .step-line { flex: 1; height: 2px; background: var(--border); max-width: 40px; }
        .step-line.done { background: var(--success); }
        @keyframes fadeIn  { from{opacity:0}  to{opacity:1} }
        @keyframes scaleIn { from{opacity:0;transform:scale(.92)} to{opacity:1;transform:scale(1)} }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">
        <a href="<?php echo SITE_URL; ?>/index.php">
            <span class="brand-icon"><i class="fas fa-award"></i></span>
            <span class="brand-text"><?php echo SITE_NAME; ?></span>
        </a>
    </div>
    <div class="navbar-right">
        <a href="<?php echo SITE_URL; ?>/register.php" class="btn-outline-sm">Create Account</a>
    </div>
</nav>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="brand-icon"><i class="fas fa-award"></i></div>
            <h2>Welcome Back</h2>
            <p>Sign in to your <?php echo SITE_NAME; ?> account</p>
        </div>

        <?php if ($error): ?>
        <div class="flash-alert flash-danger"><i class="fas fa-times-circle"></i> <?php echo sanitize($error); ?></div>
        <?php endif; ?>

        <?php if ($resetMsg): ?>
        <div class="flash-alert flash-success"><i class="fas fa-check-circle"></i> <?php echo sanitize($resetMsg); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="you@example.com"
                       value="<?php echo sanitize($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" style="display:flex;justify-content:space-between;align-items:center;">
                    <span>Password</span>
                    <button type="button" onclick="openResetModal()"
                            style="background:none;border:none;color:var(--primary);font-size:.82rem;font-weight:700;cursor:pointer;padding:0;text-decoration:underline;">
                        Forgot Password?
                    </button>
                </label>
                <div style="position:relative;">
                    <input type="password" name="password" id="loginPassword" class="form-control"
                           placeholder="Enter your password" required style="padding-right:44px;">
                    <button type="button" onclick="togglePwd('loginPassword','eyeLogin')"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--gray);cursor:pointer;font-size:.95rem;">
                        <i class="fas fa-eye" id="eyeLogin"></i>
                    </button>
                </div>
            </div>
            <button type="submit" name="login" class="btn btn-primary btn-block btn-lg" style="margin-top:8px;">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="<?php echo SITE_URL; ?>/register.php" style="font-weight:700;">Create one free</a>
        </div>
        <div class="auth-footer" style="margin-top:6px;">
            <small style="color:#94a3b8;">Earn with Rewardke </small>
        </div>
    </div>
</div>


<!-- ══════════════════════════════════════
     FORGOT PASSWORD MODAL
══════════════════════════════════════ -->
<div class="reset-modal-overlay <?php echo ($resetStep === 2 || !empty($resetError)) ? 'open' : ''; ?>"
     id="resetModalOverlay">
    <div class="reset-modal">

        <div class="reset-modal-header">
            <div class="icon"><i class="fas fa-key"></i></div>
            <h3>Reset Password</h3>
            <p>Verify your identity to set a new password</p>
        </div>

        <div class="reset-modal-body">

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step-dot <?php echo $resetStep >= 2 ? 'done' : 'active'; ?>">
                    <?php echo $resetStep >= 2 ? '<i class="fas fa-check"></i>' : '1'; ?>
                </div>
                <div class="step-line <?php echo $resetStep >= 2 ? 'done' : ''; ?>"></div>
                <div class="step-dot <?php echo $resetStep === 2 ? 'active' : 'idle'; ?>">2</div>
            </div>

            <?php if (!empty($resetError)): ?>
            <div class="flash-alert flash-danger" style="margin-bottom:14px;">
                <i class="fas fa-times-circle"></i> <?php echo sanitize($resetError); ?>
            </div>
            <?php endif; ?>

            <!-- STEP 1: Verify Identity -->
            <?php if ($resetStep === 1): ?>
            <form method="POST" action="">
                <p style="font-size:.875rem;color:var(--gray);margin-bottom:18px;">
                    Enter your registered email and phone number to verify your identity.
                </p>
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-envelope" style="color:var(--primary);margin-right:5px;"></i>Email Address</label>
                    <input type="email" name="reset_email" class="form-control"
                           placeholder="you@example.com"
                           value="<?php echo sanitize($_POST['reset_email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-phone" style="color:var(--primary);margin-right:5px;"></i>Phone Number</label>
                    <input type="text" name="reset_phone" class="form-control"
                           placeholder="07XXXXXXXX"
                           value="<?php echo sanitize($_POST['reset_phone'] ?? ''); ?>" required>
                    <div class="form-text">Use the phone number you registered with</div>
                </div>
                <button type="submit" name="reset_step1" class="btn btn-primary btn-block">
                    <i class="fas fa-arrow-right"></i> Verify & Continue
                </button>
            </form>

            <!-- STEP 2: New Password -->
            <?php elseif ($resetStep === 2): ?>
            <form method="POST" action="">
                <div style="display:flex;align-items:center;gap:8px;background:#ecfdf5;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:.85rem;color:#065f46;">
                    <i class="fas fa-check-circle"></i>
                    <span>Identity verified! Set your new password below.</span>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <div style="position:relative;">
                        <input type="password" name="new_password" id="newPwd" class="form-control"
                               placeholder="Min 6 characters" required style="padding-right:44px;">
                        <button type="button" onclick="togglePwd('newPwd','eyeNew')"
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--gray);cursor:pointer;font-size:.95rem;">
                            <i class="fas fa-eye" id="eyeNew"></i>
                        </button>
                    </div>
                    <!-- Strength bar -->
                    <div style="margin-top:8px;">
                        <div class="progress"><div class="progress-bar" id="strengthBar" style="width:0%;transition:width .3s,background .3s;"></div></div>
                        <div style="font-size:.72rem;margin-top:3px;font-weight:600;" id="strengthLabel"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <div style="position:relative;">
                        <input type="password" name="confirm_password" id="confirmPwd" class="form-control"
                               placeholder="Repeat new password" required style="padding-right:44px;">
                        <button type="button" onclick="togglePwd('confirmPwd','eyeConfirm')"
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--gray);cursor:pointer;font-size:.95rem;">
                            <i class="fas fa-eye" id="eyeConfirm"></i>
                        </button>
                    </div>
                    <div style="font-size:.75rem;margin-top:4px;font-weight:600;" id="matchLabel"></div>
                </div>
                <button type="submit" name="reset_step2" class="btn btn-success btn-block">
                    <i class="fas fa-lock"></i> Reset My Password
                </button>
            </form>
            <?php endif; ?>

        </div><!-- /.reset-modal-body -->

        <div class="reset-modal-footer">
            <button type="button" onclick="closeResetModal()"
                    style="width:100%;padding:10px;background:none;border:1.5px solid var(--border);border-radius:8px;color:var(--gray);cursor:pointer;font-size:.85rem;font-weight:600;transition:all .2s;">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>

    </div>
</div>
<!-- END MODAL -->


<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
<script>
// ── Toggle password show/hide ──
function togglePwd(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// ── Modal open / close ──
function openResetModal() {
    document.getElementById('resetModalOverlay').classList.add('open');
}
function closeResetModal() {
    document.getElementById('resetModalOverlay').classList.remove('open');
}
document.getElementById('resetModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeResetModal();
});

// ── Password strength meter ──
const newPwdInput = document.getElementById('newPwd');
const confirmInput = document.getElementById('confirmPwd');

if (newPwdInput) {
    newPwdInput.addEventListener('input', function () {
        const val = this.value;
        let score = 0;
        if (val.length >= 6)              score++;
        if (val.length >= 10)             score++;
        if (/[A-Z]/.test(val))            score++;
        if (/[0-9]/.test(val))            score++;
        if (/[^A-Za-z0-9]/.test(val))    score++;

        const levels = [
            { pct: '20%',  color: '#ef4444', text: 'Very Weak' },
            { pct: '40%',  color: '#f97316', text: 'Weak' },
            { pct: '60%',  color: '#eab308', text: 'Fair' },
            { pct: '80%',  color: '#3b82f6', text: 'Strong' },
            { pct: '100%', color: '#10b981', text: 'Very Strong ✓' },
        ];
        const lvl = levels[Math.max(score - 1, 0)];
        const bar   = document.getElementById('strengthBar');
        const label = document.getElementById('strengthLabel');
        bar.style.width      = lvl.pct;
        bar.style.background = lvl.color;
        label.textContent    = lvl.text;
        label.style.color    = lvl.color;

        checkMatch();
    });
}

// ── Password match indicator ──
if (confirmInput) {
    confirmInput.addEventListener('input', checkMatch);
}
function checkMatch() {
    const label = document.getElementById('matchLabel');
    if (!label || !confirmInput || !newPwdInput) return;
    if (confirmInput.value.length === 0) { label.textContent = ''; return; }
    if (confirmInput.value === newPwdInput.value) {
        label.textContent = '✓ Passwords match';
        label.style.color = '#10b981';
    } else {
        label.textContent = '✗ Passwords do not match';
        label.style.color = '#ef4444';
    }
}
</script>
</body>
</html>

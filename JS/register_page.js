function togglePw(inputId, iconId) {
  const input  = document.getElementById(inputId);
  const icon   = document.getElementById(iconId);
  const isHide = input.type === 'password';

  input.type = isHide ? 'text' : 'password';

  icon.innerHTML = isHide
    ? /* eye-off */`
        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8
                 a18.45 18.45 0 0 1 5.06-5.94"/>
        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8
                 a18.5 18.5 0 0 1-2.16 3.19"/>
        <line x1="1" y1="1" x2="23" y2="23"/>
      `
    : /* eye */`
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
        <circle cx="12" cy="12" r="3"/>
      `;
}

/* ── Password strength meter ── */
(function () {
  const pwInput     = document.getElementById('password');
  const bar         = document.getElementById('strength-bar');
  const barWrap     = document.getElementById('strength-wrap');
  const strengthLbl = document.getElementById('strength-label');

  if (!pwInput) return;

  function checkStrength(val) {
    let score = 0;
    if (val.length >= 8)                      score++;
    if (val.length >= 12)                     score++;
    if (/[A-Z]/.test(val))                    score++;
    if (/[0-9]/.test(val))                    score++;
    if (/[^A-Za-z0-9]/.test(val))            score++;
    return score;
  }

  pwInput.addEventListener('input', function () {
    const val = pwInput.value;
    if (!val) {
      barWrap.style.display = 'none';
      strengthLbl.textContent = '';
      return;
    }

    barWrap.style.display = 'block';
    const score = checkStrength(val);

    const levels = [
      { pct: '20%',  color: '#ef4444', label: 'Very weak',  labelColor: '#ef4444' },
      { pct: '40%',  color: '#f97316', label: 'Weak',       labelColor: '#f97316' },
      { pct: '60%',  color: '#eab308', label: 'Fair',       labelColor: '#ca8a04' },
      { pct: '80%',  color: '#22c55e', label: 'Strong',     labelColor: '#16a34a' },
      { pct: '100%', color: '#15803d', label: 'Very strong',labelColor: '#15803d' },
    ];

    const lvl = levels[Math.min(score - 1, 4)] || levels[0];
    bar.style.width      = lvl.pct;
    bar.style.background = lvl.color;
    strengthLbl.textContent  = lvl.label;
    strengthLbl.style.color  = lvl.labelColor;
  });
})();

/* ── Password match indicator ── */
(function () {
  const pw      = document.getElementById('password');
  const confirm = document.getElementById('confirm_pass');
  const lbl     = document.getElementById('match-label');

  if (!pw || !confirm) return;

  function checkMatch() {
    if (!confirm.value) {
      lbl.textContent = '';
      return;
    }
    if (pw.value === confirm.value) {
      lbl.textContent = '✓ Passwords match';
      lbl.style.color = '#16a34a';
      confirm.classList.remove('error');
    } else {
      lbl.textContent = '✗ Passwords do not match';
      lbl.style.color = '#ef4444';
    }
  }

  confirm.addEventListener('input', checkMatch);
  pw.addEventListener('input', checkMatch);
})();

/* ── Client-side validation + loading state ── */
(function () {
  const form = document.getElementById('register-form');
  const btn  = document.getElementById('submit-btn');
  if (!form) return;

  const requiredIds = ['student_id', 'name', 'email', 'course', 'year_level', 'password', 'confirm_pass'];

  form.addEventListener('submit', function (e) {
    let ok = true;

    requiredIds.forEach(id => {
      const el = document.getElementById(id);
      if (el) el.classList.remove('error');
    });

    requiredIds.forEach(id => {
      const el = document.getElementById(id);
      if (el && !el.value.trim()) {
        el.classList.add('error');
        ok = false;
      }
    });

    // Email format + EVSU domain only
    const emailEl = document.getElementById('email');
    if (emailEl && emailEl.value) {
      const emailVal = emailEl.value.trim();
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
        emailEl.classList.add('error');
        ok = false;
      } else if (!/^[^\s@]+@evsu\.edu\.ph$/i.test(emailVal)) {
        emailEl.classList.add('error');
        ok = false;
        alert('Registration is only allowed with an @evsu.edu.ph email address.');
      }
    }

    // Password length
    const pw = document.getElementById('password');
    if (pw && pw.value && pw.value.length < 8) {
      pw.classList.add('error');
      ok = false;
    }

    // Password match
    const confirm = document.getElementById('confirm_pass');
    if (pw && confirm && pw.value !== confirm.value) {
      confirm.classList.add('error');
      ok = false;
    }

    if (!ok) {
      e.preventDefault();
      form.classList.add('shake');
      setTimeout(() => form.classList.remove('shake'), 450);
      // Scroll to first error
      const firstErr = form.querySelector('.error');
      if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    btn.classList.add('loading');
    btn.textContent = 'Creating account…';
    btn.disabled = true;
  });

  // Live clear error on input
  requiredIds.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', () => el.classList.remove('error'));
  });
})();
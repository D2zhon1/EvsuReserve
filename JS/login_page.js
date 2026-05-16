/* =============================================
   EVSU RESERVE — login.js
   ============================================= */

/* ── Toggle password visibility ── */
function togglePw() {
  const input  = document.getElementById('password');
  const icon   = document.getElementById('eye-icon');
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

/* ── Client-side validation + loading state ── */
(function () {
  const form = document.getElementById('login-form');
  const btn  = document.getElementById('submit-btn');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    let ok = true;
    const sid = document.getElementById('student_id');
    const pw  = document.getElementById('password');

    [sid, pw].forEach(el => el.classList.remove('error'));

    if (!sid.value.trim()) { sid.classList.add('error'); ok = false; }
    if (!pw.value.trim())  { pw.classList.add('error');  ok = false; }

    if (!ok) {
      e.preventDefault();
      form.classList.add('shake');
      setTimeout(() => form.classList.remove('shake'), 450);
      return;
    }

    btn.classList.add('loading');
    btn.textContent = 'Signing in…';
    btn.disabled = true;
  });

  ['student_id', 'password'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('input', () => el.classList.remove('error'));
  });
})();
document.addEventListener('DOMContentLoaded', () => {

    // Toggle tutor subject field (used by inline onchange)
    window.toggleSubject = function () {
        const role = document.querySelector('[name="role"]').value;
        const subject = document.getElementById('subjectField');
        if (subject) {
            subject.style.display = role === 'tutor' ? 'block' : 'none';
        }
    };

    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm-password');
    const bar = document.getElementById('passwordStrengthBar');
    const text = document.getElementById('passwordStrengthText');
    const confirmText = document.getElementById('confirmPasswordText');

    if (!password || !bar || !text) return;

    password.addEventListener('input', () => {
        const val = password.value;
        let strength = 0;

        if (val.length >= 6) strength++;
        if (/[A-Z]/.test(val)) strength++;
        if (/[0-9]/.test(val)) strength++;
        if (/[^A-Za-z0-9]/.test(val)) strength++;

        // Reset state
        if (!val) {
            bar.style.width = '0%';
            bar.className = 'h-2 w-0 bg-gray-300 transition-all';
            text.textContent = 'Password strength';
            return;
        }

        const levels = [
            { width: '25%', color: 'bg-red-500', text: 'Weak password' },
            { width: '50%', color: 'bg-yellow-500', text: 'Medium password' },
            { width: '75%', color: 'bg-blue-500', text: 'Good password' },
            { width: '100%', color: 'bg-green-500', text: 'Strong password' }
        ];

        const index = Math.max(0, Math.min(strength - 1, levels.length - 1));
        const lvl = levels[index];

        bar.style.width = lvl.width;
        bar.className = 'h-2 transition-all ' + lvl.color;
        text.textContent = lvl.text;
    });

    if (!confirmPassword || !confirmText) return;

    confirmPassword.addEventListener('input', () => {
        if (!confirmPassword.value) {
            confirmText.textContent = '';
            return;
        }

        if (confirmPassword.value === password.value) {
            confirmText.textContent = 'Passwords match';
            confirmText.className = 'mt-1 text-xs text-green-600';
        } else {
            confirmText.textContent = 'Passwords do not match';
            confirmText.className = 'mt-1 text-xs text-red-600';
        }
    });
});

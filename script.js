        document.addEventListener('DOMContentLoaded', () => {
            const themeBtn = document.getElementById('themeToggle');
            const body = document.body;
            const currentTheme = localStorage.getItem('theme');

            if (currentTheme === 'dark') {
                body.classList.add('dark-mode');
                if (themeBtn) themeBtn.innerHTML = '<i class="fa-solid fa-sun"></i>';
            }

            themeBtn?.addEventListener('click', () => {
                body.classList.toggle('dark-mode');
                const isDark = body.classList.contains('dark-mode');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
                themeBtn.innerHTML = isDark ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
            });

            const balanceEl = document.getElementById('mainBal');
            if (balanceEl) {
                const realBalance = window.userBalance || 0;
                animateValue(balanceEl, 0, realBalance, 1500);
            }

            const ctx = document.getElementById('spendingChart')?.getContext('2d');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        datasets: [{
                            label: 'Daily Spending ($)',
                            data: [450, 200, 600, 300, 800, 150, 400],
                            borderColor: '#1E4ED8',
                            backgroundColor: 'rgba(30, 78, 216, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true }, x: { grid: { display: false } } }
                    }
                });
            }

            document.getElementById('transferForm')?.addEventListener('submit', (e) => {
                e.preventDefault();
                const btn = e.target.querySelector('button');
                const formData = new FormData(e.target);

                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
                btn.disabled = true;

                fetch('transfer.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showToast('Error: ' + data.message);
                        btn.innerHTML = 'Send Money';
                        btn.disabled = false;
                    }
                })
                .catch(() => {
                    showToast('A system error occurred.');
                    btn.innerHTML = 'Send Money';
                    btn.disabled = false;
                });
            });
        });

        function animateValue(obj, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (isNaN(end)) return;
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                obj.innerHTML = '$' + (progress * (end - start) + start).toLocaleString(undefined, { minimumFractionDigits: 2 });
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        function showToast(msg) {
            const toast = document.getElementById('toast');
            toast.innerText = msg;
            toast.style.transform = 'translateY(0)';

            setTimeout(() => {
                toast.style.transform = 'translateY(-100px)';
            }, 4000);
        }


// منصة التعليم — سلوكيات الواجهة
document.addEventListener('DOMContentLoaded', function () {
    // قائمة الجوال
    var toggle = document.querySelector('.nav-toggle');
    var links = document.getElementById('navLinks');
    if (toggle && links) {
        toggle.addEventListener('click', function () {
            links.classList.toggle('open');
        });
    }

    // إخفاء التنبيهات تلقائيا بعد 6 ثوانٍ
    document.querySelectorAll('.alert-success').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .5s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 500);
        }, 6000);
    });

    // تأكيد قبل تسجيل الخروج
    document.querySelectorAll('a.logout').forEach(function (a) {
        a.addEventListener('click', function (e) {
            if (!confirm('هل تريد تسجيل الخروج فعلا؟')) e.preventDefault();
        });
    });
});

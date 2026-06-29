<script>
(function () {
    function pad(n) { return String(n).padStart(2, '0'); }

    function updateCountdowns() {
        document.querySelectorAll('.flash-countdown').forEach(function (el) {
            var diff = new Date(el.dataset.end).getTime() - Date.now();

            if (diff <= 0) {
                el.classList.add('expired');
                el.querySelectorAll('.countdown-value').forEach(function (v) { v.textContent = '00'; });
                return;
            }

            el.querySelector('.js-cd-days').textContent  = pad(Math.floor(diff / 86400000));
            el.querySelector('.js-cd-hours').textContent = pad(Math.floor(diff / 3600000) % 24);
            el.querySelector('.js-cd-mins').textContent  = pad(Math.floor(diff / 60000) % 60);
            el.querySelector('.js-cd-secs').textContent  = pad(Math.floor(diff / 1000) % 60);
        });
    }

    updateCountdowns();
    setInterval(updateCountdowns, 1000);
}());
</script>

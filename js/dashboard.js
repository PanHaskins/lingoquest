document.addEventListener('DOMContentLoaded', function () {
    // Adding event listener to locked cards
    const lockedCards = document.querySelectorAll('.card.locked');
    lockedCards.forEach(card => {
        card.addEventListener('click', function (e) {
            e.preventDefault();
            this.classList.add('shaking');
            this.addEventListener('animationend', function () {
                this.classList.remove('shaking');
            }, { once: true });
        });
    });

    // Checking for the existence of a mode-switch before adding a listener
    const modeSwitch = document.getElementById('mode-switch');
    if (modeSwitch) {
        modeSwitch.addEventListener('change', function() {
            const isCourseMode = this.checked;
            const newMode = isCourseMode ? 'course' : 'sandbox';
            const url = new URL(window.location.href);
            url.searchParams.set('mode', newMode);
            window.location.href = url.toString();
        });
    }
});
document.addEventListener('DOMContentLoaded', function () {
    const modeSwitch = document.getElementById('mode-switch');
    if (modeSwitch) {
        modeSwitch.addEventListener('change', function() {
            const isCourseMode = this.checked;
            const newMode = isCourseMode ? 'course' : 'sandbox';
            const modeLabel = document.querySelector('.mode-label');
            
            // Update URL without reloading the page
            const url = new URL(window.location.href);
            url.searchParams.set('mode', newMode);
            window.history.pushState({}, document.title, url.toString());
            
            if (modeLabel) {
                modeLabel.textContent = newMode.toUpperCase();
            }
            
            fetch('/english.php?mode=' + newMode, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(data => {
                const mainElement = document.querySelector('main');
                if (mainElement) {
                    mainElement.innerHTML = data;
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }
});
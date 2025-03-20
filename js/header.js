document.addEventListener('DOMContentLoaded', () => {
    const languageButton = document.getElementById('language-button');
    const profileButton = document.getElementById('profile-button');
    const languageDropdown = document.querySelector('#language-select .dropdown');
    const profileDropdown = document.querySelector('#profile-select .dropdown');

    if (languageButton && languageDropdown) {
        languageButton.addEventListener('click', (e) => {
            e.stopPropagation();
            languageButton.classList.toggle('active');
            languageDropdown.classList.toggle('show');
        });
    }

    if (profileButton && profileDropdown) {
        profileButton.addEventListener('click', (e) => {
            e.stopPropagation();
            profileButton.classList.toggle('active');
            profileDropdown.classList.toggle('show');
        });
    }

    document.addEventListener('click', (e) => {
        if (languageButton && !languageButton.contains(e.target)) {
            languageButton.classList.remove('active');
            languageDropdown.classList.remove('show');
        }
        if (profileButton && !profileButton.contains(e.target)) {
            profileButton.classList.remove('active');
            profileDropdown.classList.remove('show');
        }
    });
});
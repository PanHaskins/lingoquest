// Global variables for tracking game state
let currentImageIndex = 0;
let correctCount = 0;
let incorrectCount = 0;
let feedbackVisible = false;
let images = defaultImages || [];
let selectedCategories = [];

// DOM element references
const imageBoxElement = document.querySelector('.image-box');
const imageElement = document.getElementById('image');
const answerInput = document.getElementById('answer-input');
const correctCountElement = document.getElementById('correct-count');
const incorrectCountElement = document.getElementById('incorrect-count');
const percentageElement = document.getElementById('percentage');
const progressCorrect = document.querySelector('.progress-correct');
const progressIncorrect = document.querySelector('.progress-incorrect');
const feedbackElement = document.getElementById('feedback');
const settingsModal = document.getElementById('settings-modal');

// Initialize images and display first image if available, otherwise fetch from server
if (images.length > 0) {
    displayImage();
} else {
    fetchImagesFromServer();
}

// Event listener for answer submission via Enter or Right Arrow key
answerInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' || e.key === 'ArrowRight') {
        e.preventDefault();
        checkAnswer();
    }
});

answerInput.focus();

// Load and display the next image
function loadImage() {
    if (currentImageIndex >= images.length || images.length === 0) {
        fetchImagesFromServer();
    } else {
        displayImage();
    }
}

// Fetch images from server via POST request
function fetchImagesFromServer() {
    const categories = selectedCategories.length > 0 ? selectedCategories : ['all'];
    fetch(window.location.pathname + '?api=true', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_images', categories })
    })
    .then(response => {
        if (response.status === 200) {
            return response.json();
        } else if (response.status === 400) {
            throw new Error('Invalid request');
        } else if (response.status === 429) {
            throw new Error('Too many requests');
        } else if (response.status === 500) {
            throw new Error('Server error');
        } else {
            throw new Error(`Unexpected status: ${response.status}`);
        }
    })
    .then(data => {
        images = data.images || [];
        currentImageIndex = 0;
        if (images.length > 0) {
            displayImage();
        } else {
            showNotification('warning', 'No images available');
        }
    })
    .catch(error => {
        console.error('Error fetching images:', error.message);
        showNotification('error', error.message === 'Too many requests' ? 'Too many requests, please slow down' : translations.server_error);
    });
}

// Display an image for naming
function displayImage() {
    imageElement.src = images[currentImageIndex]['image_url'];
    imageBoxElement.classList.remove('correct', 'incorrect');
    answerInput.value = '';
    answerInput.focus();
    if (!feedbackVisible) {
        feedbackElement.textContent = '';
    }
}

// Check user's answer and update game state
function checkAnswer() {
    const userAnswer = answerInput.value.trim();
    if (!userAnswer) return;

    const correctAnswers = images[currentImageIndex][languages.target];
    let isCorrect = correctAnswers.some(answer => answer.toLowerCase() === userAnswer.toLowerCase());

    if (isCorrect) {
        correctCount++;
        imageBoxElement.classList.remove('incorrect');
        imageBoxElement.classList.add('correct');
        feedbackElement.textContent = '';
        feedbackVisible = false;
    } else {
        incorrectCount++;
        imageBoxElement.classList.remove('correct');
        imageBoxElement.classList.add('incorrect');
        const randomCorrectAnswer = correctAnswers[Math.floor(Math.random() * correctAnswers.length)];
        feedbackElement.textContent = translations.incorrect_answer.replace('{correct}', randomCorrectAnswer);
        feedbackVisible = true;
    }

    updateProgress();
    currentImageIndex++;
    setTimeout(loadImage, 500);
}

// Update progress bar and counters
function updateProgress() {
    correctCountElement.textContent = correctCount;
    incorrectCountElement.textContent = incorrectCount;

    const total = correctCount + incorrectCount;
    const correctPercentage = total > 0 ? (correctCount / total) * 100 : 50;
    const incorrectPercentage = total > 0 ? (incorrectCount / total) * 100 : 50;

    progressCorrect.style.width = `${correctPercentage}%`;
    progressIncorrect.style.width = `${incorrectPercentage}%`;
    percentageElement.textContent = `${Math.round(correctPercentage)}%`;
}

// Navigate back to previous page
function goBack() {
    window.history.back();
}

// Open settings modal
function openSettings() {
    settingsModal.innerHTML = `
        <div class="modal-content">
            <h2 class="modal-title">${translations.settings_title}</h2>
            <div class="settings-section">
                <h3>${translations.select_categories}</h3>
                ${imageCategories.map(cat => `
                    <div class="checkbox-group">
                        <input type="checkbox" name="category" value="${cat.id}" id="cat-${cat.id}" ${selectedCategories.includes(cat.id) ? 'checked' : ''}>
                        <label for="cat-${cat.id}">${cat.name}</label>
                    </div>
                `).join('')}
            </div>
            <div class="modal-buttons">
                <button class="btn btn-accept">${translations.yes}</button>
                <button class="btn btn-cancel">${translations.no}</button>
            </div>
        </div>
    `;
    settingsModal.style.display = 'flex';

    const acceptBtn = settingsModal.querySelector('.btn-accept');
    const cancelBtn = settingsModal.querySelector('.btn-cancel');

    acceptBtn.addEventListener('click', () => {
        selectedCategories = Array.from(settingsModal.querySelectorAll('input[name="category"]:checked'))
            .map(checkbox => checkbox.value);
        fetch(window.location.pathname + '?api=true', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'set_categories', categories: selectedCategories.length > 0 ? selectedCategories : ['all'] })
        })
        .then(response => {
            if (response.status === 200) return response.json();
            else if (response.status === 400) throw new Error('Invalid category selection');
            else if (response.status === 429) throw new Error('Too many requests');
            else if (response.status === 500) throw new Error('Server error');
            else throw new Error(`Unexpected status: ${response.status}`);
        })
        .then(data => {
            images = data.images || [];
            currentImageIndex = 0;
            showNotification('success', translations.categories_loaded);
            loadImage();
            closeSettings();
        })
        .catch(error => {
            console.error('Error setting categories:', error.message);
            showNotification('error', error.message === 'Too many requests' ? 'Too many requests, please slow down' : error.message === 'Invalid category selection' ? translations.categories_load_failed : translations.server_error);
        });
    });

    cancelBtn.addEventListener('click', closeSettings);
    settingsModal.addEventListener('click', (e) => {
        if (e.target === settingsModal) closeSettings();
    });
}

// Close settings modal
function closeSettings() {
    settingsModal.style.display = 'none';
}

// Event listener for settings button
document.getElementById('settings-btn').addEventListener('click', openSettings);
let currentSentenceIndex = 0;
let correctCount = 0;
let incorrectCount = 0;
let sentences = defaultSentences || [];
let selectedCategories = [];
let feedbackVisible = false;

const sentenceElement = document.getElementById('sentence');
const dropZone = document.getElementById('drop-zone');
const optionsContainer = document.getElementById('options');
const correctCountElement = document.getElementById('correct-count');
const incorrectCountElement = document.getElementById('incorrect-count');
const percentageElement = document.getElementById('percentage');
const progressCorrect = document.querySelector('.progress-correct');
const progressIncorrect = document.querySelector('.progress-incorrect');
const feedbackElement = document.getElementById('feedback');
const settingsModal = document.getElementById('settings-modal');

if (sentences.length > 0) {
    displaySentence();
} else {
    fetchSentencesFromServer();
}

function loadSentence() {
    if (currentSentenceIndex >= sentences.length || sentences.length === 0) {
        fetchSentencesFromServer();
    } else {
        displaySentence();
    }
}

function fetchSentencesFromServer() {
    const categories = selectedCategories.length > 0 ? selectedCategories : ['all'];
    fetch(window.location.pathname + '?api=true', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_sentences', categories })
    })
    .then(response => {
        if (response.status === 200) return response.json();
        else if (response.status === 400) throw new Error('Invalid request');
        else if (response.status === 429) throw new Error('Too many requests');
        else if (response.status === 500) throw new Error('Server error');
        else throw new Error(`Unexpected status: ${response.status}`);
    })
    .then(data => {
        sentences = data.sentences || [];
        currentSentenceIndex = 0;
        if (sentences.length > 0) {
            displaySentence();
        } else {
            showNotification('warning', 'No sentences available for selected categories');
        }
    })
    .catch(error => {
        console.error('Error fetching sentences:', error.message);
        showNotification('error', error.message === 'Too many requests' ? 'Too many requests, please slow down' : error.message === 'Server error' ? translations.server_error : error.message);
    });
}

function displaySentence() {
    const sentenceData = sentences[currentSentenceIndex];
    const sentenceElement = document.getElementById('sentence');
    const optionsContainer = document.getElementById('options');

    // Insert the drop zone directly into the sentence text
    const fullSentence = sentenceData.sentence.replace('*', '<span id="drop-zone" class="drop-zone">___</span>');
    sentenceElement.innerHTML = fullSentence;

    // Get the newly created drop zone element
    const dropZone = document.getElementById('drop-zone');
    dropZone.classList.remove('correct', 'incorrect');

    // Clear and populate options
    optionsContainer.innerHTML = '';
    const numOptions = Math.floor(Math.random() * 3) + 2; // Random number between 2 and 4
    const allOptions = [...sentenceData.options];
    const correctAnswer = sentenceData.correct_answer;
    const shuffledOptions = allOptions.sort(() => 0.5 - Math.random()).slice(0, numOptions - 1);
    shuffledOptions.push(correctAnswer);
    shuffledOptions.sort(() => 0.5 - Math.random());

    shuffledOptions.forEach(option => {
        const optionElement = document.createElement('div');
        optionElement.classList.add('option');
        optionElement.textContent = option;
        optionElement.draggable = true;
        optionElement.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', option);
        });
        optionsContainer.appendChild(optionElement);
    });

    if (!feedbackVisible) {
        feedbackElement.textContent = '';
    }

    // Re-attach drop zone event listeners
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        const droppedOption = e.dataTransfer.getData('text/plain');
        checkAnswer(droppedOption);
    });
}

function checkAnswer(userAnswer) {
    const correctAnswer = sentences[currentSentenceIndex].correct_answer;
    const isCorrect = userAnswer === correctAnswer;
    const dropZone = document.getElementById('drop-zone');

    dropZone.textContent = userAnswer;
    if (isCorrect) {
        correctCount++;
        dropZone.classList.add('correct');
        feedbackElement.textContent = '';
        feedbackVisible = false;
    } else {
        incorrectCount++;
        dropZone.classList.add('incorrect');
        feedbackElement.textContent = translations.incorrect_answer.replace('{correct}', correctAnswer);
        feedbackVisible = true;
    }

    updateProgress();
    currentSentenceIndex++;
    setTimeout(loadSentence, 1500);
}

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

function goBack() {
    window.history.back();
}

function openSettings() {
    settingsModal.innerHTML = `
        <div class="modal-content">
            <h2 class="modal-title">${translations.settings_title}</h2>
            <div class="settings-section">
                <h3>${translations.select_categories}</h3>
                ${sentenceCategories.map(cat => `
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
            sentences = data.sentences || [];
            currentSentenceIndex = 0;
            showNotification('success', translations.categories_loaded);
            loadSentence();
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

function closeSettings() {
    settingsModal.style.display = 'none';
}

document.getElementById('settings-btn').addEventListener('click', openSettings);
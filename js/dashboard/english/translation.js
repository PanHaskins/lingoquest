// Global variables for tracking game state
let currentWordIndex = 0; // Index of the current word in the vocabulary array
let correctCount = 0; // Number of correct answers
let incorrectCount = 0; // Number of incorrect answers
let caseSensitive = false; // Flag for case-sensitive answer checking
let feedbackVisible = false; // Flag to show/hide feedback
let vocabulary = defaultVocabulary || []; // Vocabulary array, defaults to PHP-provided data or empty
let customWords = []; // Array for user-defined custom words
let isCustomMode = false; // Flag indicating if custom vocabulary mode is active
let selectedCategories = []; // Array of selected category IDs

// DOM element references
const wordElement = document.getElementById('word');
const answerInput = document.getElementById('answer-input');
const correctCountElement = document.getElementById('correct-count');
const incorrectCountElement = document.getElementById('incorrect-count');
const percentageElement = document.getElementById('percentage');
const progressCorrect = document.querySelector('.progress-correct');
const progressIncorrect = document.querySelector('.progress-incorrect');
const feedbackElement = document.getElementById('feedback');
const instructionElement = document.getElementById('instruction');
const settingsModal = document.getElementById('settings-modal');

// Initialize vocabulary and display first word if available, otherwise fetch from server
if (vocabulary.length > 0) {
    displayWord();
} else {
    fetchVocabularyFromServer();
}

// Event listener for answer submission via Enter or Right Arrow key
answerInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' || e.key === 'ArrowRight') {
        e.preventDefault();
        checkAnswer();
    }
});

answerInput.focus();

// Load and display the next word based on current mode
function loadWord() {
    if (isCustomMode) {
        if (currentWordIndex >= vocabulary.length) {
            currentWordIndex = 0; // Reset index to loop custom vocabulary
        }
        displayWord();
    } else {
        if (currentWordIndex >= vocabulary.length || vocabulary.length === 0) {
            fetchVocabularyFromServer(); // Fetch new words if list is exhausted or empty
        } else {
            displayWord();
        }
    }
}

// Fetch vocabulary from server via POST request
function fetchVocabularyFromServer() {
    const categories = selectedCategories.length > 0 ? selectedCategories : ['all'];
    fetch(window.location.pathname + '?api=true', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_vocabulary', categories })
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
        vocabulary = data.vocabulary || [];
        currentWordIndex = 0;
        if (vocabulary.length > 0) {
            displayWord();
        } else {
            showNotification('warning', 'No vocabulary available for selected categories');
        }
    })
    .catch(error => {
        console.error('Error fetching vocabulary:', error.message);
        showNotification('error', error.message === 'Too many requests' ? 'Too many requests, please slow down' : error.message === 'Server error' ? translations.server_error : error.message);
    });
}

// Display a word for translation with random source/target direction
function displayWord() {
    const isSourceToTarget = Math.random() < 0.5;
    const sourceWords = vocabulary[currentWordIndex][languages.source];
    const targetWords = vocabulary[currentWordIndex][languages.target];

    // Randomly pick one word from the array
    const randomSourceWord = sourceWords[Math.floor(Math.random() * sourceWords.length)];
    const randomTargetWord = targetWords[Math.floor(Math.random() * targetWords.length)];

    if (isSourceToTarget) {
        wordElement.textContent = randomSourceWord;
        instructionElement.textContent = translations.translate_to_target.replace('{target}', languages.target.toUpperCase());
    } else {
        wordElement.textContent = randomTargetWord;
        instructionElement.textContent = translations.translate_to_source.replace('{source}', languages.source.toUpperCase());
    }
    wordElement.classList.remove('correct', 'incorrect');
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

    const correctAnswers = vocabulary[currentWordIndex][languages.target];
    const isSourceToTarget = instructionElement.textContent.includes(languages.target.toUpperCase());
    let isCorrect = false;

    if (isSourceToTarget) {
        isCorrect = correctAnswers.some(answer => answer.toLowerCase() === userAnswer.toLowerCase());
    } else {
        const sourceWords = vocabulary[currentWordIndex][languages.source];
        isCorrect = sourceWords.some(source => source.toLowerCase() === userAnswer.toLowerCase()); 
    }

    if (isCorrect) {
        correctCount++;
        wordElement.classList.remove('incorrect');
        wordElement.classList.add('correct');
        feedbackElement.textContent = '';
        feedbackVisible = false;
    } else {
        incorrectCount++;
        wordElement.classList.remove('correct');
        wordElement.classList.add('incorrect');
        // Pick a random correct answer for feedback
        const randomCorrectAnswer = isSourceToTarget 
            ? correctAnswers[Math.floor(Math.random() * correctAnswers.length)] 
            : vocabulary[currentWordIndex][languages.source][Math.floor(Math.random() * vocabulary[currentWordIndex][languages.source].length)];
        feedbackElement.textContent = translations.incorrect_answer.replace('{correct}', randomCorrectAnswer);
        feedbackVisible = true;
    }

    updateProgress();
    currentWordIndex++;
    setTimeout(loadWord, 500);
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

// Open settings modal and handle mode switching
function openSettings() {
    settingsModal.innerHTML = `
        <div class="modal-content">
            <h2 class="modal-title">${translations.settings_title}</h2>
            <div class="settings-section">
                <div class="mode-switch-container">
                    <label>${translations.vocabulary_mode}</label>
                    <label class="switch">
                        <input type="checkbox" id="vocab-mode" ${!isCustomMode ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                    <span class="mode-label">${isCustomMode ? translations.custom_mode : translations.list_mode}</span>
                </div>
            </div>
            <div class="settings-section" id="vocab-content">
                <!-- Dynamic content populated later -->
            </div>
            <div class="modal-buttons">
                <button class="btn btn-accept">${translations.yes}</button>
                <button class="btn btn-cancel">${translations.no}</button>
            </div>
        </div>
    `;
    settingsModal.style.display = 'flex';

    const modeSwitch = document.getElementById('vocab-mode');
    const vocabContent = document.getElementById('vocab-content');
    const acceptBtn = settingsModal.querySelector('.btn-accept');
    const cancelBtn = settingsModal.querySelector('.btn-cancel');

    // Update settings content based on selected mode
    function updateVocabContent() {
        if (modeSwitch.checked) {
            vocabContent.innerHTML = `
                <h3>${translations.select_categories}</h3>
                ${vocabularyCategories.map(cat => `
                    <div class="checkbox-group">
                        <input type="checkbox" name="category" value="${cat.id}" id="cat-${cat.id}" ${selectedCategories.includes(cat.id) ? 'checked' : ''}>
                        <label for="cat-${cat.id}">${cat.name}</label>
                    </div>
                `).join('')}
            `;
        } else {
            vocabContent.innerHTML = `
                <h3>${translations.custom_vocabulary}</h3>
                <table class="vocab-table">
                    <thead>
                        <tr>
                            <th>${languages.source.toUpperCase()}</th>
                            <th>${languages.target.toUpperCase()}</th>
                        </tr>
                    </thead>
                    <tbody id="custom-vocab-body">
                        ${customWords.length > 0 ? customWords.map(word => `
                            <tr>
                                <td><input type="text" class="custom-input" value="${word[languages.source].join(', ')}" placeholder="${translations.enter_word}"></td>
                                <td><input type="text" class="custom-input" value="${word[languages.target].join(', ')}" placeholder="${translations.enter_word}"></td>
                            </tr>
                        `).join('') : `
                            <tr>
                                <td><input type="text" class="custom-input" placeholder="${translations.enter_word}"></td>
                                <td><input type="text" class="custom-input" placeholder="${translations.enter_word}"></td>
                            </tr>
                        `}
                    </tbody>
                </table>
                <button class="btn btn-secondary" id="add-row">${translations.add_row}</button>
            `;
            document.getElementById('add-row').addEventListener('click', () => {
                const tbody = document.getElementById('custom-vocab-body');
                tbody.insertAdjacentHTML('beforeend', `
                    <tr>
                        <td><input type="text" class="custom-input" placeholder="${translations.enter_word}"></td>
                        <td><input type="text" class="custom-input" placeholder="${translations.enter_word}"></td>
                    </tr>
                `);
            });
        }
    }

    modeSwitch.addEventListener('change', () => {
        document.querySelector('.mode-label').textContent = modeSwitch.checked ? translations.list_mode : translations.custom_mode;
        updateVocabContent();
    });

    updateVocabContent();

    // Handle settings confirmation
    acceptBtn.addEventListener('click', () => {
        if (modeSwitch.checked) {
            selectedCategories = Array.from(vocabContent.querySelectorAll('input[name="category"]:checked'))
                .map(checkbox => checkbox.value);
            fetch(window.location.pathname + '?api=true', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'set_categories', categories: selectedCategories.length > 0 ? selectedCategories : ['all'] })
            })
            .then(response => {
                if (response.status === 200) {
                    return response.json();
                } else if (response.status === 400) {
                    throw new Error('Invalid category selection');
                } else if (response.status === 429) {
                    throw new Error('Too many requests');
                } else if (response.status === 500) {
                    throw new Error('Server error');
                } else {
                    throw new Error(`Unexpected status: ${response.status}`);
                }
            })
            .then(data => {
                isCustomMode = false;
                vocabulary = data.vocabulary || [];
                currentWordIndex = 0;
                showNotification('success', translations.categories_loaded);
                loadWord();
                closeSettings();
            })
            .catch(error => {
                console.error('Error setting categories:', error.message);
                showNotification('error', error.message === 'Too many requests' ? 'Too many requests, please slow down' : error.message === 'Invalid category selection' ? translations.categories_load_failed : translations.server_error);
            });
        } else {
            const rows = Array.from(vocabContent.querySelectorAll('#custom-vocab-body tr'));
            customWords = rows.map(row => {
                const sourceInput = row.querySelector('td:nth-child(1) input').value.trim();
                const targetInput = row.querySelector('td:nth-child(2) input').value.trim();

                // Split input by commas and trim whitespace, only if there's input
                const sourceWords = sourceInput ? sourceInput.split(',').map(word => word.trim()) : [];
                const targetWords = targetInput ? targetInput.split(',').map(word => word.trim()) : [];

                if (sourceWords.length > 0 && targetWords.length > 0) {
                    return { [languages.source]: sourceWords, [languages.target]: targetWords };
                }
                return null;
            }).filter(word => word);

            if (customWords.length) {
                isCustomMode = true;
                vocabulary = [...customWords];
                showNotification('success', translations.custom_words_added);
                currentWordIndex = 0;
                loadWord();
                closeSettings();
            } else {
                showNotification('warning', translations.no_custom_words);
            }
        }
    });

    cancelBtn.addEventListener('click', closeSettings);

    settingsModal.addEventListener('click', (e) => {
        if (e.target === settingsModal) {
            closeSettings();
        }
    });
}

// Close settings modal
function closeSettings() {
    settingsModal.style.display = 'none';
}

// Event listener for settings button
document.getElementById('settings-btn').addEventListener('click', openSettings);
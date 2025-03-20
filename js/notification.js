function showNotification(type, message) {
    const container = document.getElementById('notificationContainer');
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    const icon = document.createElement('i');
    icon.className = 'fas';
    switch(type) {
        case 'success':
            icon.className += ' fa-check-circle';
            break;
        case 'info':
            icon.className += ' fa-info-circle';
            break;
        case 'warning':
            icon.className += ' fa-exclamation-triangle';
            break;
        case 'error':
            icon.className += ' fa-exclamation-circle';
            break;
    }
    
    const text = document.createElement('span');
    text.textContent = message;
    
    notification.appendChild(icon);
    notification.appendChild(text);
    container.appendChild(notification);
    
    // Force reflow to trigger animation
    notification.offsetHeight;
    notification.classList.add('show');
    
    // Remove notification after animation
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => {
            container.removeChild(notification);
        }, 300);
    }, 5000);
}

// Universal modal
function showModal(title, message, onAccept, onCancel) {
    // Check if modal already exists and remove it
    let modal = document.getElementById('universal-modal');
    if (modal) {
        modal.remove();
    }

    // Create new modal
    modal = document.createElement('div');
    modal.className = 'modal';
    modal.id = 'universal-modal';
    modal.style.display = 'none';

    const modalContent = document.createElement('div');
    modalContent.className = 'modal-content';

    const modalTitle = document.createElement('h2');
    modalTitle.className = 'modal-title';
    modalTitle.textContent = title;

    const modalMessage = document.createElement('p');
    modalMessage.className = 'modal-message';
    modalMessage.textContent = message;

    const modalButtons = document.createElement('div');
    modalButtons.className = 'modal-buttons';

    const acceptBtn = document.createElement('button');
    acceptBtn.className = 'btn btn-accept';
    acceptBtn.textContent = translations.yes;
    acceptBtn.addEventListener('click', function(e) {
        e.preventDefault();
        modal.style.display = 'none';
        if (onAccept) onAccept();
    });

    const cancelBtn = document.createElement('button');
    cancelBtn.className = 'btn btn-cancel';
    cancelBtn.textContent = translations.no;
    cancelBtn.addEventListener('click', function(e) {
        e.preventDefault();
        modal.style.display = 'none';
        if (onCancel) onCancel();
    });

    modalButtons.appendChild(acceptBtn);
    modalButtons.appendChild(cancelBtn);

    modalContent.appendChild(modalTitle);
    modalContent.appendChild(modalMessage);
    modalContent.appendChild(modalButtons);
    modal.appendChild(modalContent);

    // Add modal to body
    document.body.appendChild(modal);

    // Show modal
    modal.style.display = 'flex';

    // Close modal when clicking outside
    document.addEventListener('click', function handleOutsideClick(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
            if (onCancel) onCancel();
            document.removeEventListener('click', handleOutsideClick);
        }
    });
}
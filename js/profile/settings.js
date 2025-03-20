/* Profile Settings Initialization */
document.addEventListener('DOMContentLoaded', function() {
    // Select necessary DOM elements
    const uploadDropzone = document.getElementById('upload-dropzone');
    const browseFileBtn = document.getElementById('browse-file-btn');
    const avatarUpload = document.getElementById('avatar-upload');
    const form = document.getElementById('edit-profile');
    const progressBar = document.getElementById('progress-bar');
    const uploadProgress = document.getElementById('upload-progress');
    const uploadSuccess = document.getElementById('upload-success');

    // Allowed file formats
    const allowedFormats = ['image/png', 'image/webp', 'image/jpeg', 'image/svg+xml'];
    const allowedExtensions = ['.png', '.webp', '.jpg', '.jpeg', '.svg'];

    if (uploadDropzone && browseFileBtn && avatarUpload && form && progressBar && uploadProgress) {
        // Trigger file input click via browse button
        browseFileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            avatarUpload.click();
        });

        // Handle file selection and initiate upload
        function handleFileSelection(event) {
            const files = event.target.files || event.dataTransfer?.files;
            if (!files || files.length === 0) return;

            const file = files[0];

            // Validate file size (250KB limit)
            if (file.size > 250 * 1024) {
                showNotification('warning', translations.file_size_limit || 'File size exceeds 250 KB limit.');
                return;
            }

            // Validate file format (MIME type and extension)
            const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
            if (!allowedFormats.includes(file.type) || !allowedExtensions.includes(fileExtension)) {
                showNotification('warning', translations.upload_image_only || 'Only PNG, WEBP, JPG, JPEG, and SVG files are allowed.');
                return;
            }

            // Check image dimensions (not applicable for SVG)
            if (file.type !== 'image/svg+xml') {
                const img = new Image();
                img.onload = function() {
                    if (img.width > 256 || img.height > 256) {
                        showNotification('warning', translations.something_wrong || 'Image dimensions exceed 256x256 pixels.');
                    } else {
                        const formData = new FormData(form);
                        formData.append('avatar', file);
                        uploadFile(formData);
                    }
                };
                img.onerror = function() {
                    showNotification('error', translations.something_wrong || 'Failed to load image for validation.');
                };
                img.src = URL.createObjectURL(file);
            } else {
                const formData = new FormData(form);
                formData.append('avatar', file);
                uploadFile(formData);
            }
        }

        // Upload file with progress tracking
        function uploadFile(formData) {
            const xhr = new XMLHttpRequest();
        
            uploadProgress.style.display = 'block';
            progressBar.style.width = '0%';
            uploadSuccess.style.display = 'none';
        
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressBar.style.width = percentComplete + '%';
                }
            });
        
            xhr.addEventListener('load', function() {
                const status = xhr.status;
                const uploadStatus = xhr.getResponseHeader('X-Upload-Status');
                const uploadMessage = decodeURIComponent(xhr.getResponseHeader('X-Upload-Message') || '');
        
                console.log('Status:', status, 'Upload-Status:', uploadStatus, 'Message:', uploadMessage);
        
                if (status === 200 && uploadStatus === 'success') {
                    uploadSuccess.style.display = 'block';
                    showNotification('success', uploadMessage || translations.avatar_upload_success || 'Avatar uploaded successfully!');
                    setTimeout(() => {
                        window.location.reload();
                        const avatarElements = document.querySelectorAll('.avatar');
                        if (avatarElements.length > 0) {
                            const userId = '<?php echo User::getCurrentUser()->getId(); ?>';
                            const timestamp = new Date().getTime();
                            const newAvatarUrl = `/upload/profile/${userId}.webp?t=${timestamp}`;

                            avatarElements.forEach(element => {
                                element.src = newAvatarUrl;
                            });
                        }
                    }, 3000);
                } else {
                    showNotification('error', uploadMessage || translations.avatar_upload_failed || 'Upload failed.');
                }
                setTimeout(() => {
                    uploadProgress.style.display = 'none';
                }, 1000);
            });
        
            xhr.addEventListener('error', function() {
                showNotification('error', translations.avatar_upload_error || 'An error occurred during upload.');
                uploadProgress.style.display = 'none';
            });
        
            xhr.open('POST', form.action || '/profile/settings', true);
            xhr.send(formData);
        }

        // Drag-and-drop event handlers
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadDropzone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
            });
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadDropzone.addEventListener(eventName, function() {
                uploadDropzone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadDropzone.addEventListener(eventName, function() {
                uploadDropzone.classList.remove('dragover');
            });
        });

        uploadDropzone.addEventListener('drop', function(e) {
            avatarUpload.files = e.dataTransfer.files;
            handleFileSelection(e);
        });

        avatarUpload.addEventListener('change', handleFileSelection);
    }

    // Profile deletion handler
    const deleteProfileBtn = document.getElementById('delete-profile-btn');
    if (deleteProfileBtn) {
        deleteProfileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showModal(
                translations.confirmation_title,
                translations.confirmation_message,
                function() {
                    const userId = document.querySelector('.profile-header h1')?.textContent.match(/#(\d+)/)?.[1];
                    if (!userId) {
                        showNotification('error', translations.something_wrong || 'Cannot determine user ID');
                        return;
                    }
                    console.error('Sending delete request for user ID:', userId);
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'user_id=' + userId
                    })
                    .then(response => {
                        if (response.redirected) {
                            window.location.href = response.url;
                        } else {
                            showNotification('error', translations.something_wrong);
                        }
                    })
                    .catch(error => {
                        showNotification('error', translations.something_wrong);
                    });
                },
                function() {
                    // No action on cancel
                }
            );
        });
    }
});
'use strict';

const notificationContainer = document.querySelector('.notification-container');

notificationContainer.addEventListener('click', (e) => {
    if (e.target.classList.contains('delete')) {
        let notification = document.querySelector('.notification');
        notification.style.display = 'none';
    }
});

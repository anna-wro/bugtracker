const notificationContainer = document.querySelector('.notification-container');

notificationContainer.addEventListener('click', (e) => {
    if (e.target.classList.contains('delete')) {
        let notification = document.querySelector('.notification');
        notification.style.display = 'none';
    }
});

$('#bug_type_start_date').dateDropper();
$('#bug_type_end_date').dateDropper();
$('#project_type_start_date').dateDropper();
$('#project_type_end_date').dateDropper();
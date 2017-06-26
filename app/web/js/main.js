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

let projectDiv = $('#bug_type_expected_result').parent().next()[0];

if (projectDiv) {
    let typeDiv = projectDiv.nextElementSibling;
    let priorityDiv = typeDiv.nextElementSibling;
    let statusDiv = priorityDiv.nextElementSibling;

    projectDiv.classList.add('is-1-2', 'select-project');
    typeDiv.classList.add('is-1-2', 'select-type');
    priorityDiv.classList.add('is-2-3', 'select-priority');
    statusDiv.classList.add('is-1-3', 'select-status');
}

let readonlyInputs = document.querySelectorAll('input[readonly="readonly"]');
if (readonlyInputs) {
    for (let input of readonlyInputs) {
        input.disabled = true;
    }
}

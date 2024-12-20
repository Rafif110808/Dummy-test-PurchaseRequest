// progress bar (nilai value & max)
const progress = document.querySelectorAll('.progress-done');
const data = document.querySelectorAll('.progress');
const maxValue = document.querySelectorAll('.progress');
const progressLabel = document.querySelectorAll('.progress-label');
let increment = 0;
let value = 0;
let max = 0;

progress.forEach((e) => {
    value = data[increment].getAttribute('data-value');
    max = maxValue[increment].getAttribute('data-max');
    progress[increment].style.width = `${(value / max) * 100}%`;
    increment++
})

// dropdown halaman kanban
const dropdown = document.querySelectorAll('.dropdown');
dropdown.forEach((e) => {
    e.addEventListener('click', () => {
        if(e.classList.contains('active')) {
            e.classList.remove('active')
        } else {
            e.classList.add('active')
        }
    })
})
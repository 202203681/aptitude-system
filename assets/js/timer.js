let timerInterval;
let timeRemaining = 600; // 10 minutes in seconds

function startTimer(displayElement, onComplete) {
    timerInterval = setInterval(() => {
        timeRemaining--;
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        
        if (displayElement) {
            displayElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        
        if (timeRemaining <= 0) {
            clearInterval(timerInterval);
            if (onComplete) onComplete();
        }
    }, 1000);
}

function stopTimer() {
    if (timerInterval) clearInterval(timerInterval);
}

function resetTimer(seconds = 600) {
    timeRemaining = seconds;
}
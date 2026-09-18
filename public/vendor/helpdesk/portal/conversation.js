    // Auto-scroll thread to bottom
    const thread = document.getElementById('messageThread');
    if (thread) {
        thread.scrollTop = thread.scrollHeight;
    }

    // Character counter
    const input = document.getElementById('messageInput');
    const counter = document.getElementById('charCount');
    if (input && counter) {
        input.addEventListener('input', function () {
            counter.textContent = this.value.length + ' / 5000';
        });
    }

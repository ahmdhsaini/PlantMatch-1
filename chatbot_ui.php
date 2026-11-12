<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    /* Fixed Launcher Bubble - Using your botanical green colour */
    #florabot-launcher {
        position: fixed;
        bottom: 25px;
        right: 25px;
        width: 60px;
        height: 60px;
        background-color: #198754;
        color: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(25, 135, 84, 0.35);
        z-index: 99999;
        transition: transform 0.2s ease;
    }
    #florabot-launcher:hover {
        transform: scale(1.08);
    }

    /* Pop-up Chat window box container frame */
    #florabot-window {
        position: fixed;
        bottom: 95px;
        right: 25px;
        width: 360px;
        height: 500px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        display: none; /* Initially Hidden */
        flex-direction: column;
        overflow: hidden;
        z-index: 99999;
        font-family: system-ui, -apple-system, sans-serif;
    }

    /* Chat header banner block styling - Switched back to Green */
    .florabot-header {
        background-color: #198754;
        color: #ffffff;
        padding: 15px;
        font-weight: 700;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Conversation history dynamic timeline view layout */
    .florabot-body {
        flex: 1;
        padding: 15px;
        overflow-y: auto;
        background-color: #f8fafc;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    /* Chat item text bubble structures */
    .msg-bubble {
        max-width: 80%;
        padding: 10px 14px;
        border-radius: 14px;
        font-size: 0.9rem;
        line-height: 1.4;
    }
    .msg-user {
        background-color: #e2f0d9;
        color: #2d3748;
        align-self: flex-end;
        border-bottom-right-radius: 2px;
    }
    .msg-bot {
        background-color: #ffffff;
        color: #2d3748;
        align-self: flex-start;
        border-bottom-left-radius: 2px;
        border: 1px solid #e2e8f0;
    }

    /* Bottom input utility bar using Bootstrap Flex utilities */
    .florabot-footer {
        padding: 10px;
        border-top: 1px solid #edf2f7;
        background: #ffffff;
    }
    .florabot-input {
        border: 1px solid #cbd5e1;
        border-radius: 20px;
        padding: 8px 16px;
        font-size: 0.9rem;
        outline: none;
    }
    
    /* Dedicated Send Button Style using your botanical green theme */
    .florabot-send-btn {
        background-color: #198754;
        color: white;
        border: none;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    .florabot-send-btn:hover {
        background-color: #157347;
    }
</style>

<div id="florabot-launcher" onclick="toggleFloraBot()">
    <i class="bi bi-robot"></i>
</div>

<div id="florabot-window">
    <div class="florabot-header">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-robot"></i> FloraBot Assistant
        </div>
        <span style="cursor:pointer; font-size:1.2rem;" onclick="toggleFloraBot()">&times;</span>
    </div>
    
    <div class="florabot-body" id="florabot-chat-logs">
        <div class="msg-bubble msg-bot">
            Hello! I'm FloraBot. 🤖 I have access to your plant matches. How can I assist you today?
        </div>
    </div>
    
    <div class="florabot-footer">
        <div class="input-group">
            <input type="text" id="florabot-text-field" class="form-control florabot-input" placeholder="Ask about your plant matches..." onkeypress="handleBotKeyPress(event)">
            <button class="btn florabot-send-btn ms-2" type="button" onclick="sendFloraBotMessage()">
                <i class="bi bi-send-fill"></i>
            </button>
        </div>
    </div>
</div>

<script>
function toggleFloraBot() {
    const win = document.getElementById('florabot-window');
    if(win.style.display === 'none' || win.style.display === '') {
        win.style.display = 'flex';
        const logs = document.getElementById('florabot-chat-logs');
        logs.scrollTop = logs.scrollHeight;
    } else {
        win.style.display = 'none';
    }
}

function handleBotKeyPress(event) {
    if (event.key === 'Enter') {
        sendFloraBotMessage();
    }
}

function sendFloraBotMessage() {
    const inputField = document.getElementById('florabot-text-field');
    const msgText = inputField.value.trim();
    if (!msgText) return;

    const logsContainer = document.getElementById('florabot-chat-logs');

    // Append User Message Bubble
    const userBubble = document.createElement('div');
    userBubble.className = 'msg-bubble msg-user';
    userBubble.textContent = msgText;
    logsContainer.appendChild(userBubble);
    
    inputField.value = '';
    logsContainer.scrollTop = logsContainer.scrollHeight;

    // Append Loading Indicator Bubble
    const loadingBubble = document.createElement('div');
    loadingBubble.className = 'msg-bubble msg-bot';
    loadingBubble.id = 'florabot-loading-indicator';
    loadingBubble.innerHTML = '<span style="font-style:italic; color:#718096;">FloraBot is typing...</span>';
    logsContainer.appendChild(loadingBubble);
    logsContainer.scrollTop = logsContainer.scrollHeight;

    // Fetch call to background script
    fetch('chatbot_backend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msgText })
    })
    .then(response => response.json())
    .then(data => {
        const loader = document.getElementById('florabot-loading-indicator');
        if(loader) loader.remove();

        const botBubble = document.createElement('div');
        botBubble.className = 'msg-bubble msg-bot';

        if (data.reply) {
            botBubble.textContent = data.reply;
        } else if (data.error) {
            botBubble.innerHTML = `<span style="color:#dc3545; font-weight:bold;">Error: ${data.error}</span>`;
        } else {
            botBubble.textContent = "Sorry, I encountered an error.";
        }

        logsContainer.appendChild(botBubble);
        logsContainer.scrollTop = logsContainer.scrollHeight;
    })
    .catch(error => {
        const loader = document.getElementById('florabot-loading-indicator');
        if(loader) loader.remove();

        const errorBubble = document.createElement('div');
        errorBubble.className = 'msg-bubble msg-bot';
        errorBubble.innerHTML = '<span style="color:#dc3545; font-weight:bold;">Network connectivity failed.</span>';
        logsContainer.appendChild(errorBubble);
        logsContainer.scrollTop = logsContainer.scrollHeight;
        console.error('Error:', error);
    });
}
</script>
<?php
$_floraPlantMap = [];
if (isset($conn)) {
    $_floraRes = mysqli_query($conn, "SELECT ID, Common_Name FROM plantmatch_ultimate_complete LIMIT 600");
    if ($_floraRes) {
        while ($_floraRow = mysqli_fetch_assoc($_floraRes)) {
            foreach (explode(',', $_floraRow['Common_Name'] ?? '') as $_n) {
                $_n = trim($_n);
                if ($_n !== '') $_floraPlantMap[$_n] = $_floraRow['ID'];
            }
        }
    }
}
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/9.1.6/marked.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.6/purify.min.js"></script>

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
    .msg-bot h1, .msg-bot h2, .msg-bot h3 {
        font-size: 0.95rem;
        font-weight: 700;
        margin: 8px 0 4px;
        color: #198754;
    }
    .msg-bot p { margin: 0 0 6px; }
    .msg-bot ul, .msg-bot ol { margin: 4px 0 6px; padding-left: 18px; }
    .msg-bot li { margin-bottom: 2px; }
    .msg-bot table {
        border-collapse: collapse;
        width: 100%;
        font-size: 0.8rem;
        margin: 6px 0;
    }
    .msg-bot th {
        background-color: #198754;
        color: #fff;
        padding: 5px 8px;
        text-align: left;
    }
    .msg-bot td {
        padding: 4px 8px;
        border-bottom: 1px solid #e2e8f0;
    }
    .msg-bot tr:nth-child(even) td { background-color: #f0faf4; }
    .msg-bot strong { color: #155724; }

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
        <div class="d-flex align-items-center gap-2">
            <span onclick="clearFloraBotHistory()" title="Clear chat" style="cursor:pointer;font-size:0.75rem;opacity:0.8;border:1px solid rgba(255,255,255,0.5);border-radius:4px;padding:1px 6px;">Clear</span>
            <span style="cursor:pointer; font-size:1.2rem;" onclick="toggleFloraBot()">&times;</span>
        </div>
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
const FLORA_PLANT_MAP = <?php echo json_encode($_floraPlantMap); ?>;
const FLORA_STORAGE_KEY = 'florabot_history_<?php echo isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'guest'; ?>';

function findPlantId(name) {
    const lower = name.toLowerCase().trim();
    // 1. Exact match
    for (const [k, v] of Object.entries(FLORA_PLANT_MAP)) {
        if (k.toLowerCase() === lower) return v;
    }
    // 2. DB key contained in bold text (e.g. bold="Rose (Rosa sp.)" key="Rose")
    for (const [k, v] of Object.entries(FLORA_PLANT_MAP)) {
        if (lower.includes(k.toLowerCase())) return v;
    }
    // 3. Bold text contained in DB key (e.g. bold="Sunflower" key="Common Sunflower")
    for (const [k, v] of Object.entries(FLORA_PLANT_MAP)) {
        if (k.toLowerCase().includes(lower) && lower.length > 3) return v;
    }
    return null;
}

function renderBotBubble(markdown) {
    const bubble = document.createElement('div');
    bubble.className = 'msg-bubble msg-bot';
    bubble.innerHTML = DOMPurify.sanitize(marked.parse(markdown));
    bubble.querySelectorAll('strong').forEach(el => {
        const id = findPlantId(el.textContent.trim());
        if (id) {
            const a = document.createElement('a');
            a.href = 'user_plant_details.php?id=' + encodeURIComponent(id);
            a.style.cssText = 'color:#198754;text-decoration:none;font-weight:bold;cursor:pointer;';
            a.title = 'View plant details';
            a.innerHTML = el.innerHTML;
            el.replaceWith(a);
        }
    });
    return bubble;
}

function saveChatHistory() {
    try {
        const logs = document.getElementById('florabot-chat-logs');
        const msgs = [];
        logs.querySelectorAll('.msg-bubble[data-role]').forEach(el => {
            msgs.push({ role: el.dataset.role, text: el.dataset.text });
        });
        localStorage.setItem(FLORA_STORAGE_KEY, JSON.stringify(msgs));
    } catch(e) {}
}

function restoreChatHistory() {
    try {
        const stored = localStorage.getItem(FLORA_STORAGE_KEY);
        if (!stored) return;
        const msgs = JSON.parse(stored);
        const logs = document.getElementById('florabot-chat-logs');
        msgs.forEach(m => {
            if (m.role === 'user') {
                const b = document.createElement('div');
                b.className = 'msg-bubble msg-user';
                b.dataset.role = 'user';
                b.dataset.text = m.text;
                b.textContent = m.text;
                logs.appendChild(b);
            } else {
                const b = renderBotBubble(m.text);
                b.dataset.role = 'bot';
                b.dataset.text = m.text;
                logs.appendChild(b);
            }
        });
        logs.scrollTop = logs.scrollHeight;
    } catch(e) {}
}

function clearFloraBotHistory() {
    try { localStorage.removeItem(FLORA_STORAGE_KEY); } catch(e) {}
    const logs = document.getElementById('florabot-chat-logs');
    logs.querySelectorAll('.msg-bubble[data-role]').forEach(el => el.remove());
}

function toggleFloraBot() {
    const win = document.getElementById('florabot-window');
    if (win.style.display === 'none' || win.style.display === '') {
        win.style.display = 'flex';
        const logs = document.getElementById('florabot-chat-logs');
        logs.scrollTop = logs.scrollHeight;
    } else {
        win.style.display = 'none';
    }
}

function handleBotKeyPress(event) {
    if (event.key === 'Enter') sendFloraBotMessage();
}

function sendFloraBotMessage() {
    const inputField = document.getElementById('florabot-text-field');
    const msgText = inputField.value.trim();
    if (!msgText) return;

    const logs = document.getElementById('florabot-chat-logs');

    const userBubble = document.createElement('div');
    userBubble.className = 'msg-bubble msg-user';
    userBubble.dataset.role = 'user';
    userBubble.dataset.text = msgText;
    userBubble.textContent = msgText;
    logs.appendChild(userBubble);

    inputField.value = '';
    logs.scrollTop = logs.scrollHeight;

    const loadingBubble = document.createElement('div');
    loadingBubble.className = 'msg-bubble msg-bot';
    loadingBubble.id = 'florabot-loading-indicator';
    loadingBubble.innerHTML = '<span style="font-style:italic;color:#718096;">FloraBot is typing...</span>';
    logs.appendChild(loadingBubble);
    logs.scrollTop = logs.scrollHeight;

    fetch('chatbot_backend.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msgText })
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('florabot-loading-indicator')?.remove();

        if (data.reply) {
            const botBubble = renderBotBubble(data.reply);
            botBubble.dataset.role = 'bot';
            botBubble.dataset.text = data.reply;
            logs.appendChild(botBubble);
        } else {
            const errBubble = document.createElement('div');
            errBubble.className = 'msg-bubble msg-bot';
            errBubble.innerHTML = `<span style="color:#dc3545;font-weight:bold;">Error: ${data.error ?? 'Unknown error'}</span>`;
            logs.appendChild(errBubble);
        }

        logs.scrollTop = logs.scrollHeight;
        saveChatHistory();
    })
    .catch(() => {
        document.getElementById('florabot-loading-indicator')?.remove();
        const errBubble = document.createElement('div');
        errBubble.className = 'msg-bubble msg-bot';
        errBubble.innerHTML = '<span style="color:#dc3545;font-weight:bold;">Network connectivity failed.</span>';
        logs.appendChild(errBubble);
        logs.scrollTop = logs.scrollHeight;
    });
}

document.addEventListener('DOMContentLoaded', restoreChatHistory);
</script>
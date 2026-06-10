<?php
$page_title = 'Staff Chat';
include dirname(__DIR__) . '/includes/admin_header.php';
require_once dirname(__DIR__) . '/db.php';
?>

<style>
.chat-wrap { display:flex; flex-direction:column; height:calc(100vh - 180px); }
.chat-messages { flex:1; overflow-y:auto; padding:1rem; display:flex; flex-direction:column; gap:.6rem; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px 8px 0 0; }
.chat-msg { max-width:70%; }
.chat-msg.mine { align-self:flex-end; }
.chat-msg.theirs { align-self:flex-start; }
.chat-bubble { padding:.55rem .9rem; border-radius:12px; font-size:.9rem; line-height:1.4; word-break:break-word; }
.mine .chat-bubble { background:#6366f1; color:#fff; border-bottom-right-radius:3px; }
.theirs .chat-bubble { background:#fff; color:#1e293b; border:1px solid #e5e7eb; border-bottom-left-radius:3px; }
.chat-meta { font-size:.72rem; color:#94a3b8; margin-top:.2rem; }
.mine .chat-meta { text-align:right; }
.role-badge { display:inline-block; font-size:.65rem; font-weight:700; padding:.05rem .35rem; border-radius:4px; margin-right:.3rem; }
.role-SUPERADMIN { background:#ede9fe; color:#7c3aed; }
.role-ADMIN { background:#dbeafe; color:#1d4ed8; }
.role-MANAGER { background:#fef9c3; color:#92400e; }
.chat-form { display:flex; gap:.5rem; border:1px solid #e5e7eb; border-top:none; background:#fff; padding:.75rem; border-radius:0 0 8px 8px; }
.chat-form input { flex:1; padding:.55rem .75rem; border:1px solid #d1d5db; border-radius:6px; font-size:.9rem; }
.chat-form input:focus { outline:none; border-color:#6366f1; }
.chat-form button { padding:.55rem 1.25rem; background:#6366f1; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:.9rem; }
.chat-form button:hover { background:#4f46e5; }
.chat-status { font-size:.75rem; color:#94a3b8; padding:.3rem .5rem; text-align:center; }
</style>

<div class="chat-wrap">
    <div class="chat-messages" id="chatMessages">
        <div class="chat-status">Loading messages…</div>
    </div>
    <form class="chat-form" id="chatForm">
        <input type="text" id="chatInput" placeholder="Type a message… (Enter to send)" maxlength="1000" autocomplete="off">
        <button type="submit">Send</button>
    </form>
</div>

<script>
const ME_ID   = '<?= htmlspecialchars($current_user['id']) ?>';
const ME_NAME = '<?= htmlspecialchars($current_user['name']) ?>';
let lastTimestamp = null;
let isAtBottom    = true;

const box   = document.getElementById('chatMessages');
const form  = document.getElementById('chatForm');
const input = document.getElementById('chatInput');

box.addEventListener('scroll', () => {
    isAtBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 40;
});

function roleBadge(role) {
    return `<span class="role-badge role-${role}">${role}</span>`;
}

function addMessage(msg) {
    const mine = msg.senderId === ME_ID;
    const div  = document.createElement('div');
    div.className = 'chat-msg ' + (mine ? 'mine' : 'theirs');
    div.dataset.id = msg.id;
    const time = new Date(msg.createdAt + 'Z').toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    div.innerHTML = `
      <div class="chat-bubble">${escHtml(msg.message)}</div>
      <div class="chat-meta">${mine ? '' : roleBadge(msg.senderRole) + escHtml(msg.senderName) + ' &middot; '}${time}</div>
    `;
    box.appendChild(div);
}

function escHtml(s) {
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

async function fetchMessages(initial) {
    const url = '<?= APP_URL ?>/api/staff/chat.php' + (lastTimestamp ? '?since=' + encodeURIComponent(lastTimestamp) : '');
    try {
        const r = await fetch(url, {credentials:'include'});
        const d = await r.json();
        if (!d.success) return;

        if (initial) {
            box.innerHTML = '';
            if (d.data.messages.length === 0) {
                box.innerHTML = '<div class="chat-status">No messages yet. Say hi!</div>';
            }
        }

        d.data.messages.forEach(msg => {
            if (!document.querySelector(`[data-id="${msg.id}"]`)) {
                // remove "no messages" placeholder
                const placeholder = box.querySelector('.chat-status');
                if (placeholder) placeholder.remove();
                addMessage(msg);
                lastTimestamp = msg.createdAt;
            }
        });

        if (isAtBottom || initial) {
            box.scrollTop = box.scrollHeight;
        }
    } catch(e) {}
}

form.addEventListener('submit', async e => {
    e.preventDefault();
    const msg = input.value.trim();
    if (!msg) return;
    input.value = '';
    try {
        const r = await fetch('<?= APP_URL ?>/api/staff/chat.php', {
            method: 'POST',
            credentials: 'include',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({message: msg})
        });
        const d = await r.json();
        if (d.success) {
            if (!document.querySelector(`[data-id="${d.data.id}"]`)) {
                const placeholder = box.querySelector('.chat-status');
                if (placeholder) placeholder.remove();
                addMessage(d.data);
                lastTimestamp = d.data.createdAt;
            }
            isAtBottom = true;
            box.scrollTop = box.scrollHeight;
        }
    } catch(e) {}
});

input.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.dispatchEvent(new Event('submit')); }
});

fetchMessages(true);
setInterval(() => fetchMessages(false), 5000);
</script>

<?php include dirname(__DIR__) . '/includes/admin_footer.php'; ?>

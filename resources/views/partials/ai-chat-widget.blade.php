<!-- AI CHAT WIDGET -->
<style>
    #aiChatPanel {
        transition: opacity .2s ease, transform .2s ease;
    }
    #aiChatPanel.hidden {
        opacity: 0;
        transform: translateY(12px) scale(.97);
        pointer-events: none;
    }
    #aiChatPanel.open {
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: all;
    }
    .ai-msg-user {
        background: #2563eb;
        color: white;
        border-radius: 18px 18px 4px 18px;
        align-self: flex-end;
    }
    .ai-msg-bot {
        background: #f1f5f9;
        color: #1e293b;
        border-radius: 18px 18px 18px 4px;
        align-self: flex-start;
    }
    #aiChatMessages::-webkit-scrollbar { width: 4px; }
    #aiChatMessages::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>

<!-- Floating bubble -->
<button
    id="aiChatBubble"
    onclick="toggleAiChat()"
    class="no-print fixed bottom-6 right-6 z-[9999] w-14 h-14 rounded-full bg-violet-600 hover:bg-violet-700 shadow-xl flex items-center justify-center transition"
    title="ANIRA - KPI AI Assistant"
>
    <svg id="aiChatIconOpen" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2v10z"/>
    </svg>
    <svg id="aiChatIconClose" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
    </svg>
    <span id="aiUnreadDot" class="hidden absolute top-1 right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-white"></span>
</button>

<!-- Chat panel -->
<div
    id="aiChatPanel"
    class="no-print fixed bottom-24 right-6 z-[9998] w-80 sm:w-96 bg-white rounded-3xl shadow-2xl border border-slate-100 flex flex-col hidden"
    style="height: 480px;"
>
    <!-- Header -->
    <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-100 rounded-t-3xl bg-violet-600">
        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2a1 1 0 0 1 .993.883L13 3v1.07a8.002 8.002 0 0 1 6.93 6.93H21a1 1 0 0 1 .117 1.993L21 13h-1.07a8.002 8.002 0 0 1-6.93 6.93V21a1 1 0 0 1-1.993.117L11 21v-1.07a8.002 8.002 0 0 1-6.93-6.93H3a1 1 0 0 1-.117-1.993L3 11h1.07a8.002 8.002 0 0 1 6.93-6.93V3a1 1 0 0 1 1-1z"/>
            </svg>
        </div>
        <div>
            <p class="text-white font-black text-sm">ANIRA</p>
            <p class="text-violet-200 text-xs">KPI AI Assistant</p>
        </div>
        <button onclick="toggleAiChat()" class="ml-auto text-white/70 hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Messages -->
    <div id="aiChatMessages" class="flex-1 overflow-y-auto px-4 py-4 flex flex-col gap-3">
        <!-- Initial greeting -->
        <div class="ai-msg-bot text-sm px-4 py-3 max-w-[85%]">
            Hi! I'm ANIRA, your KPI coach. I'm here to help you build strong, high-scoring KPIs — not by writing them for you, but by asking the right questions so you can think it through yourself. Want to build a KPI, or do you have a question about the system?
        </div>

        <!-- Quick prompts -->
        <div id="aiQuickPrompts" class="flex flex-col gap-2 mt-1">
            <p class="text-xs text-slate-400 font-medium">Quick questions:</p>
            <button onclick="aiQuickSend('Help me build a KPI')" class="text-left text-xs px-3 py-2 rounded-xl border border-violet-200 text-violet-700 hover:bg-violet-50 transition">Help me build a KPI</button>
            <button onclick="aiQuickSend('How do I score high on my KPI?')" class="text-left text-xs px-3 py-2 rounded-xl border border-violet-200 text-violet-700 hover:bg-violet-50 transition">How do I score high on my KPI?</button>
            <button onclick="aiQuickSend('How does the approval process work?')" class="text-left text-xs px-3 py-2 rounded-xl border border-violet-200 text-violet-700 hover:bg-violet-50 transition">How does the approval process work?</button>
        </div>
    </div>

    <!-- Input -->
    <div class="px-4 py-3 border-t border-slate-100">
        <div class="flex items-center gap-2 bg-slate-50 rounded-2xl px-4 py-2">
            <input
                id="aiChatInput"
                type="text"
                placeholder="Ask something..."
                class="flex-1 bg-transparent text-sm outline-none text-slate-700 placeholder-slate-400"
                onkeydown="if(event.key==='Enter' && !event.shiftKey){ event.preventDefault(); aiSendMessage(); }"
                maxlength="500"
            >
            <button
                id="aiChatSendBtn"
                onclick="aiSendMessage()"
                class="w-8 h-8 rounded-xl bg-violet-600 hover:bg-violet-700 flex items-center justify-center transition flex-shrink-0"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const CSRF = '{{ csrf_token() }}';
    const chatUrl = '{{ route("ai.chat") }}';

    let history   = [];
    let isOpen    = false;
    let isWaiting = false;

    window.toggleAiChat = function () {
        isOpen = !isOpen;
        const panel = document.getElementById('aiChatPanel');
        const iconOpen  = document.getElementById('aiChatIconOpen');
        const iconClose = document.getElementById('aiChatIconClose');
        const dot       = document.getElementById('aiUnreadDot');

        if (isOpen) {
            panel.classList.remove('hidden');
            setTimeout(() => panel.classList.add('open'), 10);
            iconOpen.classList.add('hidden');
            iconClose.classList.remove('hidden');
            dot.classList.add('hidden');
            document.getElementById('aiChatInput').focus();
        } else {
            panel.classList.remove('open');
            setTimeout(() => panel.classList.add('hidden'), 200);
            iconOpen.classList.remove('hidden');
            iconClose.classList.add('hidden');
        }
    };

    window.aiQuickSend = function (text) {
        document.getElementById('aiQuickPrompts')?.remove();
        document.getElementById('aiChatInput').value = text;
        aiSendMessage();
    };

    window.aiSendMessage = async function () {
        if (isWaiting) return;

        const input = document.getElementById('aiChatInput');
        const text  = input.value.trim();
        if (!text) return;

        input.value = '';
        document.getElementById('aiQuickPrompts')?.remove();

        appendMessage('user', text);
        history.push({ role: 'user', content: text });

        // keep last 10 turns to avoid huge payloads
        if (history.length > 20) history = history.slice(-20);

        isWaiting = true;
        const typing = appendTyping();

        try {
            const res  = await fetch(chatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({ messages: history }),
            });

            const data = await res.json();
            typing.remove();

            const reply = data.success ? data.reply : (data.message ?? 'Something went wrong.');
            appendMessage('bot', reply);
            history.push({ role: 'assistant', content: reply });

            if (!isOpen) {
                document.getElementById('aiUnreadDot').classList.remove('hidden');
            }

        } catch (e) {
            typing.remove();
            appendMessage('bot', 'Network error. Please try again.');
        } finally {
            isWaiting = false;
        }
    };

    function formatBotText(text) {
        const lines = text.split('\n');
        let html = '';
        let inOl = false;
        let inUl = false;

        const closeList = () => {
            if (inOl) { html += '</ol>'; inOl = false; }
            if (inUl) { html += '</ul>'; inUl = false; }
        };

        lines.forEach(raw => {
            const line = raw.trim();
            if (!line) { closeList(); return; }

            const olMatch = line.match(/^(\d+)\.\s+(.*)/);
            const ulMatch = line.match(/^[-•]\s+(.*)/);

            if (olMatch) {
                if (inUl) { html += '</ul>'; inUl = false; }
                if (!inOl) { html += '<ol class="list-decimal pl-4 mt-1 space-y-1">'; inOl = true; }
                html += `<li>${escHtml(olMatch[2])}</li>`;
            } else if (ulMatch) {
                if (inOl) { html += '</ol>'; inOl = false; }
                if (!inUl) { html += '<ul class="list-disc pl-4 mt-1 space-y-1">'; inUl = true; }
                html += `<li>${escHtml(ulMatch[1])}</li>`;
            } else {
                closeList();
                html += `<p class="mt-1">${escHtml(line)}</p>`;
            }
        });

        closeList();
        return html;
    }

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function appendMessage(role, text) {
        const wrap = document.getElementById('aiChatMessages');
        const div  = document.createElement('div');
        div.className = (role === 'user' ? 'ai-msg-user' : 'ai-msg-bot') + ' text-sm px-4 py-3 max-w-[85%]';

        if (role === 'bot') {
            div.innerHTML = formatBotText(text);
        } else {
            div.textContent = text;
        }

        wrap.appendChild(div);
        wrap.scrollTop = wrap.scrollHeight;
        return div;
    }

    function appendTyping() {
        const wrap = document.getElementById('aiChatMessages');
        const div  = document.createElement('div');
        div.className = 'ai-msg-bot text-sm px-4 py-3 max-w-[85%]';
        div.innerHTML = '<span class="inline-flex gap-1"><span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay:0ms"></span><span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay:150ms"></span><span class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay:300ms"></span></span>';
        wrap.appendChild(div);
        wrap.scrollTop = wrap.scrollHeight;
        return div;
    }
})();
</script>

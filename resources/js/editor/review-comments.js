/** Local document comments. Anchors and comment text live in document HTML so drafts survive reloads. */
export class ReviewComments {
    constructor(engine) {
        this.engine = engine;
        this.panel = document.getElementById('review-comments-panel');
        this.list = document.getElementById('review-comments-list');
        this.input = document.getElementById('review-comment-input');
        this.pendingRange = null;
    }

    get anchors() {
        return this.engine.getAllEditorBodies().flatMap(body =>
            Array.from(body.querySelectorAll('.document-comment[data-comment-id]')));
    }

    addComment(range, message) {
        const text = message.trim();
        if (!range || range.collapsed || !text || text.length > 2000) return false;
        const start = range.startContainer.nodeType === 1 ? range.startContainer : range.startContainer.parentElement;
        const end = range.endContainer.nodeType === 1 ? range.endContainer : range.endContainer.parentElement;
        const startBody = start?.closest('.page-sheet-body');
        const blockSelector = 'p, h1, h2, h3, h4, h5, h6, li, blockquote, td, th';
        if (!startBody?.isConnected || !this.engine.getAllEditorBodies().includes(startBody) ||
            end?.closest('.page-sheet-body') !== startBody ||
            !start.closest(blockSelector) || start.closest(blockSelector) !== end.closest(blockSelector) ||
            start.closest('.document-comment') || end.closest('.document-comment') ||
            !range.toString().trim()) return false;

        const anchor = document.createElement('span');
        anchor.className = 'document-comment';
        anchor.dataset.commentId = globalThis.crypto?.randomUUID?.() || `comment-${Date.now()}-${Math.random().toString(36).slice(2)}`;
        anchor.dataset.commentText = text;
        anchor.dataset.commentResolved = 'false';
        anchor.appendChild(range.extractContents());
        range.insertNode(anchor);
        this.engine.handleInput();
        this.render();
        return true;
    }

    setResolved(id, resolved) {
        const anchor = this.anchors.find(item => item.dataset.commentId === id);
        if (!anchor) return false;
        anchor.dataset.commentResolved = String(resolved);
        this.engine.handleInput();
        this.render();
        return true;
    }

    deleteComment(id) {
        const anchor = this.anchors.find(item => item.dataset.commentId === id);
        if (!anchor) return false;
        anchor.replaceWith(...anchor.childNodes);
        this.engine.handleInput();
        this.render();
        return true;
    }

    render() {
        if (!this.list) return;
        this.list.replaceChildren();
        const anchors = this.anchors;
        if (!anchors.length) {
            const empty = document.createElement('p');
            empty.className = 'text-xs text-gray-500 dark:text-gray-400';
            empty.textContent = 'Belum ada komentar pada dokumen.';
            this.list.appendChild(empty);
            return;
        }
        for (const anchor of anchors) {
            const item = document.createElement('div');
            item.className = 'rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-xs space-y-2';
            const quote = document.createElement('button');
            quote.type = 'button';
            quote.className = 'block text-left truncate w-full text-indigo-700 dark:text-indigo-300 font-medium';
            quote.textContent = anchor.textContent;
            quote.addEventListener('click', () => anchor.scrollIntoView({ behavior: 'smooth', block: 'center' }));
            const message = document.createElement('p');
            message.className = 'whitespace-pre-wrap break-words text-gray-800 dark:text-gray-200';
            message.textContent = anchor.dataset.commentText || '';
            const status = document.createElement('span');
            status.className = 'text-gray-500 dark:text-gray-400';
            const resolved = anchor.dataset.commentResolved === 'true';
            status.textContent = resolved ? 'Selesai' : 'Terbuka';
            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'text-indigo-600 dark:text-indigo-400';
            toggle.textContent = resolved ? 'Buka kembali' : 'Selesaikan';
            toggle.addEventListener('click', () => this.setResolved(anchor.dataset.commentId, !resolved));
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'text-rose-600 dark:text-rose-400';
            remove.textContent = 'Hapus';
            remove.addEventListener('click', () => this.deleteComment(anchor.dataset.commentId));
            const actions = document.createElement('div');
            actions.className = 'flex items-center gap-3';
            actions.append(status, toggle, remove);
            item.append(quote, message, actions);
            this.list.appendChild(item);
        }
    }

    bind() {
        if (!this.panel || !this.list || !this.input) return;
        const open = () => {
            this.panel.classList.remove('hidden');
            this.render();
        };
        document.getElementById('btn-review-comments')?.addEventListener('click', open);
        document.getElementById('btn-close-review-comments')?.addEventListener('click', () => this.panel.classList.add('hidden'));
        document.getElementById('btn-review-new-comment')?.addEventListener('click', () => {
            const selection = window.getSelection();
            this.pendingRange = selection?.rangeCount && !selection.isCollapsed ? selection.getRangeAt(0).cloneRange() : null;
            open();
            this.input.focus();
        });
        document.getElementById('btn-save-review-comment')?.addEventListener('click', () => {
            if (!this.addComment(this.pendingRange, this.input.value)) {
                this.input.setCustomValidity('Pilih teks dalam satu paragraf dan isi komentar (maksimal 2000 karakter).');
                this.input.reportValidity();
                return;
            }
            this.pendingRange = null;
            this.input.value = '';
            this.input.setCustomValidity('');
        });
        this.input.addEventListener('input', () => this.input.setCustomValidity(''));
        const observer = new MutationObserver(() => this.render());
        if (this.engine.pagesWrapper) observer.observe(this.engine.pagesWrapper, { childList: true, subtree: true, attributes: true, attributeFilter: ['data-comment-resolved', 'data-comment-text'] });
        this.render();
    }
}

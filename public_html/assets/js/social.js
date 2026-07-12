/* ====================================================
   social.js – Amis, Chat, Publications
   ==================================================== */

const API = {
    async call(endpoint, method = 'GET', body = null, params = {}) {
        const qs = new URLSearchParams({ action: 'api', endpoint, ...params });
        const opts = { method, headers: { 'Content-Type': 'application/json' } };
        if (body) opts.body = JSON.stringify(body);
        const res = await fetch(`index.php?${qs}`, opts);
        return res.json();
    },
    async callMultipart(endpoint, formData) {
        const qs = new URLSearchParams({ action: 'api', endpoint });
        const res = await fetch(`index.php?${qs}`, { method: 'POST', body: formData });
        return res.json();
    },
    get: (ep, params) => API.call(ep, 'GET', null, params),
    post: (ep, body) => API.call(ep, 'POST', body),
};

/* ── FRIEND SEARCH ────────────────────────────── */
const FriendSearch = {
    timer: null,
    init() {
        const input = document.getElementById('friend-search');
        if (!input) return;
        input.addEventListener('input', () => {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.search(input.value.trim()), 350);
        });
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#friend-search') && !e.target.closest('#search-results')) {
                const searchResults = document.getElementById('search-results');
                if (searchResults) searchResults.classList.remove('show');
            }
        });
    },

    async search(q) {
        const container = document.getElementById('search-results');
        if (!container) return;
        if (q.length < 2) { container.classList.remove('show'); return; }

        container.innerHTML = '<div style="padding:0.75rem 1rem;color:var(--text-muted);font-size:0.85rem;">Recherche…</div>';
        container.classList.add('show');

        const results = await API.get('search-users', { q });
        if (!Array.isArray(results) || results.length === 0) {
            container.innerHTML = '<div style="padding:0.75rem 1rem;color:var(--text-muted);font-size:0.85rem;">Aucun résultat</div>';
            return;
        }

        container.innerHTML = results.map(u => {
            const initials = (u.nom || '?')[0].toUpperCase();
            const name = `${u.nom} ${u.prenom}`;
            const handle = u.username ? `@${u.username}` : '';
            let actionBtn = '';
            if (u.relation === 'accepted') {
                actionBtn = `<span style="font-size:0.75rem;color:var(--success);font-weight:600;">✓ Ami</span>`;
            } else if (u.relation === 'sent') {
                actionBtn = `<button class="btn btn-outline btn-sm" onclick="Social.cancelFriend(event, ${u.id_user}, this)" style="color:var(--text-muted);">Annuler</button>`;
            } else if (u.relation === 'received') {
                actionBtn = `<button class="btn btn-success btn-sm" onclick="Social.respondFromSearch(event, ${u.id_user})">Accepter</button>`;
            } else if (u.relation === 'declined') {
                actionBtn = `<span style="font-size:0.75rem;color:var(--danger);">Demande refusée</span>`;
            } else {
                actionBtn = `<button class="btn btn-primary btn-sm" onclick="Social.addFriend(event, ${u.id_user}, this)">+ Ajouter</button>`;
            }
            return `
        <div class="search-result-item">
          <div class="user-avatar-sm">${initials}</div>
          <div class="user-info-sm">
            <div class="name">${escHtml(name)}</div>
            <div class="handle">${escHtml(handle)}</div>
          </div>
          ${actionBtn}
        </div>`;
        }).join('');
    }
};

/* ── SOCIAL ACTIONS ───────────────────────────── */
const Social = {

    async addFriend(e, userId, btn) {
        e.stopPropagation();
        btn.disabled = true;
        const originalText = btn.textContent;
        btn.textContent = '…';

        const res = await API.post('add-friend', { id_user: userId });
        if (res.success) {
            btn.textContent = 'Annuler';
            btn.className = 'btn btn-outline btn-sm';
            btn.onclick = (ev) => Social.cancelFriend(ev, userId, btn);
            WorkFlow.toast.show('Demande d\'amitié envoyée !', 'success');
        } else {
            btn.disabled = false;
            btn.textContent = originalText;
            WorkFlow.toast.show(res.error || 'Erreur', 'error');
        }
    },

    async cancelFriend(e, userId, btn) {
        e.stopPropagation();
        if (!confirm('Annuler votre demande ?')) return;

        btn.disabled = true;
        const res = await API.post('cancel-friend-request', { id_user: userId });
        if (res.success) {
            btn.textContent = '+ Ajouter';
            btn.className = 'btn btn-primary btn-sm';
            btn.onclick = (ev) => Social.addFriend(ev, userId, btn);
            WorkFlow.toast.show('Demande annulée', 'info');
        } else {
            btn.disabled = false;
            WorkFlow.toast.show('Erreur', 'error');
        }
    },

    async respondFromSearch(e, userId) {
        // Afficher modal ou rediriger vers la section demandes
        e.stopPropagation();
        WorkFlow.nav.navigate('amis', 'Amis');
        WorkFlow.toast.show('Acceptez la demande dans la section Amis', 'info');
    },

    async respondFriend(id_amitie, statut, btn) {
        btn.disabled = true;
        const res = await API.post('friend-respond', { id_amitie, statut });
        if (res.success) {
            const card = document.getElementById(`demande-${id_amitie}`);
            if (card) {
                card.style.transition = 'opacity 0.3s ease, max-height 0.3s ease';
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 300);
            }
            WorkFlow.toast.show(statut === 'accepted' ? 'Ami ajouté ! 🎉' : 'Demande refusée', statut === 'accepted' ? 'success' : 'info');
            if (statut === 'accepted') setTimeout(() => location.reload(), 1200);
        }
    },

    async removeFriend(userId, btn) {
        if (!confirm('Retirer cet ami ?')) return;
        btn.disabled = true;
        const res = await API.post('remove-friend', { id_user: userId });
        if (res.success) {
            const card = document.getElementById(`ami-${userId}`);
            if (card) {
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 300);
            }
            WorkFlow.toast.show('Ami retiré', 'info');
        }
    },

    openChat(userId, name) {
        console.log('[Social] openChat', { userId, name });
        WorkFlow.nav.navigate('messages', 'Messages');
        // Laisser le temps à la nav SPA d'activer la section avant l'ouverture du chat
        setTimeout(() => Chat.openWithUser(userId, name), 180);
    },

    openChatFromBtn(btn) {
        const userId = parseInt(btn.getAttribute('data-user-id'));
        const userName = btn.getAttribute('data-user-name') || btn.textContent.trim();
        console.log('[Social] openChatFromBtn', { userId, userName });
        if (!userId) {
            WorkFlow.toast.show('Erreur: id utilisateur manquant', 'error');
            return;
        }
        if (!userName) {
            WorkFlow.toast.show('Erreur: nom utilisateur manquant', 'error');
            return;
        }
        this.openChat(userId, userName);
    },

    openAttachActivity() {
        WorkFlow.modal.open('modal-attach-activity');
    },

    _composeActiviteId: null,

    attachActivity(id, name) {
        this._composeActiviteId = id;
        document.getElementById('compose-activite-name').textContent = name;
        document.getElementById('compose-activite-tag').style.display = 'block';
        WorkFlow.modal.closeEl(document.getElementById('modal-attach-activity'));
    },

    clearComposeActivite() {
        this._composeActiviteId = null;
        document.getElementById('compose-activite-tag').style.display = 'none';
    },

    previewComposePhoto(input) {
        const file = input.files && input.files[0];
        const preview = document.getElementById('compose-photo-preview');
        const img = document.getElementById('compose-photo-img');
        if (!file || !preview || !img) return;
        img.src = URL.createObjectURL(file);
        preview.style.display = 'block';
    },

    clearComposePhoto() {
        const input = document.getElementById('compose-photo');
        const preview = document.getElementById('compose-photo-preview');
        if (input) input.value = '';
        if (preview) preview.style.display = 'none';
    },

    async submitPost() {
        const text = document.getElementById('compose-text').value.trim();
        if (!text) { WorkFlow.toast.show('Écrivez quelque chose !', 'error'); return; }
        const btn = document.getElementById('compose-submit');
        btn.disabled = true;

        const photoInput = document.getElementById('compose-photo');
        const hasPhoto = photoInput && photoInput.files && photoInput.files[0];
        let res;

        if (hasPhoto) {
            const formData = new FormData();
            formData.append('contenu', text);
            formData.append('visibilite', document.getElementById('compose-visibilite').value);
            if (this._composeActiviteId) formData.append('id_activite', this._composeActiviteId);
            formData.append('photo', photoInput.files[0]);
            res = await API.callMultipart('create-post', formData);
        } else {
            res = await API.post('create-post', {
                contenu: text,
                visibilite: document.getElementById('compose-visibilite').value,
                id_activite: this._composeActiviteId,
            });
        }

        btn.disabled = false;
        if (res.success) {
            document.getElementById('compose-text').value = '';
            this.clearComposeActivite();
            this.clearComposePhoto();
            WorkFlow.toast.show('Publication créée !', 'success');
            Feed.load();
        } else {
            WorkFlow.toast.show(res.error || 'Erreur lors de la publication', 'error');
        }
    },

    openShareActivity() {
        const act = window._selectedActivite;
        if (!act) return;
        const preview = document.getElementById('share-activity-preview');
        preview.innerHTML = `<div class="post-activite-tag" style="display:inline-flex;">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
      ${escHtml(act.libelle)}
    </div>`;
        WorkFlow.modal.open('modal-share-activity');
    },

    async shareActivity() {
        const act = window._selectedActivite;
        if (!act) return;
        const text = document.getElementById('share-activity-text').value.trim();
        const res = await API.post('create-post', {
            contenu: text || `J'ai créé une activité : ${act.libelle}`,
            visibilite: document.getElementById('share-visibilite').value,
            id_activite: act.id,
        });
        if (res.success) {
            WorkFlow.modal.closeEl(document.getElementById('modal-share-activity'));
            WorkFlow.toast.show('Activité partagée !', 'success');
            WorkFlow.nav.navigate('feed', 'Publications');
            Feed.load();
        }
    },

    async respondFromSearch(e, userId) {
        e.stopPropagation();
        // Find the pending request - just refresh the section
        WorkFlow.toast.show('Chargement…', 'info');
    }
};

/* ── FEED ─────────────────────────────────────── */
const Feed = {
    async load() {
        const container = document.getElementById('feed-container');
        if (!container) return;
        const posts = await API.get('get-feed');
        if (!Array.isArray(posts) || posts.length === 0) {
            container.innerHTML = `<div class="empty-state" style="padding:3rem 0;">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.35;"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <p>Aucune publication pour l'instant.<br>Ajoutez des amis pour voir leur actualité !</p>
      </div>`;
            return;
        }
        container.innerHTML = posts.map(p => this.renderPost(p)).join('');
    },

    renderPost(p) {
        const initials = (p.nom || '?')[0].toUpperCase();
        const name = `${p.nom} ${p.prenom}`;
        const date = p.date_publication ? new Date(p.date_publication).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '';
        const isMe = parseInt(p.id_user) === (window.APP && window.APP.userId);
        const deleteBtn = isMe ? `<button onclick="Feed.deletePost(${p.id_post}, this)" style="background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:0.8rem;padding:2px 6px;border-radius:4px;transition:color 0.2s;" title="Supprimer" onmouseover="this.style.color='var(--danger)'" onmouseout="this.style.color='var(--text-muted)'">✕</button>` : '';
        const visIcon = p.visibilite === 'public' ? '🌍' : '👥';
        const activiteTag = p.activite_libelle ? `<div class="post-activite-tag"><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg> ${escHtml(p.activite_libelle)}</div>` : '';
        const titleHtml = p.titre ? `<div class="post-title">${escHtml(p.titre)}</div>` : '';
        const photoHtml = p.photo_path ? `<img src="${escAttr(p.photo_path)}" alt="Publication" class="post-photo">` : '';

        let activiteCard = '';
        if (p.id_activite && p.activite_libelle) {
            const meta = [
                p.activite_date ? escHtml(p.activite_date) : '',
                p.activite_debut && p.activite_fin ? `${escHtml(p.activite_debut)} → ${escHtml(p.activite_fin)}` : '',
                p.activite_priorite ? escHtml(p.activite_priorite) : ''
            ].filter(Boolean).join(' · ');
            const addBtn = !isMe ? `<button class="btn btn-primary btn-sm" style="margin-top:0.5rem;" onclick="Feed.addToAgenda(${p.id_activite}, this)">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Ajouter à mon agenda
            </button>` : '';
            activiteCard = `<div class="post-activite-card">
                <strong>${escHtml(p.activite_libelle)}</strong>
                ${p.activite_description ? `<div style="font-size:0.85rem;margin-top:0.25rem;">${escHtml(p.activite_description)}</div>` : ''}
                ${meta ? `<div class="meta">${meta}</div>` : ''}
                ${addBtn}
            </div>`;
        }

        const avatarHtml = p.user_photo_profil
            ? `<img src="${escAttr(p.user_photo_profil)}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`
            : initials;

        return `<div class="post-card" id="post-${p.id_post}">
      <div class="post-header">
        <div class="user-avatar-sm" style="overflow:hidden;">${avatarHtml}</div>
        <div style="flex:1;">
          <div style="font-weight:700;font-size:0.875rem;">${escHtml(name)}</div>
          <div style="font-size:0.75rem;color:var(--text-muted);">${date}</div>
        </div>
        <span class="visibility-badge">${visIcon}</span>
        ${deleteBtn}
      </div>
      <div class="post-body">
        ${activiteTag}
        ${titleHtml}
        <div class="post-content">${escHtml(p.contenu)}</div>
        ${photoHtml}
        ${activiteCard}
      </div>
    </div>`;
    },

    async addToAgenda(idActivite, btn) {
        btn.disabled = true;
        const res = await API.post('add-shared-activity', { id_activite: idActivite });
        if (res.success) {
            btn.textContent = '✓ Ajoutée à votre agenda';
            btn.className = 'btn btn-outline btn-sm';
            btn.style.marginTop = '0.5rem';
            WorkFlow.toast.show('Activité ajoutée à votre agenda !', 'success');
        } else {
            btn.disabled = false;
            WorkFlow.toast.show(res.error || 'Impossible d\'ajouter l\'activité', 'error');
        }
    },

    async deletePost(id, btn) {
        if (!confirm('Supprimer cette publication ?')) return;
        const res = await API.post('delete-post', { id_post: id });
        if (res.success) {
            const card = document.getElementById(`post-${id}`);
            if (card) {
                card.style.opacity = '0';
                card.style.transition = 'opacity 0.3s';
                setTimeout(() => card.remove(), 300);
            }
            WorkFlow.toast.show('Publication supprimée', 'info');
        }
    }
};

/* ── CHAT ─────────────────────────────────────── */
const Chat = {
    activeConvId: null,
    selectedConvId: null,
    convListScroll: 0,
    lastMsgId: 0,
    pollTimer: null,
    contactName: '',

    async loadConversations() {
        const convs = await API.get('get-conversations');
        const list = document.getElementById('conv-list');
        if (!list) return;

        if (!Array.isArray(convs) || convs.length === 0) {
            const amis = await API.get('get-friends');
            if (Array.isArray(amis) && amis.length > 0) {
                list.innerHTML = amis.map(a => {
                    const name = `${a.nom} ${a.prenom}`;
                    return `<div class="conv-item" onclick="Social.openChatFromBtn(this)" data-user-id="${a.id_user}" data-user-name="${escAttr(name)}">
                        <div class="user-avatar-sm" style="width:36px;height:36px;">${(a.nom||'?')[0].toUpperCase()}</div>
                        <div class="conv-meta"><div class="conv-name">${escHtml(name)}</div><div class="conv-preview">Démarrer le chat</div></div>
                        <span class="badge" style="background:var(--primary);color:#fff">Nouveau</span>
                    </div>`;
                }).join('');
            } else {
                list.innerHTML = `<div style="padding:1.25rem;text-align:center;color:var(--text-muted);font-size:0.8rem;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.4;display:block;margin:0 auto 0.4rem;"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            Aucune conversation pour le moment.<br>Ajoutez un ami pour discuter.
          </div>`;
            }
            return;
        }

        list.innerHTML = convs.map(c => {
            const initials = (c.nom || '?')[0].toUpperCase();
            const name = `${c.nom} ${c.prenom}`;
            const preview = c.dernier_message ? escHtml(c.dernier_message.slice(0, 35)) + (c.dernier_message.length > 35 ? '…' : '') : 'Commencer la conversation';
            const statusDot = c.statut_en_ligne ? '<span style="display:inline-block;width:8px;height:8px;background:#10b981;border-radius:50%;margin-left:4px;"></span>' : '';
            const unreadBadge = c.messages_non_lus > 0 ? `<span style="display:inline-block;background:var(--danger);color:white;padding:2px 6px;border-radius:12px;font-size:0.7rem;font-weight:700;">${c.messages_non_lus}</span>` : '';
            return `<div class="conv-item ${this.activeConvId == c.id_conversation ? 'active' : ''}" data-conv-id="${c.id_conversation}"
                   onclick="Chat.openConv(${c.id_conversation}, '${escAttr(name)}')">
        <div class="user-avatar-sm" style="width:36px;height:36px;position:relative;">${initials}${statusDot}</div>
        <div class="conv-meta">
          <div class="conv-name">${escHtml(name)}</div>
          <div class="conv-preview">${preview}</div>
        </div>
        ${unreadBadge}
      </div>`;
        }).join('');
    },

    async openWithUser(userId, name) {
        console.log('[Chat] openWithUser', { userId, name });
        const res = await API.post('start-conversation', { id_user: userId });
        if (res && res.id_conversation) {
            await this.loadConversations();
            this.openConv(res.id_conversation, name);
        } else {
            console.error('[Chat] start-conversation failed', res);
            WorkFlow.toast.show(res.error || 'Impossible d\'ouvrir le chat', 'error');
            const layout = document.getElementById('chat-layout');
            if (layout) {
                layout.classList.remove('mobile-chat-active');
            }
        }
    },

    async openConv(convId, name) {
        this.activeConvId = convId;
        this.lastMsgId = 0;
        this.contactName = name;

        // Marquer les messages comme lus
        await API.post('mark-messages-read', { conv_id: convId });

        document.getElementById('chat-empty').style.display = 'none';
        const active = document.getElementById('chat-active');
        active.classList.add('visible');

        const layout = document.getElementById('chat-layout');
        const backBtn = document.getElementById('chat-back-btn');

        this.selectedConvId = convId;
        const convList = document.getElementById('conv-list');
        if (convList) {
            this.convListScroll = convList.scrollTop;
        }

        if (layout && window.matchMedia('(max-width: 640px)').matches) {
            layout.classList.add('mobile-chat-active');
            if (backBtn) backBtn.style.display = 'inline-flex';
        } else {
            if (backBtn) backBtn.style.display = 'none';
        }

        document.getElementById('chat-contact-name').textContent = name;
        document.getElementById('chat-avatar').textContent = name[0].toUpperCase();

        document.querySelectorAll('.conv-item').forEach(i => i.classList.remove('active'));
        const items = document.querySelectorAll('.conv-item');
        items.forEach(i => {
            if (i.dataset && i.dataset.convId && parseInt(i.dataset.convId) === convId) {
                i.classList.add('active');
            }
        });

        document.getElementById('chat-messages').innerHTML = '';
        await this.loadMessages(true);

        document.getElementById('chat-input').focus();
        this.startPolling();
    },

    showConversations() {
        console.log('[Chat] showConversations');
        const layout = document.getElementById('chat-layout');
        const backBtn = document.getElementById('chat-back-btn');
        if (!layout) return;
        layout.classList.remove('mobile-chat-active');
        if (backBtn) backBtn.style.display = 'none';

        const chatActive = document.getElementById('chat-active');
        if (chatActive) chatActive.classList.remove('visible');
        const chatEmpty = document.getElementById('chat-empty');
        if (chatEmpty) chatEmpty.style.display = 'block';

        const convList = document.getElementById('conv-list');
        if (convList) {
            convList.scrollTop = this.convListScroll || 0;
        }

        if (this.selectedConvId) {
            document.querySelectorAll('.conv-item').forEach(i => {
                if (i.dataset && i.dataset.convId && parseInt(i.dataset.convId) === this.selectedConvId) {
                    i.classList.add('active');
                } else {
                    i.classList.remove('active');
                }
            });
        }
    },

    async loadMessages(initial = false) {
        if (!this.activeConvId) return;
        const params = initial ? { conv_id: this.activeConvId } : { conv_id: this.activeConvId, last_id: this.lastMsgId };
        const msgs = await API.get('get-messages', params);
        if (!Array.isArray(msgs) || msgs.length === 0) return;

        const container = document.getElementById('chat-messages');
        const myId = window.APP && window.APP.userId;
        const shouldScroll = container.scrollHeight - container.scrollTop - container.clientHeight < 80;

        msgs.forEach(m => {
            const isMe = parseInt(m.id_user) === myId;
            const time = m.date_envoi ? new Date(m.date_envoi).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '';
            const el = document.createElement('div');
            el.className = `chat-bubble-wrap ${isMe ? 'me' : ''}`;
            el.innerHTML = `
        <div class="chat-bubble">${escHtml(m.contenu)}</div>
        <span class="bubble-time">${time}</span>`;
            container.appendChild(el);
            if (parseInt(m.id_msg) > this.lastMsgId) this.lastMsgId = parseInt(m.id_msg);
        });

        if (initial || shouldScroll) {
            container.scrollTop = container.scrollHeight;
        }
    },

    async send() {
        const input = document.getElementById('chat-input');
        const contenu = input.value.trim();
        if (!contenu || !this.activeConvId) return;
        input.value = '';

        await API.post('send-message', { conv_id: this.activeConvId, contenu });
        await this.loadMessages(false);
        this.loadConversations();
    },

    startPolling() {
        clearInterval(this.pollTimer);
        this.pollTimer = setInterval(() => {
            if (this.activeConvId) this.loadMessages(false);
        }, 2500);
    },

    stopPolling() {
        clearInterval(this.pollTimer);
    }
};

/* ── PROFIL ───────────────────────────────────── */
const Profile = {
    async loadCollection() {
        const grid = document.getElementById('collection-grid');
        if (!grid) return;
        const photos = await API.get('get-collection');
        if (!Array.isArray(photos) || photos.length === 0) {
            grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1;padding:2rem 0;">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                <p>Aucune photo dans votre collection.<br>Ajoutez-en avec le bouton ci-dessus.</p>
            </div>`;
            return;
        }
        grid.innerHTML = photos.map(p => `
            <div class="collection-item" id="collection-${p.id_photo}">
                <img src="${escAttr(p.chemin_photo)}" alt="Collection">
                <button class="delete-btn" onclick="Profile.deleteCollectionPhoto(${p.id_photo})" title="Supprimer">✕</button>
            </div>
        `).join('');
    },

    async uploadProfilePic(input) {
        const file = input.files && input.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('photo', file);
        const res = await API.callMultipart('upload-profile-pic', formData);
        input.value = '';
        if (res.success) {
            this.updateAvatarDisplay(res.path);
            WorkFlow.toast.show('Photo de profil mise à jour !', 'success');
        } else {
            WorkFlow.toast.show(res.error || 'Erreur lors de l\'upload', 'error');
        }
    },

    updateAvatarDisplay(path) {
        const avatar = document.getElementById('profile-avatar');
        const topbar = document.getElementById('topbar-avatar');
        const html = `<img src="${escAttr(path)}" alt="Profil" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
        if (avatar) avatar.innerHTML = html;
        if (topbar) topbar.innerHTML = html;
        if (window.APP) window.APP.photoProfil = path;
    },

    async uploadCollectionPhoto(input) {
        const file = input.files && input.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('photo', file);
        const res = await API.callMultipart('upload-collection-photo', formData);
        input.value = '';
        if (res.success) {
            WorkFlow.toast.show('Photo ajoutée à la collection !', 'success');
            this.loadCollection();
        } else {
            WorkFlow.toast.show(res.error || 'Erreur lors de l\'upload', 'error');
        }
    },

    async deleteCollectionPhoto(photoId) {
        if (!confirm('Supprimer cette photo de la collection ?')) return;
        const res = await API.post('delete-collection-photo', { id_photo: photoId });
        if (res.success) {
            const el = document.getElementById(`collection-${photoId}`);
            if (el) el.remove();
            WorkFlow.toast.show('Photo supprimée', 'info');
            const grid = document.getElementById('collection-grid');
            if (grid && !grid.querySelector('.collection-item')) this.loadCollection();
        }
    }
};

/* ── NOTIFICATIONS ────────────────────────────── */
const Notifications = {
    lastUnread: 0,
    lastNotifId: 0,
    pollTimer: null,

    async poll() {
        const res = await API.get('get-notifications');
        if (!res || !Array.isArray(res.notifications)) return;

        const unread = res.unread || 0;
        this.updateBadges(unread);

        // Toast pour nouvelles notifications
        const latest = res.notifications[0];
        if (latest) {
            const latestId = parseInt(latest.id_notification);
            if (this.lastNotifId > 0 && latestId > this.lastNotifId && latest.etat_notification === 'unread') {
                WorkFlow.toast.show(latest.message || 'Nouvelle notification', 'info');
            }
            if (latestId > this.lastNotifId) this.lastNotifId = latestId;
        }

        this.lastUnread = unread;

        const section = document.querySelector('.page-section[data-id="notifications"]');
        if (section && section.classList.contains('active')) {
            this.renderList(res.notifications);
        }
    },

    updateBadges(count) {
        const navBadge = document.getElementById('nav-notif-badge');
        const topDot = document.getElementById('topbar-notif-dot');
        if (navBadge) {
            if (count > 0) {
                navBadge.style.display = '';
                navBadge.textContent = count > 99 ? '99+' : count;
            } else {
                navBadge.style.display = 'none';
            }
        }
        if (topDot) topDot.style.display = count > 0 ? '' : 'none';
    },

    async load() {
        const res = await API.get('get-notifications');
        if (!res) return;
        this.updateBadges(res.unread || 0);
        this.renderList(res.notifications || []);
        if (res.notifications && res.notifications.length) {
            this.lastNotifId = parseInt(res.notifications[0].id_notification);
        }
    },

    renderList(notifications) {
        const container = document.getElementById('notif-list');
        if (!container) return;

        if (!notifications.length) {
            container.innerHTML = `<div class="empty-state" style="padding:2rem 0;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                <p>Aucune notification.</p>
            </div>`;
            return;
        }

        container.innerHTML = notifications.map(n => {
            const date = n.date_notification
                ? new Date(n.date_notification).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
                : '';
            const unreadClass = n.etat_notification === 'unread' ? ' unread' : '';
            return `<div class="notif-item${unreadClass}">
                <div class="notif-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg></div>
                <div>
                    <div class="notif-text">${escHtml(n.message || n.type_libelle || 'Notification')}</div>
                    <div class="notif-time">${date}</div>
                </div>
            </div>`;
        }).join('');
    },

    async markAllRead() {
        await API.post('mark-notifications-read', {});
        this.updateBadges(0);
        document.querySelectorAll('#notif-list .notif-item.unread').forEach(el => el.classList.remove('unread'));
        WorkFlow.toast.show('Notifications marquées comme lues', 'info');
    },

    startPolling() {
        this.load();
        this.pollTimer = setInterval(() => this.poll(), 5000);
    }
};

/* ── HELPERS ──────────────────────────────────── */
function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function escAttr(str) {
    return String(str || '').replace(/'/g, "\\'");
}

/* ── INIT ─────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    FriendSearch.init();

    // Chat enter key
    const chatInput = document.getElementById('chat-input');
    if (chatInput) {
        chatInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                Chat.send();
            }
        });
    }

    // When navigating to messages, load conversations
    document.querySelectorAll('.nav-item[data-section="messages"]').forEach(btn => {
        btn.addEventListener('click', () => setTimeout(() => Chat.loadConversations(), 100));
    });

    // When navigating to feed, load posts
    document.querySelectorAll('.nav-item[data-section="feed"]').forEach(btn => {
        btn.addEventListener('click', () => setTimeout(() => Feed.load(), 100));
    });

    // When navigating to profil, load collection
    document.querySelectorAll('.nav-item[data-section="profil"]').forEach(btn => {
        btn.addEventListener('click', () => setTimeout(() => Profile.loadCollection(), 100));
    });

    // When navigating to notifications, load and mark read
    document.querySelectorAll('.nav-item[data-section="notifications"]').forEach(btn => {
        btn.addEventListener('click', () => setTimeout(() => {
            Notifications.load();
            API.post('mark-notifications-read', {});
            Notifications.updateBadges(0);
        }, 100));
    });

    // Auto-load feed if hash is #feed
    if (location.hash === '#feed') setTimeout(() => Feed.load(), 300);
    if (location.hash === '#messages') setTimeout(() => Chat.loadConversations(), 300);

    // Stop polling / refresh when messages section visibility change
    const messagesSection = document.querySelector('.page-section[data-id="messages"]');
    if (messagesSection) {
        const observer = new MutationObserver(() => {
            if (messagesSection.classList.contains('active')) {
                Chat.loadConversations();
            } else {
                Chat.stopPolling();
            }
        });
        observer.observe(messagesSection, { attributes: true, attributeFilter: ['class'] });
    }

    // Polling pour les messages non lus
    updateUnreadBadge();
    setInterval(updateUnreadBadge, 5000);

    // Polling notifications temps réel
    Notifications.startPolling();

    // Mettre à jour statut en ligne
    API.post('update-online-status', { online: 1 });
    setInterval(() => API.post('update-online-status', { online: 1 }), 30000);
});

async function updateUnreadBadge() {
    const res = await API.get('unread-count');
    const count = res.unread || 0;
    const icon = document.querySelector('[title="Messages"]');

    if (!icon) return;

    let badge = icon.querySelector('.notif-dot');
    if (count > 0) {
        if (!badge) {
            badge = document.createElement('span');
            badge.className = 'notif-dot';
            icon.appendChild(badge);
        }
    } else if (badge) {
        badge.remove();
    }
}

// Expose globally for inline HTML onclick handlers
window.Social = Social;
window.Chat = Chat;
window.Feed = Feed;
window.Profile = Profile;
window.Notifications = Notifications;
window.FriendSearch = FriendSearch;
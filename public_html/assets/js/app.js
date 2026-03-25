/* ====================================================
   WorkFlow – app.js
   Navigation fluide + interactions UI
   ==================================================== */

(function() {
    'use strict';

    /* ── Progress bar ───────────────────────────── */
    const progress = {
        el: null,
        timer: null,
        init() {
            this.el = document.getElementById('nprogress-bar');
        },
        start() {
            if (!this.el) return;
            this.el.style.transition = 'none';
            this.el.style.transform = 'scaleX(0)';
            requestAnimationFrame(() => {
                this.el.style.transition = 'transform 0.6s ease';
                this.el.style.transform = 'scaleX(0.7)';
            });
        },
        done() {
            if (!this.el) return;
            this.el.style.transform = 'scaleX(1)';
            clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                this.el.style.transition = 'opacity 0.3s ease';
                this.el.style.opacity = '0';
                setTimeout(() => {
                    this.el.style.transition = '';
                    this.el.style.transform = 'scaleX(0)';
                    this.el.style.opacity = '1';
                }, 350);
            }, 300);
        }
    };

    /* ── Toast notifications ────────────────────── */
    const toast = {
        container: null,
        init() {
            this.container = document.getElementById('toast-container');
        },
        show(message, type = 'info', duration = 3800) {
            if (!this.container) return;
            const el = document.createElement('div');
            el.className = `toast toast-${type}`;

            const icons = {
                success: '✓',
                error: '✕',
                info: 'ℹ'
            };

            el.innerHTML = `<span class="toast-dot"></span><span>${message}</span>`;
            this.container.appendChild(el);

            setTimeout(() => {
                el.classList.add('out');
                el.addEventListener('animationend', () => el.remove(), { once: true });
            }, duration);
        }
    };

    /* ── SPA-style section navigation ──────────── */
    const nav = {
        sections: {},
        navItems: [],
        topbarTitle: null,

        init() {
            this.sections = {};
            this.navItems = document.querySelectorAll('.nav-item[data-section]');
            this.topbarTitle = document.getElementById('topbar-title');

            // Index sections
            document.querySelectorAll('.page-section').forEach(s => {
                this.sections[s.dataset.id] = s;
            });

            // Bind nav items
            this.navItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.navigate(item.dataset.section, item.dataset.title || item.dataset.section);
                });
            });

            // Load from hash
            const hash = location.hash.replace('#', '') || 'dashboard';
            this.navigate(hash, null, false);
        },

        navigate(id, title, pushState = true) {
            const section = this.sections[id];
            if (!section) return;

            // Progress bar
            progress.start();

            // Deactivate all
            Object.values(this.sections).forEach(s => s.classList.remove('active'));
            this.navItems.forEach(i => i.classList.remove('active'));

            // Activate target
            section.classList.add('active');
            const activeItem = document.querySelector(`.nav-item[data-section="${id}"]`);
            if (activeItem) {
                activeItem.classList.add('active');
                title = title || activeItem.dataset.title || id;
            }

            // Topbar title
            if (this.topbarTitle && title) {
                this.topbarTitle.textContent = title;
            }

            // Update hash
            if (pushState) {
                history.pushState({ section: id }, '', `#${id}`);
            }

            // Close mobile sidebar
            sidebar.close();

            if (id === 'messages' && window.Chat) {
                setTimeout(() => window.Chat.loadConversations(), 120);
            }

            setTimeout(() => progress.done(), 200);
        }
    };

    /* ── Sidebar (mobile) ───────────────────────── */
    const sidebar = {
        el: null,
        overlay: null,
        toggleBtn: null,

        init() {
            this.el = document.getElementById('sidebar');
            this.toggleBtn = document.getElementById('sidebar-toggle');

            if (!this.el) return;

            // Create overlay
            this.overlay = document.createElement('div');
            this.overlay.style.cssText =
                'position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:99;display:none;';
            document.body.appendChild(this.overlay);

            if (this.toggleBtn) {
                this.toggleBtn.addEventListener('click', () => this.toggle());
            }
            this.overlay.addEventListener('click', () => this.close());
        },

        toggle() {
            this.el.classList.toggle('open');
            this.overlay.style.display = this.el.classList.contains('open') ? 'block' : 'none';
        },

        close() {
            if (!this.el) return;
            this.el.classList.remove('open');
            if (this.overlay) this.overlay.style.display = 'none';
        }
    };

    /* ── Modals ─────────────────────────────────── */
    const modal = {
        init() {
            // Open triggers
            document.querySelectorAll('[data-modal-open]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.modalOpen;
                    this.open(id);
                });
            });

            // Close triggers
            document.querySelectorAll('[data-modal-close], .modal-close').forEach(btn => {
                btn.addEventListener('click', () => {
                    const overlay = btn.closest('.modal-overlay');
                    if (overlay) this.closeEl(overlay);
                });
            });

            // Close on overlay click
            document.querySelectorAll('.modal-overlay').forEach(overlay => {
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) this.closeEl(overlay);
                });
            });

            // ESC key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.modal-overlay.open').forEach(o => this.closeEl(o));
                }
            });
        },

        open(id) {
            const overlay = document.getElementById(id);
            if (overlay) overlay.classList.add('open');
        },

        closeEl(overlay) {
            overlay.classList.remove('open');
        }
    };

    /* ── Flash message auto-dismiss ─────────────── */
    function initFlash() {
        document.querySelectorAll('.alert').forEach(el => {
            setTimeout(() => {
                el.style.transition = 'opacity 0.4s ease, max-height 0.4s ease, margin 0.4s ease, padding 0.4s ease';
                el.style.opacity = '0';
                el.style.maxHeight = '0';
                el.style.marginBottom = '0';
                el.style.paddingTop = '0';
                el.style.paddingBottom = '0';
                setTimeout(() => el.remove(), 450);
            }, 4000);
        });

        // Also display as toast if present
        const errorAlert = document.querySelector('.alert-error');
        const successAlert = document.querySelector('.alert-success');
        if (errorAlert) toast.show(errorAlert.textContent.trim(), 'error');
        if (successAlert) toast.show(successAlert.textContent.trim(), 'success');
    }

    /* ── Form loading state ─────────────────────── */
    function initForms() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                const btn = form.querySelector('[type="submit"]');
                if (!btn) return;

                // Validate HTML5
                if (!form.checkValidity()) return;

                const original = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `
          <svg style="animation:spin 0.7s linear infinite" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M21 12a9 9 0 11-6.219-8.56"/>
          </svg>
          Chargement…`;

                // Re-enable after 8s as failsafe
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = original;
                }, 8000);
            });
        });
    }

    /* ── Password toggle ────────────────────────── */
    function initPasswordToggle() {
        document.querySelectorAll('[data-password-toggle]').forEach(btn => {
            const targetId = btn.dataset.passwordToggle;
            const input = document.getElementById(targetId);
            if (!input) return;
            btn.addEventListener('click', () => {
                const isText = input.type === 'text';
                input.type = isText ? 'password' : 'text';
                btn.innerHTML = isText ?
                    `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>` :
                    `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;
            });
        });
    }

    /* ── Back button navigation ─────────────────── */
    window.addEventListener('popstate', (e) => {
        if (e.state && e.state.section) {
            nav.navigate(e.state.section, null, false);
        }
    });

    /* ── Confirm dialogs ───────────────────────── */
    function initConfirm() {
        document.querySelectorAll('[data-confirm]').forEach(el => {
            el.addEventListener('click', (e) => {
                if (!confirm(el.dataset.confirm)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        });
    }

    /* ── Spin keyframe ──────────────────────────── */
    const style = document.createElement('style');
    style.textContent = '@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }';
    document.head.appendChild(style);

    /* ── Activite management ────────────────────── */
    const activite = {
        openEditModal(id, data, event = null) {
            if (event && typeof event.stopPropagation === 'function') {
                event.stopPropagation();
            }

            const modSection = document.querySelector('.page-section[data-id="modifier-activite"]');
            if (!modSection) {
                console.error('Section modifier-activite introuvable');
                return;
            }

            document.getElementById('mod-id_activite').value = id;
            document.getElementById('mod-libelle').value = data.libelle || '';
            document.getElementById('mod-description').value = data.description || '';
            document.getElementById('mod-priorite').value = data.priorite || '';
            document.getElementById('mod-date_activite').value = data.date_activite || '';
            document.getElementById('mod-heure_debut').value = data.heure_debut || '';
            document.getElementById('mod-heure_fin').value = data.heure_fin || '';
            document.getElementById('mod-id_etat').value = data.id_etat || 1;

            nav.navigate('modifier-activite', 'Modifier l\'activité');
        },

        openEditModalFromButton(button, event) {
            if (event && typeof event.stopPropagation === 'function') {
                event.stopPropagation();
            }
            const dataJson = button.getAttribute('data-activite');
            if (!dataJson) {
                console.error('Aucune donnée d\'activité fournie');
                return;
            }
            let data = {};
            try {
                data = JSON.parse(dataJson);
            } catch (err) {
                console.error('Impossible de parser data-activite', err);
                return;
            }
            this.openEditModal(data.id_activite || data.id, data);
        },

        delete(id) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette activité ?')) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/?action=supprimer-activite';
            form.innerHTML = '<input type="hidden" name="id_activite" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        },

        updateEtatAuto(id) {
            fetch('/?action=api&endpoint=update-activite-etat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_activite: id })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    };

    /* ── Init everything ────────────────────────── */
    document.addEventListener('DOMContentLoaded', () => {
        progress.init();
        toast.init();
        modal.init();
        sidebar.init();
        initFlash();
        initForms();
        initPasswordToggle();
        initConfirm();

        // Only init SPA nav on dashboard
        if (document.getElementById('sidebar')) {
            nav.init();
        }
    });

    // Expose globally for inline use
    window.WorkFlow = { toast, modal, nav };
    window.Activite = activite;
})();
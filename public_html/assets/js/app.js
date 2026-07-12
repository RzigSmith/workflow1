/* ====================================================
   WorkFlow – app.js
   Navigation fluide + interactions UI
   ==================================================== */

(function() {
    'use strict';

    const THEME_KEY = 'wf-theme';

    /* ── Theme (clair / sombre) ─────────────────── */
    const theme = {
        init() {
            const saved = localStorage.getItem(THEME_KEY);
            const preferred = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            this.set(saved || preferred, false);

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem(THEME_KEY)) {
                    this.set(e.matches ? 'dark' : 'light', false);
                }
            });

            document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
                btn.setAttribute('aria-pressed', this.get() === 'dark' ? 'true' : 'false');
                btn.addEventListener('click', () => this.toggle(btn));
            });
        },

        get() {
            return document.documentElement.getAttribute('data-theme') || 'light';
        },

        set(mode, persist = true) {
            document.documentElement.setAttribute('data-theme', mode);
            if (persist) localStorage.setItem(THEME_KEY, mode);
            document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
                btn.setAttribute('aria-pressed', mode === 'dark' ? 'true' : 'false');
            });
            document.dispatchEvent(new CustomEvent('themechange', { detail: { theme: mode } }));
        },

        toggle(btn) {
            const next = this.get() === 'dark' ? 'light' : 'dark';
            this.set(next);
            this.animateToggle(btn);
            if (window.WorkFlow?.toast) {
                window.WorkFlow.toast.show(
                    next === 'dark' ? 'Mode sombre activé' : 'Mode clair activé',
                    'info',
                    2200
                );
            }
        },

        animateToggle(btn) {
            const targets = btn ? [btn] : [...document.querySelectorAll('[data-theme-toggle]')];
            targets.forEach(el => {
                el.classList.remove('theme-toggle-spin');
                void el.offsetWidth;
                el.classList.add('theme-toggle-spin');
                setTimeout(() => el.classList.remove('theme-toggle-spin'), 500);
            });
            document.documentElement.classList.add('theme-switching');
            setTimeout(() => document.documentElement.classList.remove('theme-switching'), 450);
        }
    };

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
        indicator: null,
        isNavigating: false,
        currentId: null,

        init() {
            this.sections = {};
            this.navItems = document.querySelectorAll('.nav-item[data-section]');
            this.topbarTitle = document.getElementById('topbar-title');
            this.indicator = document.getElementById('nav-indicator');

            document.querySelectorAll('.page-section').forEach(s => {
                this.sections[s.dataset.id] = s;
            });

            this.navItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.navigate(item.dataset.section, item.dataset.title || item.dataset.section);
                    this.ripple(item);
                });
            });

            const hash = location.hash.replace('#', '') || 'accueil';
            const validHash = this.sections[hash] ? hash : 'accueil';
            this.navigate(validHash, null, false);

            window.addEventListener('resize', () => {
                const active = document.querySelector('.nav-item[data-section].active');
                if (active) this.moveIndicator(active);
            });

            document.addEventListener('themechange', () => {
                const active = document.querySelector('.nav-item[data-section].active');
                if (active) requestAnimationFrame(() => this.moveIndicator(active));
            });
        },

        ripple(el) {
            el.classList.remove('ripple');
            void el.offsetWidth;
            el.classList.add('ripple');
            setTimeout(() => el.classList.remove('ripple'), 500);
        },

        moveIndicator(activeItem) {
            if (!this.indicator || !activeItem) return;
            const nav = activeItem.closest('.sidebar-nav');
            if (!nav) return;
            const navRect = nav.getBoundingClientRect();
            const itemRect = activeItem.getBoundingClientRect();
            const top = itemRect.top - navRect.top + nav.scrollTop;
            this.indicator.style.transform = `translateY(${top}px)`;
            this.indicator.style.height = `${itemRect.height}px`;
            this.indicator.classList.add('visible');
        },

        navigate(id, title, pushState = true) {
            const section = this.sections[id];
            if (!section || this.isNavigating) return;
            if (id === this.currentId && section.classList.contains('active')) return;

            const current = document.querySelector('.page-section.active');
            if (current === section) return;

            this.isNavigating = true;
            progress.start();

            const finish = () => {
                Object.values(this.sections).forEach(s => {
                    s.classList.remove('active', 'leaving', 'entering');
                });
                this.navItems.forEach(i => i.classList.remove('active'));

                section.classList.add('active');
                const activeItem = document.querySelector(`.nav-item[data-section="${id}"]`);
                if (activeItem) {
                    activeItem.classList.add('active');
                    title = title || activeItem.dataset.title || id;
                    requestAnimationFrame(() => this.moveIndicator(activeItem));
                }

                if (this.topbarTitle && title) {
                    this.topbarTitle.style.opacity = '0';
                    this.topbarTitle.style.transform = 'translateY(-4px)';
                    setTimeout(() => {
                        this.topbarTitle.textContent = title;
                        this.topbarTitle.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                        this.topbarTitle.style.opacity = '1';
                        this.topbarTitle.style.transform = 'none';
                    }, 80);
                }

                if (pushState) {
                    history.pushState({ section: id }, '', `#${id}`);
                }

                this.currentId = id;
                sidebar.close();

                if (id === 'messages' && window.Chat) {
                    setTimeout(() => window.Chat.loadConversations(), 120);
                }
                if (id === 'feed' && window.Feed) {
                    setTimeout(() => window.Feed.load(), 120);
                }
                if (id === 'profil' && window.Profile) {
                    setTimeout(() => window.Profile.loadCollection(), 120);
                }
                if (id === 'notifications' && window.Notifications) {
                    setTimeout(() => {
                        window.Notifications.load();
                        fetch('index.php?action=api&endpoint=mark-notifications-read', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: '{}'
                        });
                        window.Notifications.updateBadges(0);
                    }, 120);
                }

                setTimeout(() => {
                    progress.done();
                    this.isNavigating = false;
                }, 280);
            };

            if (current) {
                current.classList.add('leaving');
                current.classList.remove('active');
                setTimeout(finish, 200);
            } else {
                finish();
            }
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
            form.action = 'index.php?action=supprimer-activite';
            form.innerHTML = '<input type="hidden" name="id_activite" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        },

        updateEtatAuto(id) {
            fetch('index.php?action=api&endpoint=update-activite-etat', {
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
        theme.init();
        progress.init();
        toast.init();
        modal.init();
        sidebar.init();
        initFlash();
        initForms();
        initPasswordToggle();
        initConfirm();

        if (document.getElementById('sidebar')) {
            nav.init();
        }
    });

    window.WorkFlow = { toast, modal, nav, theme };
    window.Activite = activite;
})();
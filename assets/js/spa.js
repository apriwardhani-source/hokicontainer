/**
 * Hoki Container - SPA Navigation Engine
 * Minimalist & Luxury Transitions
 */

class SPANav {
    constructor() {
        this.progressBar = null;
        this.container = document.querySelector('.app-container');
        this.init();
    }

    init() {
        // Create progress bar if not exists
        if (!document.querySelector('.spa-progress-bar')) {
            this.progressBar = document.createElement('div');
            this.progressBar.className = 'spa-progress-bar';
            document.body.appendChild(this.progressBar);
        } else {
            this.progressBar = document.querySelector('.spa-progress-bar');
        }

        // Intercept link clicks
        document.addEventListener('click', (e) => this.handleLinkClick(e));

        // Handle browser back/forward
        window.addEventListener('popstate', () => this.navigateTo(window.location.href, false));

        console.log('SPA Engine Initialized');
    }

    handleLinkClick(e) {
        const link = e.target.closest('a');
        if (!link) return;

        const url = new URL(link.href);
        const isInternal = url.origin === window.location.origin;
        const isSelf = link.getAttribute('target') === '_blank' || link.hasAttribute('download');
        const isSpecial = link.href.includes('#') || link.href.startsWith('javascript:');

        if (isInternal && !isSelf && !isSpecial) {
            e.preventDefault();
            this.navigateTo(link.href);
        }
    }

    async navigateTo(url, push = true) {
        if (this.isNavigating) return;
        this.isNavigating = true;

        // 1. Show Progress / Fade Out
        this.progressBar.style.width = '30%';
        this.container.classList.add('content-fade-out');

        try {
            const response = await fetch(url);
            const html = await response.text();
            const parser = new DOMParser();
            const newDoc = parser.parseFromString(html, 'text/html');

            // 2. Advance Progress
            this.progressBar.style.width = '70%';

            setTimeout(() => {
                // 3. Swap Content
                const newContent = newDoc.querySelector('.app-container');
                if (newContent) {
                    this.container.innerHTML = newContent.innerHTML;

                    // Update Page Title
                    document.title = newDoc.title;

                    // Update History
                    if (push) history.pushState({}, '', url);

                    // 4. Re-run Scripts
                    this.reexecuteScripts(newContent);

                    // 5. Scroll to Top
                    window.scrollTo(0, 0);

                    // 6. Fade In
                    this.container.classList.remove('content-fade-out');
                    this.container.classList.add('content-fade-in');

                    this.progressBar.style.width = '100%';

                    setTimeout(() => {
                        this.progressBar.style.width = '0';
                        this.container.classList.remove('content-fade-in');
                        this.isNavigating = false;
                    }, 400);
                } else {
                    // Fallback to normal navigation if structure mismatch
                    window.location.href = url;
                }
            }, 300);

        } catch (error) {
            console.error('Navigation Error:', error);
            window.location.href = url;
        }
    }

    reexecuteScripts(container) {
        const scripts = container.querySelectorAll('script');
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => {
                newScript.setAttribute(attr.name, attr.value);
            });
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });

        // Re-init generic animations if they exist
        if (window.initPageAnimations) window.initPageAnimations();
    }
}

// Start Engine
document.addEventListener('DOMContentLoaded', () => {
    window.spa = new SPANav();
});

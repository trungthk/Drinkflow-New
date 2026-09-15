import React from 'react';
import { createRoot } from 'react-dom/client';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

let initialized = false;

/** Initialize the shared React Toastify container and browser notification helper. */
export function initToastNotifications() {
    if (initialized) return;
    initialized = true;

    const mount = () => {
        const host = document.createElement('div');
        host.id = 'drinkflow-toast-root';
        document.body.appendChild(host);
        createRoot(host).render(
            React.createElement(ToastContainer, {
                position: 'top-right',
                autoClose: 4000,
                hideProgressBar: false,
                newestOnTop: true,
                closeOnClick: true,
                pauseOnHover: true,
                theme: 'light',
            }),
        );
    };

    if (document.body) mount();
    else document.addEventListener('DOMContentLoaded', mount, { once: true });

    window.notify = (message, type = 'info') => {
        const method = typeof toast[type] === 'function' ? toast[type] : toast.info;
        return method(String(message ?? ''));
    };

    // Keep legacy inline Blade handlers non-blocking while routing them through Toastify.
    window.alert = (message) => window.notify(message, 'info');
}

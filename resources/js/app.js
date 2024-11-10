import './bootstrap';
import * as bootstrap from 'bootstrap'
window.bootstrap = bootstrap;

// Custom JavaScript
document.querySelector('.navbar-toggler')?.addEventListener('click', () => {
    document.querySelector('.sidebar').classList.toggle('show');
});

document.addEventListener('click', (e) => {
    const sidebar = document.querySelector('.sidebar');
    const toggler = document.querySelector('.navbar-toggler');
    
    if (!sidebar?.contains(e.target) && !toggler?.contains(e.target)) {
        sidebar?.classList.remove('show');
    }
});
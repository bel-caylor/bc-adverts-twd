import '../css/main.css';

document.addEventListener('DOMContentLoaded', () => {
    console.log('Main JS Loaded');
    const header = document.createElement('h1');
    header.className = 'text-3xl font-bold underline text-center';
    header.innerText = 'Hello from Tailwind and Vite!';
    document.body.appendChild(header);
});
document.addEventListener('livewire:init', () => {
    const loginUrl = document.querySelector('meta[name="login-url"]')?.content ?? '/login';

    Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 419) {
                preventDefault();
                const separator = loginUrl.includes('?') ? '&' : '?';
                window.location.href = `${loginUrl}${separator}session_expired=1`;
            }
        });
    });
});

const ok = async (res) => {
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`);
    return data;
};

const API = {
    listProfiles() {
        return fetch('/api/profiles').then(ok);
    },
    me() {
        return fetch('/api/me', { credentials: 'include' }).then(ok);
    },
    login(email, password) {
        return fetch('/api/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ email, password }),
        }).then(ok);
    },
    logout() {
        return fetch('/api/logout', {
            method: 'POST',
            credentials: 'include',
        }).then(ok);
    },
    createProfile({ name, bio, file }) {
        const fd = new FormData();
        fd.append('name', name);
        fd.append('bio', bio);
        if (file) fd.append('avatar', file);
        return fetch('/api/profiles', {
            method: 'POST',
            body: fd,
            credentials: 'include',
        }).then(ok);
    },
};

export default API;

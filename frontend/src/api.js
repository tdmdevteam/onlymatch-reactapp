// api.js
const ok = async (res) => {
    const ct = res.headers.get('content-type') || '';
    let data = null;

    if (res.status === 204) {
        data = { ok: true };
    } else if (ct.includes('application/json')) {
        data = await res.json();
    } else {
        const text = await res.text().catch(() => '');
        data = text ? { message: text } : {};
    }

    if (!res.ok) throw new Error(data?.error || data?.message || `HTTP ${res.status}`);
    return data ?? { ok: true };
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
    createProfile({ name, file, onlyfans_url }) {
        const fd = new FormData();
        fd.append('name', name);
        if (onlyfans_url) fd.append('onlyfans_url', onlyfans_url);
        if (file) fd.append('avatar', file);

        return fetch('/api/profiles', {
            method: 'POST',
            body: fd,
            credentials: 'include',
        }).then(ok);
    },

    deleteProfile(id) {
        return fetch(`api/profiles/${id}`, {
            method: 'DELETE',
            credentials: 'include'
        }).then(ok);
    },
};

export default API;

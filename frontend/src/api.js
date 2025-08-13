const ok = async (res) => {
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.error || 'Request failed');
    return data;
};

const API = {
    listProfiles() {
        return fetch('/api/profiles').then(ok);
    },
    login(email, password) {
        return fetch('/api/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ email, password })
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
            credentials: 'include'
        }).then(ok);
    },
    deleteProfile(id) {
        return fetch(`/api/profiles/${id}`, {
            method: 'DELETE',
            credentials: 'include'
        }).then(ok);
    }
};

export default API;

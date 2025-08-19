import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import API from '../api.js';

export default function Admin() {
    const nav = useNavigate();

    const [authed, setAuthed] = useState(false);
    const [checking, setChecking] = useState(true);
    const [status, setStatus] = useState('');
    const [email, setEmail] = useState('dan@onlymatch.com');
    const [password, setPassword] = useState('1');
    const [name, setName] = useState('');
    const [file, setFile] = useState(null);
    const [onlyfans, setOnlyfans] = useState('');

    useEffect(() => {
        let alive = true;
        API.me()
            .then(() => { if (alive) setAuthed(true); })
            .catch(() => { if (alive) setAuthed(false); })
            .finally(() => { if (alive) setChecking(false); });
        return () => { alive = false; };
    }, []);

    async function handleLogin(e) {
        e.preventDefault();
        setStatus('Logging in…');
        try {
            await API.login(email, password);
            setAuthed(true);
            setStatus('Logged in ✔');
        } catch (err) {
            setStatus(err.message || 'Login failed');
        }
    }

    async function handleLogout() {
        setStatus('Logging out…');
        try {
            await API.logout();
            setAuthed(false);
            setStatus('Logged out');
        } catch (e) {
            setStatus(e.message || 'Logout failed');
        }
    }

    async function handleCreate(e) {
        e.preventDefault();
        setStatus('Uploading…');
        try {
            await API.createProfile({ name, file, onlyfans_url: onlyfans });
            setStatus('Created ✔');
            setName(''); setFile(null);
            nav('/'); // go see it on Home
        } catch (err) {
            setStatus(err.message || 'Upload failed');
        }
    }

    if (checking) return <p style={{ padding: 16 }}>Checking session…</p>;

    return (
        <div className="admin" style={{ padding: 16 }}>
            <h1>Admin</h1>

            {!authed ? (
                <form className="form" onSubmit={handleLogin}>
                    <h2>Login</h2>
                    <input type="email" placeholder="Email"
                        value={email} onChange={(e) => setEmail(e.target.value)} required />
                    <input type="password" placeholder="Password"
                        value={password} onChange={(e) => setPassword(e.target.value)} required />
                    <button className="btn">Login</button>
                </form>
            ) : (
                <>
                    <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                        <strong>Authenticated</strong>
                        <button className="btn" onClick={handleLogout}>Logout</button>
                    </div>

                    <form className="form" onSubmit={handleCreate} style={{ marginTop: 16 }}>
                        <h2>Create Profile</h2>
                        <input placeholder="Name" value={name} onChange={(e) => setName(e.target.value)} required />
                        <input placeholder="OnlyFans URL (e.g. onlyfans.com/username)" value={onlyfans} onChange={(e) => setOnlyfans(e.target.value)} />
                        <input type="file" accept="image/*"
                            onChange={(e) => setFile(e.target.files?.[0] || null)} />
                        <button className="btn">Create</button>
                    </form>
                </>
            )}

            {status && <p className="status">{status}</p>}
        </div>
    );
}

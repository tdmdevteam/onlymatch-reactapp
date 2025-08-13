import { useState } from 'react';
import API from '../api.js';
import ProfileCard from '../components/ProfileCard.jsx';

export default function Admin() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [name, setName] = useState('');
    const [bio, setBio] = useState('');
    const [file, setFile] = useState(null);
    const [status, setStatus] = useState('');
    const [created, setCreated] = useState(null);

    async function handleLogin(e) {
        e.preventDefault();
        try {
            await API.login(email, password);
            setStatus('Logged in ✔');
        } catch (e) {
            setStatus(e.message);
        }
    }

    async function handleCreate(e) {
        e.preventDefault();
        setStatus('Uploading…');
        try {
            const p = await API.createProfile({ name, bio, file });
            setCreated(p);
            setStatus(`Created profile #${p.id}`);
            setName(''); setBio(''); setFile(null);
        } catch (e) {
            setStatus(e.message);
        }
    }

    return (
        <div className="admin">
            <h1>Admin</h1>

            <form className="form" onSubmit={handleLogin}>
                <h2>Login</h2>
                <input
                    placeholder="Email"
                    value={email} onChange={(e) => setEmail(e.target.value)}
                    type="email" required
                />
                <input
                    placeholder="Password"
                    value={password} onChange={(e) => setPassword(e.target.value)}
                    type="password" required
                />
                <button className="btn">Login</button>
            </form>

            <form className="form" onSubmit={handleCreate}>
                <h2>Create Profile</h2>
                <input
                    placeholder="Name"
                    value={name} onChange={(e) => setName(e.target.value)}
                    required
                />
                <textarea
                    placeholder="Bio"
                    value={bio} onChange={(e) => setBio(e.target.value)}
                    rows={4}
                />
                <input
                    type="file" accept="image/*"
                    onChange={(e) => setFile(e.target.files?.[0] || null)}
                />
                <button className="btn">Create</button>
            </form>

            {status && <p className="status">{status}</p>}

            {created && (
                <>
                    <h3>Preview</h3>
                    <ProfileCard profile={created} />
                </>
            )}
        </div>
    );
}

import { useEffect, useState } from 'react';
import API from '../api.js';
import ProfileCard from '../components/ProfileCard.jsx';

export default function Home() {
    const [profiles, setProfiles] = useState([]);
    const [loading, setLoading] = useState(true);
    const [err, setErr] = useState('');

    const fetchProfiles = () => {
        setLoading(true);
        setErr('');
        API.listProfiles()
            .then((data) => setProfiles(Array.isArray(data) ? data : []))
            .catch((e) => setErr(e.message || 'Failed to load'))
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        fetchProfiles();
    }, []);

    if (loading) return <p style={{ padding: 16 }}>Loading profiles…</p>;
    if (err) return <p style={{ padding: 16, color: 'tomato' }}>{err}</p>;

    return (
        <div style={{ padding: 16 }}>
            <h1>Profiles</h1>
            {profiles.length === 0 ? (
                <p>No profiles yet.</p>
            ) : (
                <div className="grid">
                    {profiles.map(p => <ProfileCard key={p.id} profile={p} />)}
                </div>
            )}
        </div>
    );
}

import { useEffect, useState } from 'react';
import API from '../api.js';
import ProfileCard from '../components/ProfileCard.jsx';

export default function Home() {
    const [profiles, setProfiles] = useState([]);
    const [loading, setLoading] = useState(true);
    const [err, setErr] = useState('');

    useEffect(() => {
        let alive = true;
        API.listProfiles()
            .then((data) => { if (alive) setProfiles(data); })
            .catch((e) => setErr(e.message || 'Failed to load'))
            .finally(() => setLoading(false));
        return () => { alive = false; };
    }, []);

    if (loading) return <p>Loading profiles…</p>;
    if (err) return <p className="error">{err}</p>;

    return (
        <>
            <h1>Profiles</h1>
            {profiles.length === 0 ? (
                <p>No profiles yet.</p>
            ) : (
                <div className="grid">
                    {profiles.map((p) => (
                        <ProfileCard key={p.id} profile={p} />
                    ))}
                </div>
            )}
        </>
    );
}

// components/ProfileCard.jsx
import { useEffect, useState } from 'react';
import API from '../api';

export default function ProfileCard({ profile, onDeleted }) {
    const [me, setMe] = useState(null);
    const [deleting, setDeleting] = useState(false);
    useEffect(() => {
        API.me().then(setMe).catch(() => setMe(null));
    }, []);

    const safeId = profile.id ?? profile._id ?? profile.uuid;
    const isOwner = me && me.id === profile.user_id;
    const isAdmin = me && me.is_admin;
    const canDelete = Boolean(me && (isOwner || isAdmin));


    const handleDelete = async () => {
        if (!confirm(`Delete "${profile.name}"? This cannot be undone.`)) return;
        setDeleting(true);
        try {
            await API.deleteProfile(safeId);
            onDeleted?.(safeId);
        } finally {
            setDeleting(false);
        }
    };

    return (
        <article className="card">
            {profile.avatar_url ? (
                <img className="avatar" src={profile.avatar_url} alt={profile.name} loading="lazy" />
            ) : (
                <div className="avatar placeholder" />
            )}
            <div className="card-body">
                <h3 className="card-title">
                    {profile.name}
                    {profile.display_name && profile.display_name !== profile.name && (
                        <span style={{ marginLeft: 8, opacity: 0.7 }}>({profile.display_name})</span>
                    )}
                </h3>
                <div style={{ display: 'flex', gap: 12, alignItems: 'center', marginTop: 8, flexWrap: 'wrap' }}>
                    {Number.isFinite(profile.likes) && <span>❤️ {profile.likes}</span>}
                    {Number.isFinite(profile.photos_count) && <span>🖼️ {profile.photos_count} photos</span>}
                    {Number.isFinite(profile.videos_count) && <span>🎥 {profile.videos_count} videos</span>}
                    {profile.onlyfans_url && (
                        <a className="btn" href={profile.onlyfans_url} target="_blank" rel="noopener noreferrer">
                            View profile
                        </a>
                    )}
                    {canDelete && (
                        <button
                            className="btn danger"
                            onClick={handleDelete}
                            aria-label={`Delete ${profile.name}`}
                            disabled={deleting}
                        >
                            {deleting ? 'Deleting…' : 'Delete'}
                        </button>
                    )}
                </div>
            </div>
        </article>
    );
}
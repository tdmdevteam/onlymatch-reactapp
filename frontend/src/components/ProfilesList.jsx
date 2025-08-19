import React from 'react';
import API from '../api';
import ProfileCard from './ProfileCard.jsx';

export default function ProfilesList() {
    const [profiles, setProfiles] = React.useState([]);

    React.useEffect(() => {
        API.listProfiles().then(setProfiles);
    }, []);

    const handleDeleted = (deletedId) => {
        const did = String(deletedId);
        setProfiles(prev =>
            prev.filter(p => String(p.id ?? p._id ?? p.uuid) !== did)
        );
    };

    const getKey = (p) => String(p.id ?? p._id ?? p.uuid);

    return (
        <div>
            {profiles.map(p => (
                <ProfileCard
                    key={getKey(p)}
                    profile={p}
                    onDeleted={handleDeleted}
                />
            ))}
        </div>
    );
}

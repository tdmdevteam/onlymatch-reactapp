export default function ProfileCard({ profile, onDelete }) {
    return (
        <article className="card">
            {profile.avatar_url && (
                <img
                    src={profile.avatar_url}
                    alt={profile.name}
                    className="avatar"
                    loading="lazy"
                />
            )}
            <div className="card-body">
                <h3 className="card-title">{profile.name}</h3>
                {profile.bio && <p className="card-text">{profile.bio}</p>}
                {onDelete && (
                    <button className="btn danger" onClick={() => onDelete(profile.id)}>
                        Delete
                    </button>
                )}
            </div>
        </article>
    );
}

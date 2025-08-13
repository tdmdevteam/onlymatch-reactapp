export default function ProfileCard({ profile }) {
    return (
        <article className="card">
            {profile.avatar_url && (
                <img className="avatar" src={profile.avatar_url} alt={profile.name} loading="lazy" />
            )}
            <div className="card-body">
                <h3 className="card-title">{profile.name}</h3>
                {profile.bio && <p className="card-text">{profile.bio}</p>}
            </div>
        </article>
    );
}

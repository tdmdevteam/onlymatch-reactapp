#!/bin/sh
set -e

# Ensure the uploads directory exists and has correct permissions
mkdir -p /var/www/html/backend/public/uploads
chown -R www-data:www-data /var/www/html/backend/public/uploads
chmod -R 755 /var/www/html/backend/public/uploads

# Ensure SQLite database exists and has correct permissions
if [ ! -f /var/www/html/backend/db.sqlite ]; then
    echo "Creating SQLite database..."
    touch /var/www/html/backend/db.sqlite
fi
chown www-data:www-data /var/www/html/backend/db.sqlite
chmod 664 /var/www/html/backend/db.sqlite

# Ensure the directory containing the database is writable
chown www-data:www-data /var/www/html/backend
chmod 775 /var/www/html/backend

# Initialize database if needed
if [ -f /var/www/html/backend/schema.sql ]; then
    echo "Checking if database needs initialization..."
    php -r "
    \$db = new PDO('sqlite:/var/www/html/backend/db.sqlite');
    \$tables = \$db->query(\"SELECT name FROM sqlite_master WHERE type='table'\")->fetchAll(PDO::FETCH_COLUMN);
    if (empty(\$tables)) {
        echo \"Initializing database...\n\";
        \$sql = file_get_contents('/var/www/html/backend/schema.sql');
        \$db->exec(\$sql);
        echo \"Database initialized.\n\";
    } else {
        echo \"Database already initialized.\n\";
    }
    "
fi

# Run the seed script if it exists and admin table is empty
if [ -f /var/www/html/backend/seed_admin.php ]; then
    php -r "
    \$db = new PDO('sqlite:/var/www/html/backend/db.sqlite');
    \$count = \$db->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if (\$count == 0) {
        echo \"Seeding admin user...\n\";
        require '/var/www/html/backend/seed_admin.php';
        echo \"Admin user seeded.\n\";
    }
    "
fi

echo "Starting services..."
exec "$@"
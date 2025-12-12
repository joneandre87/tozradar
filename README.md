# TozRadar - Security Platform

A modern, feature-rich security platform with dynamic feature management system built with PHP, MySQL, and a stunning red neon theme.

## Features

- **Dynamic Feature Management**: Superadmin can add features through the backend without accessing cPanel
- **Modern Red Neon UI**: Professional dark theme with red accent colors and neon glow effects
- **User Authentication**: Secure login/registration system with role-based access
- **Subscription System**: Multiple subscription tiers (Free, Basic, Pro, Enterprise)
- **Custom CSS/JS**: Global custom styling and scripting through admin panel
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile devices

## Setup Instructions

### 1. Upload Files
- Upload all files from the `tozradar` folder to your cPanel public_html directory via File Manager

### 2. Database Setup
1. Open phpMyAdmin in cPanel
2. Create a new database named `tozradar_db`
3. Create a new database user `tozradar_user` with a strong password
4. Assign the user to the database with ALL PRIVILEGES
5. Open the `database.sql` file and run all SQL commands in phpMyAdmin

### 3. Configure Database Connection
1. Open `config.php` in the root directory
2. Update the database password:
   ```php
   define('DB_PASS', 'YOUR_ACTUAL_PASSWORD_HERE');
   ```

### 4. Set Permissions
- Ensure the `/features` directory has 755 permissions (it should create files automatically)

### 5. Login
- URL: `https://tozradar.com/login.php`
- Username: `admin`
- Password: `tozradar69`

**Important:** Change the admin password immediately after first login!

## File Structure

```
tozradar/
├── assets/
│   ├── style.css          # Main stylesheet (red neon theme)
│   └── script.js          # Main JavaScript
├── admin/
│   ├── index.php          # Admin dashboard
│   ├── settings.php       # User settings
│   └── superadmin/
│       ├── features.php   # Manage features
│       ├── newfeatures.php # Add new features
│       ├── design.php     # Custom CSS editor
│       └── script.php     # Custom JS editor
├── features/              # Dynamic feature files (auto-created)
├── config.php             # Database & site configuration
├── header.php             # Global header
├── footer.php             # Global footer
├── index.php              # Homepage
├── login.php              # Login page
├── register.php           # Registration page
├── subscriptions.php      # Subscription plans
├── features.php           # Features listing
├── feature.php            # Dynamic feature viewer
├── logout.php             # Logout handler
└── database.sql           # Database schema & demo data
```

## Superadmin Feature Creation

The superadmin can add new features through the backend in 5 simple steps:

1. **Title & Description**: Set the feature name and description
2. **Frontend Code**: Add HTML/PHP code for the user-facing feature page
3. **Backend Code**: Add HTML/PHP code for admin settings (optional)
4. **SQL Code**: Database modifications (optional)
5. **Create**: Automatically creates PHP files and executes SQL

### Example Feature Code

**Frontend Code:**
```html
<div class="feature-content">
    <h2>My Security Feature</h2>
    <p>This is a custom security feature.</p>
    <button class="btn btn-primary">Run Scan</button>
</div>
```

**Backend Code:**
```html
<div class="settings-panel">
    <h3>Feature Settings</h3>
    <form method="POST">
        <div class="form-group">
            <label>API Key</label>
            <input type="text" class="form-control" name="api_key">
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
```

## Database Tables

- **users**: User accounts with roles (user, admin, superadmin)
- **subscriptions**: User subscription plans and status
- **features**: Dynamically created features
- **settings**: Global site settings (CSS, JS, etc.)

## Security Features

- Password hashing with bcrypt
- SQL injection protection with prepared statements
- XSS protection with htmlspecialchars()
- Session security with regeneration
- Role-based access control

## Customization

### Colors
Edit CSS variables in `assets/style.css`:
```css
:root {
    --primary-red: #ff0000;
    --neon-red: #ff0033;
    --dark-bg: #0a0a0a;
}
```

### Global CSS/JS
Use the superadmin panel:
- Design (CSS): `/admin/superadmin/design.php`
- Scripts (JS): `/admin/superadmin/script.php`

## Support

For issues or questions, contact your developer or check the code comments.

## License

Proprietary - All rights reserved

---

**Built for TozRadar Security Solutions**

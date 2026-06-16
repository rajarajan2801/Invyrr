# Invyrr

Multi-location inventory management system built with PHP 7.2+ / MySQL.

## Local Development (XAMPP)

1. Place this folder in `C:\xampp\htdocs\Invyrr`
2. Start Apache + MySQL in XAMPP Control Panel
3. Import `invyrr.sql` into phpMyAdmin
4. Visit `http://localhost/Invyrr`

## Railway Deployment

### Environment Variables (set in Railway dashboard)
| Variable | Description |
|---|---|
| `MYSQLHOST` | Auto-set by Railway MySQL plugin |
| `MYSQLPORT` | Auto-set by Railway MySQL plugin |
| `MYSQLDATABASE` | Auto-set by Railway MySQL plugin |
| `MYSQLUSER` | Auto-set by Railway MySQL plugin |
| `MYSQLPASSWORD` | Auto-set by Railway MySQL plugin |

Railway MySQL plugin sets these automatically — no manual entry needed.

### Deploy Steps
1. Push this repo to GitHub (private repository)
2. Create new project on railway.app
3. Add MySQL plugin to the project
4. Deploy from GitHub repo
5. Import your database via Railway's MySQL console

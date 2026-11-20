# Karjee Farms – krf_amanaccounting

PHP / MySQL (MariaDB) web application for Karjee Farms: order & sales, dispatch, polyhouse tracking, purchase orders, labour payment, payroll and accounting.

## Local setup (XAMPP on Windows)

1. Clone into the XAMPP web root **with this exact folder name** (many pages build URLs from it):
   ```
   cd C:\xampp\htdocs
   git clone https://github.com/himanshuvashisht1999/karjee.git krf_amanaccounting
   ```
2. Create the config files that are **not** in git (they hold passwords). Copy each template and fill in real values:

   | Template (in git) | Create this file (git-ignored) |
   |---|---|
   | `connection.example.php` | `connection.php` |
   | `loginCell/connection.example.php` | `loginCell/connection.php` |
   | `modulesCell/connection.example.php` | `modulesCell/connection.php` |
   | `payroll/connection.example.php` | `payroll/connection.php` |
   | `labourpayment/connection.example.php` | `labourpayment/connection.php` |
   | `mail_config.example.php` | `mail_config.php` (SMTP password for outgoing email) |
   | – | `krf-orderandsales/private-key.pem` (QZ Tray print signing key) |

3. Import the databases into local MySQL (`karjeefarm`, `krf_accounting`, `hrmplus`, `dbauthorization_karjee`). Database dumps are never committed.
4. Open `http://localhost/krf_amanaccounting/loginCell/index.php`.

## Branches

- `main` – stable code. Changes arrive only through pull requests.
- `himanshu` – personal working branch. Push here, then open a pull request `himanshu → main` on GitHub and merge.

```
git checkout himanshu
git pull origin himanshu
# ...work...
git add -A
git commit -m "Describe the change"
git push origin himanshu
```

To bring the latest `main` into your branch: `git pull origin main` while on `himanshu`.

## What is never committed (see `.gitignore`)

Connection/credential files, `mail_config.php`, keys and certificates, SQL dumps and archives, user uploads (`attachementfile/`, `uploads/`), logs and editor folders.

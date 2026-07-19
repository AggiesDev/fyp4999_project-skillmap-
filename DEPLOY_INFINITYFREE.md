# Deploy to InfinityFree from GitHub

This project deploys to InfinityFree with GitHub Actions over FTP.

## 1. Create the InfinityFree database

In InfinityFree, create a MySQL database and import your database backup or `schema.sql` manually in phpMyAdmin.

After import, keep these values:

- Database host
- Database name
- Database username
- Database password

## 2. Add GitHub Secrets

Open your GitHub repository:

`Settings` -> `Secrets and variables` -> `Actions` -> `New repository secret`

Add these secrets:

```text
INFINITYFREE_FTP_SERVER
INFINITYFREE_FTP_USERNAME
INFINITYFREE_FTP_PASSWORD
INFINITYFREE_FTP_SERVER_DIR
INFINITYFREE_DB_HOST
INFINITYFREE_DB_NAME
INFINITYFREE_DB_USER
INFINITYFREE_DB_PASS
```

Common `INFINITYFREE_FTP_SERVER_DIR` values:

```text
/htdocs/
```

or, if your domain is inside a folder:

```text
/yourdomain.infinityfreeapp.com/htdocs/
```

Use the exact FTP path shown in InfinityFree File Manager or FTP details.

## 3. Push to GitHub

Every push to `main` or `master` will deploy automatically.

You can also deploy manually:

`GitHub` -> `Actions` -> `Deploy to InfinityFree` -> `Run workflow`

## Important

The workflow creates `config/dbconnect.php` during deployment using GitHub Secrets. Do not put InfinityFree database passwords directly in the repository.

The workflow does not upload:

- `.git`
- `.github`
- `.vscode`
- `.continue`
- `backupdatabase`
- `schema.sql`
- `DEPLOY_INFINITYFREE.md`

This helps avoid exposing development and database files publicly.

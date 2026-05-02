# Database Import Script

Automated script to download production database backups and import them into your local development environment.

## Overview

The `import-db-backup.sh` script streamlines the process of syncing production data to your local development environment by:

1. Connecting to the production server via SSH
2. Finding the latest database backup
3. Downloading it to your local machine
4. Extracting the compressed backup
5. Starting Docker development containers
6. Importing the database into your local MariaDB instance

## Prerequisites

- **SSH Access**: Valid SSH key (`.pem` format) with access to the production server
- **Docker**: Docker and Docker Compose installed and running
- **WSL2** (Windows users): Docker Desktop with WSL2 integration enabled
- **Disk Space**: At least 5GB free for typical backups (2GB compressed + 3GB extracted)
- **PuTTY Key Conversion** (if applicable): Convert `.ppk` keys to OpenSSH `.pem` format

## Setup

### 1. Convert SSH Key (if using PuTTY)

If you have a `.ppk` key, convert it to OpenSSH format:

```bash
# Install PuTTY tools
sudo apt-get update && sudo apt-get install putty-tools

# Convert .ppk to .pem
puttygen cnc_comm_private_key.ppk -O private-openssh -o cnc_comm_private_key.pem

# Set correct permissions
chmod 600 cnc_comm_private_key.pem
```

### 2. Configure Environment Variables

Add these variables to your `.env` file in the project root:

```bash
# Server connection for database backup
SERVER_IP=your.server.ip.address
SERVER_PORT=22
SERVER_USER=cncnet
SSH_KEY_PATH=/home/peter/cncnet-ladder-api-main/cnc_comm_private_key.pem
SERVER_BACKUP_PATH=/home/cncnet/ladder-new/backups
```

**Important**: Ensure your `.env` file uses **Unix line endings (LF)**, not Windows line endings (CRLF). If you edited the file in Windows, convert it:

```bash
# Fix line endings
sed -i 's/\r$//' .env
```

**Configuration Details:**

| Variable | Description | Required |
|----------|-------------|----------|
| `SERVER_IP` | Production server IP address | **Yes** |
| `SERVER_PORT` | SSH port number (typically 22) | **Yes** |
| `SERVER_USER` | SSH username | **Yes** |
| `SSH_KEY_PATH` | **Absolute path** to SSH private key (.pem) | **Yes** |
| `SERVER_BACKUP_PATH` | Directory containing backups on server | **Yes** |

All server connection variables are **required** if you want to download backups. You can skip server connection if using existing local backups.

### 3. Make Script Executable

```bash
chmod +x scripts/import-db-backup.sh
```

## Usage

**Important**: Always run from the project root directory:

```bash
cd /path/to/cncnet-ladder-api-main
```

### Full Import (Default)

Run the complete workflow from download to import:

```bash
./scripts/import-db-backup.sh
```

Answer `y` (or press Enter for defaults) to proceed through each step.

### Download Only

To download and extract the backup without importing:

```bash
./scripts/import-db-backup.sh
```

1. Confirm download and extraction steps (`y`)
2. Decline database import (`n`)

### Import Existing Backup

If you've already downloaded a backup and just want to import:

```bash
./scripts/import-db-backup.sh
```

1. Decline re-download (`n`) - uses existing file
2. Decline re-extract (`n`) - uses existing init.sql
3. Confirm Docker startup (`y`)
4. Confirm database import (`y`)

## Workflow Steps

The script performs 7 steps with confirmation prompts at each major stage:

### [1/7] Create Backups Directory
- Creates `./backups` directory if it doesn't exist
- Runs automatically without confirmation

### [2/7] Find Latest Backup
- **Prompt**: "Connect to server and find latest backup?"
- Searches for newest `mariadb_all_mysql_*.sql.gz` file
- Falls back to yesterday's backup if latest not found
- Example filename: `mariadb_all_mysql_20260426-000000.sql.gz`

### [3/7] Download Backup
- **Prompt**: "Download this backup?" or "Re-download this backup?"
- Downloads via SCP to `./backups/` directory
- Skips if file already exists locally (optional re-download)
- Shows file size for existing files

### [4/7] Extract Backup
- **Prompt**: "Extract backup to init.sql?" or "Re-extract the backup?"
- Decompresses `.sql.gz` to `./backups/init.sql`
- Skips if init.sql already exists (optional re-extract)
- Shows extracted file size

### [5/7] Start Docker Containers
- **Prompt**: "Start Docker containers?" (only if not running)
- Executes: `docker compose -f docker-compose.dev.yml up -d`
- Auto-detects if containers already running
- Starts all development services (app, MySQL, PHPMyAdmin)

### [6/7] Wait for MySQL
- Waits up to 60 seconds for MariaDB to accept connections
- Automatic health check with progress indicator
- No confirmation required

### [7/7] Import Database
- **Prompt**: "Proceed with database import?"
- Shows existing database statistics (if applicable)
- **Warning**: Drops existing database before import
- Displays real-time progress (database size growing)
- Estimated time: 10-30 minutes for 2GB+ files

## Import Progress

During import, you'll see:

```
Current database size: 247.52 MB
```

This updates every 5 seconds to show import progress.

## Expected Timings

For a typical 2GB compressed backup (~3GB extracted):

| Step | Duration |
|------|----------|
| Find backup | 2-5 seconds |
| Download | 2-10 minutes (depends on connection) |
| Extract | 30-60 seconds |
| Docker startup | 10-30 seconds (if not running) |
| MySQL ready | 5-20 seconds |
| **Database import** | **10-30 minutes** |

Total time: ~15-45 minutes for full workflow

## File Locations

```
cncnet-ladder-api-main/
├── scripts/
│   ├── import-db-backup.sh      # This script
│   └── DATABASE_IMPORT_README.md # This file
├── .env                          # Configuration (add SERVER_* variables here)
├── cnc_comm_private_key.pem     # SSH key (gitignored)
└── backups/                      # Created by script
    ├── mariadb_all_mysql_YYYYMMDD-HHMMSS.sql.gz  # Downloaded backup
    └── init.sql                  # Extracted SQL file
```

## Security Notes

- **SSH keys** (`.pem`, `.ppk`) are automatically ignored by git (see `.gitignore`)
- Never commit `.env` files containing server credentials
- SSH key permissions must be `600` or SSH will refuse to use them
- Database contains production data - treat with appropriate security

## Troubleshooting

### "Error: SERVER_IP not set in .env"

Add the required variables to your `.env` file (see Setup section above).

### "not accessible: No such file or directory..pem" or "Name or service not knownname"

This indicates **Windows line endings** in your `.env` file. Fix with:

```bash
# Remove Windows line endings
sed -i 's/\r$//' .env

# Verify it's fixed
cat .env | grep SERVER | cat -A
# Should NOT show ^M at end of lines
```

Also ensure `SSH_KEY_PATH` uses an **absolute path**, not relative:
- ✅ Correct: `/home/peter/cncnet-ladder-api-main/cnc_comm_private_key.pem`
- ❌ Wrong: `cnc_comm_private_key.pem`

### "Permission denied (publickey)"

Check:
1. SSH key path is correct in `.env`
2. Key has correct permissions: `chmod 600 cnc_comm_private_key.pem`
3. Key is in OpenSSH format (not `.ppk`)
4. Your public key is authorized on the server

### "Error: MySQL did not become ready in time"

- Check Docker containers: `docker ps`
- View MySQL logs: `docker logs dev_cncnet_ladder_mysql`
- Restart containers: `docker compose -f docker-compose.dev.yml restart`

### "Database import failed"

- Check disk space: `df -h`
- Verify SQL file integrity: `head -n 20 backups/init.sql`
- Check MySQL error logs: `docker logs dev_cncnet_ladder_mysql`
- Try importing a smaller subset manually for testing

### Import is very slow

This is normal for large databases. Progress indicator shows database size growing. Factors affecting speed:
- Disk type (SSD vs HDD)
- CPU performance
- WSL2 I/O overhead
- Number of indexes and foreign keys

### "No backup files found on server"

Check:
1. `SERVER_BACKUP_PATH` is correct in `.env`
2. You have read permissions on the backup directory
3. Backups follow naming pattern: `mariadb_all_mysql_*.sql.gz`
4. Try connecting manually: `ssh -i <key> <user>@<server> "ls <path>"`

## After Import

Once import completes successfully:

- **Web UI**: http://localhost:3000
- **PHPMyAdmin**: http://localhost:8080
  - Server: `dev_cncnet_ladder_mysql`
  - Username: `cncnet` (from `.env`)
  - Password: `cncnet` (from `.env`)
  - Database: `cncnet_api`

### Verify Import

```bash
# Check table count
docker exec dev_cncnet_ladder_mysql mysql -u cncnet -pcncnet -e \
  "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'cncnet_api';"

# Check database size
docker exec dev_cncnet_ladder_mysql mysql -u cncnet -pcncnet -e \
  "SELECT table_schema AS 'Database',
          COUNT(*) AS 'Tables',
          ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
   FROM information_schema.tables
   WHERE table_schema = 'cncnet_api'
   GROUP BY table_schema;"
```

## Alternative: Manual Import

If you prefer manual control:

```bash
# 1. Download backup
scp -i cnc_comm_private_key.pem user@server:/path/to/backup.sql.gz ./backups/

# 2. Extract
gunzip -c backups/backup.sql.gz > backups/init.sql

# 3. Start containers
docker compose -f docker-compose.dev.yml up -d

# 4. Import
docker exec -i dev_cncnet_ladder_mysql mariadb -u cncnet -pcncnet cncnet_api < backups/init.sql
```

## Environment Variables Reference

### Required in `.env` (for server connection):
- `SERVER_IP` - Production server IP address
- `SERVER_PORT` - SSH port number (e.g., `22`)
- `SERVER_USER` - SSH username (e.g., `cncnet`)
- `SSH_KEY_PATH` - Absolute path to SSH private key
- `SERVER_BACKUP_PATH` - Backup directory on server (e.g., `/home/cncnet/ladder-new/backups`)

**Note**: All server variables are required if downloading from server. You can skip server connection to use existing local backups.

### Used from `.env` for database:
- `MYSQL_DATABASE` (defaults to `cncnet_api`)
- `MYSQL_USER` (defaults to `cncnet`)
- `MYSQL_PASSWORD` (defaults to `cncnet`)

## Support

For issues related to:
- **Script functionality**: Check this README and Troubleshooting section
- **Docker/containers**: See `CLAUDE.md` Development Commands section
- **Database schema**: See `CLAUDE.md` Database Schema section
- **Laravel app**: See main project documentation

## License

Part of the CnCNet Ladder API project. See main project LICENSE file.

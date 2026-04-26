#!/bin/bash

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper function to prompt for confirmation
confirm() {
    local prompt="$1"
    local default="${2:-n}"
    local response

    if [ "$default" = "y" ]; then
        echo -e "${YELLOW}${prompt} [Y/n]${NC}"
    else
        echo -e "${YELLOW}${prompt} [y/N]${NC}"
    fi

    read -r response
    response="${response:-$default}"

    if [[ "$response" =~ ^[Yy]$ ]]; then
        return 0
    else
        return 1
    fi
}

# Load environment variables from .env
if [ ! -f .env ]; then
    echo -e "${RED}Error: .env file not found${NC}"
    exit 1
fi

# Source .env file (strip comments, empty lines, and handle special characters)
set -a
while IFS='=' read -r key value; do
    # Skip comments and empty lines
    [[ "$key" =~ ^#.*$ || -z "$key" ]] && continue
    # Remove leading/trailing whitespace and quotes
    key=$(echo "$key" | xargs)
    value=$(echo "$value" | xargs | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//")
    # Export the variable
    export "$key=$value"
done < .env
set +a

# Check required environment variables
if [ -z "$SERVER_IP" ]; then
    echo -e "${RED}Error: SERVER_IP not set in .env${NC}"
    echo "Add: SERVER_IP=your.server.ip.address"
    exit 1
fi

if [ -z "$SSH_KEY_PATH" ]; then
    echo -e "${RED}Error: SSH_KEY_PATH not set in .env${NC}"
    echo "Add: SSH_KEY_PATH=/path/to/your/key.pem"
    exit 1
fi

if [ -z "$SERVER_USER" ]; then
    echo -e "${RED}Error: SERVER_USER not set in .env${NC}"
    echo "Add: SERVER_USER=your_username"
    exit 1
fi

if [ -z "$SERVER_PORT" ]; then
    echo -e "${RED}Error: SERVER_PORT not set in .env${NC}"
    echo "Add: SERVER_PORT=22"
    exit 1
fi

if [ -z "$SERVER_BACKUP_PATH" ]; then
    echo -e "${RED}Error: SERVER_BACKUP_PATH not set in .env${NC}"
    echo "Add: SERVER_BACKUP_PATH=/path/to/backups"
    exit 1
fi

# Configuration
BACKUP_DIR="./backups"
SSH_CMD="ssh -i $SSH_KEY_PATH -p $SERVER_PORT"
SCP_CMD="scp -i $SSH_KEY_PATH -P $SERVER_PORT"
MYSQL_CONTAINER="dev_cncnet_ladder_mysql"
DB_NAME="${MYSQL_DATABASE:-cncnet_api}"
DB_USER="${MYSQL_USER:-cncnet}"
DB_PASS="${MYSQL_PASSWORD:-cncnet}"

echo -e "${BLUE}=== CnCNet Ladder Database Import Script ===${NC}\n"

# Step 1: Create backups directory
echo -e "${YELLOW}[1/7] Creating backups directory...${NC}"
mkdir -p "$BACKUP_DIR"
echo -e "${GREEN}✓ Done${NC}\n"

# Step 2: Find latest backup on server
echo -e "${BLUE}[2/7] Find latest backup on server${NC}"
echo "Server: $SERVER_USER@$SERVER_IP"
echo "Path: $SERVER_BACKUP_PATH"
echo ""

SKIP_SERVER=false
if ! confirm "Connect to server and find latest backup?" "y"; then
    echo -e "${YELLOW}Skipping server connection.${NC}"
    SKIP_SERVER=true

    # Check if there's a local backup we can use
    LOCAL_BACKUPS=$(ls -t "$BACKUP_DIR"/mariadb_all_mysql_*.sql.gz 2>/dev/null | head -1 || echo "")

    if [ -n "$LOCAL_BACKUPS" ]; then
        BACKUP_FILENAME=$(basename "$LOCAL_BACKUPS")
        LOCAL_BACKUP_PATH="$LOCAL_BACKUPS"
        echo -e "${GREEN}Found existing local backup: $BACKUP_FILENAME${NC}"

        if ! confirm "Use this backup?" "y"; then
            echo -e "${YELLOW}No backup selected. Exiting.${NC}"
            exit 0
        fi
    else
        echo -e "${RED}No local backups found in $BACKUP_DIR${NC}"
        echo "Please download a backup first or re-run and connect to server."
        exit 0
    fi
fi

if [ "$SKIP_SERVER" = false ]; then
    echo -e "${YELLOW}Finding latest backup...${NC}"

    # Try to find the latest .sql.gz file
    LATEST_BACKUP=$($SSH_CMD "$SERVER_USER@$SERVER_IP" \
        "ls -t $SERVER_BACKUP_PATH/mariadb_all_mysql_*.sql.gz 2>/dev/null | head -n 1 || echo ''")

    # If not found, try yesterday's backup
    if [ -z "$LATEST_BACKUP" ]; then
        echo -e "${YELLOW}No backup found with latest timestamp, trying yesterday's backup...${NC}"
        YESTERDAY=$(date -d "yesterday" +%Y%m%d)
        YESTERDAY_BACKUP="$SERVER_BACKUP_PATH/mariadb_all_mysql_${YESTERDAY}-000000.sql.gz"

        # Check if yesterday's backup exists
        if $SSH_CMD "$SERVER_USER@$SERVER_IP" "test -f $YESTERDAY_BACKUP"; then
            LATEST_BACKUP=$YESTERDAY_BACKUP
            echo -e "${GREEN}✓ Found yesterday's backup${NC}"
        else
            echo -e "${RED}Error: No backup files found${NC}"
            echo "Tried:"
            echo "  - Latest: $SERVER_BACKUP_PATH/mariadb_all_mysql_*.sql.gz"
            echo "  - Yesterday: $YESTERDAY_BACKUP"
            exit 1
        fi
    fi

    BACKUP_FILENAME=$(basename "$LATEST_BACKUP")
    echo -e "${GREEN}✓ Found backup: $BACKUP_FILENAME${NC}\n"
fi

# Step 3: Download backup from server
if [ "$SKIP_SERVER" = true ]; then
    echo -e "${BLUE}[3/7] Download backup from server${NC}"
    LOCAL_SIZE=$(du -h "$LOCAL_BACKUP_PATH" | cut -f1)
    echo -e "${YELLOW}Skipped - using local backup ($LOCAL_SIZE)${NC}\n"
else
    echo -e "${BLUE}[3/7] Download backup from server${NC}"
    LOCAL_BACKUP_PATH="$BACKUP_DIR/$BACKUP_FILENAME"

    if [ -f "$LOCAL_BACKUP_PATH" ]; then
        LOCAL_SIZE=$(du -h "$LOCAL_BACKUP_PATH" | cut -f1)
        echo -e "${GREEN}Backup already exists locally: $BACKUP_FILENAME ($LOCAL_SIZE)${NC}"

        if ! confirm "Re-download this backup?" "n"; then
            echo -e "${YELLOW}Using existing backup file.${NC}\n"
        else
            echo -e "${YELLOW}Downloading to: $LOCAL_BACKUP_PATH${NC}"
            $SCP_CMD "$SERVER_USER@$SERVER_IP:$LATEST_BACKUP" "$LOCAL_BACKUP_PATH"
            echo -e "${GREEN}✓ Download complete${NC}\n"
        fi
    else
        echo "Backup file: $BACKUP_FILENAME"

        if ! confirm "Download this backup?" "y"; then
            echo -e "${YELLOW}Skipping download. Exiting.${NC}"
            exit 0
        fi

        echo -e "${YELLOW}Downloading to: $LOCAL_BACKUP_PATH${NC}"
        $SCP_CMD "$SERVER_USER@$SERVER_IP:$LATEST_BACKUP" "$LOCAL_BACKUP_PATH"
        echo -e "${GREEN}✓ Download complete${NC}\n"
    fi
fi

# Step 4: Unzip backup
SQL_FILE="$BACKUP_DIR/init.sql"
echo -e "${BLUE}[4/7] Extract backup${NC}"

if [ -f "$SQL_FILE" ]; then
    SQL_SIZE=$(du -h "$SQL_FILE" | cut -f1)
    echo -e "${GREEN}Extracted SQL file already exists: init.sql ($SQL_SIZE)${NC}"

    if ! confirm "Re-extract the backup?" "n"; then
        echo -e "${YELLOW}Using existing SQL file.${NC}\n"
    else
        echo -e "${YELLOW}Decompressing $BACKUP_FILENAME to $SQL_FILE...${NC}"
        gunzip -c "$LOCAL_BACKUP_PATH" > "$SQL_FILE"
        SQL_SIZE=$(du -h "$SQL_FILE" | cut -f1)
        echo -e "${GREEN}✓ Extracted to $SQL_FILE ($SQL_SIZE)${NC}\n"
    fi
else
    if ! confirm "Extract backup to init.sql?" "y"; then
        echo -e "${YELLOW}Skipping extraction. Exiting.${NC}"
        exit 0
    fi

    echo -e "${YELLOW}Decompressing $BACKUP_FILENAME to $SQL_FILE...${NC}"
    gunzip -c "$LOCAL_BACKUP_PATH" > "$SQL_FILE"
    SQL_SIZE=$(du -h "$SQL_FILE" | cut -f1)
    echo -e "${GREEN}✓ Extracted to $SQL_FILE ($SQL_SIZE)${NC}\n"
fi

# Step 5: Start Docker containers
echo -e "${BLUE}[5/7] Start Docker development environment${NC}"

# Check if containers are already running
if docker ps --format '{{.Names}}' | grep -q "^${MYSQL_CONTAINER}$"; then
    echo -e "${GREEN}MySQL container is already running${NC}"
    SKIP_DOCKER_START=true
else
    echo "Docker compose will start all development containers"

    if ! confirm "Start Docker containers?" "y"; then
        echo -e "${YELLOW}Skipping Docker startup. Exiting.${NC}"
        exit 0
    fi

    echo -e "${YELLOW}Starting containers...${NC}"
    docker compose -f docker-compose.dev.yml up -d
    echo -e "${GREEN}✓ Containers started${NC}\n"
    SKIP_DOCKER_START=false
fi

# Step 6: Wait for MySQL to be ready
echo -e "${BLUE}[6/7] Wait for MySQL${NC}"

if [ "$SKIP_DOCKER_START" = true ]; then
    # Quick check if MySQL is ready
    if docker exec "$MYSQL_CONTAINER" mariadb -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1" &> /dev/null; then
        echo -e "${GREEN}✓ MySQL is ready${NC}\n"
    else
        echo -e "${RED}MySQL container is running but not responding${NC}"
        echo "Waiting for MySQL to become ready..."
        MAX_TRIES=60
        COUNT=0

        until docker exec "$MYSQL_CONTAINER" mariadb -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1" &> /dev/null; do
            COUNT=$((COUNT + 1))
            if [ $COUNT -ge $MAX_TRIES ]; then
                echo -e "${RED}Error: MySQL did not become ready in time${NC}"
                exit 1
            fi
            echo -n "."
            sleep 2
        done
        echo -e "\n${GREEN}✓ MySQL is ready${NC}\n"
    fi
else
    echo -e "${YELLOW}Waiting for MySQL to become ready...${NC}"
    MAX_TRIES=60
    COUNT=0

    until docker exec "$MYSQL_CONTAINER" mariadb -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1" &> /dev/null; do
        COUNT=$((COUNT + 1))
        if [ $COUNT -ge $MAX_TRIES ]; then
            echo -e "${RED}Error: MySQL did not become ready in time${NC}"
            echo -e "${YELLOW}Checking logs...${NC}"
            docker logs "$MYSQL_CONTAINER" --tail 20
            exit 1
        fi
        echo -n "."
        sleep 2
    done

    echo -e "\n${GREEN}✓ MySQL is ready${NC}\n"
fi

# Step 7: Import database
echo -e "${BLUE}[7/7] Import database${NC}"

# Get current database info if exists
DB_EXISTS=$(docker exec "$MYSQL_CONTAINER" mariadb -u"$DB_USER" -p"$DB_PASS" \
    -e "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '$DB_NAME';" 2>/dev/null | grep -v "SCHEMA_NAME" || echo "")

if [ -n "$DB_EXISTS" ]; then
    DB_SIZE=$(docker exec "$MYSQL_CONTAINER" mariadb -u"$DB_USER" -p"$DB_PASS" -e \
        "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS Size_MB
         FROM information_schema.tables WHERE table_schema = '$DB_NAME';" 2>/dev/null | tail -n 1)
    DB_TABLES=$(docker exec "$MYSQL_CONTAINER" mariadb -u"$DB_USER" -p"$DB_PASS" -e \
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_NAME';" 2>/dev/null | tail -n 1)

    echo -e "${YELLOW}Warning: Database '$DB_NAME' already exists${NC}"
    echo "  Tables: $DB_TABLES"
    echo "  Size: ${DB_SIZE} MB"
    echo ""
    echo -e "${RED}This will DROP the existing database and import fresh data${NC}"
else
    echo "Database '$DB_NAME' does not exist yet"
    echo "A fresh database will be created and imported"
fi

echo ""
SQL_SIZE=$(du -h "$SQL_FILE" | cut -f1)
echo "SQL file: $SQL_FILE ($SQL_SIZE)"
echo "Estimated import time: 10-30 minutes for large databases"
echo ""

if ! confirm "Proceed with database import?" "n"; then
    echo -e "${YELLOW}Skipping database import.${NC}"
    echo ""
    echo -e "${GREEN}=== Setup Complete (Import Skipped) ===${NC}"
    echo "Backup downloaded and extracted to: $SQL_FILE"
    echo "Docker containers are running"
    echo "To import later, run the import section manually or re-run this script"
    exit 0
fi

echo -e "${YELLOW}Starting import...${NC}"
echo "Started at: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# Drop and recreate database for clean import
echo "Dropping and recreating database..."
docker exec -i "$MYSQL_CONTAINER" mariadb -u"$DB_USER" -p"$DB_PASS" -e "DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME;" 2>&1 | grep -v "mariadb: \[Warning\]" || true

# Import with progress indicator
echo "Importing SQL file..."
(
    docker exec -i "$MYSQL_CONTAINER" mariadb -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SQL_FILE" 2>&1 | grep -v "mariadb: \[Warning\]" || true
) &

IMPORT_PID=$!

# Show progress while importing
while kill -0 $IMPORT_PID 2>/dev/null; do
    # Check database size
    DB_SIZE=$(docker exec "$MYSQL_CONTAINER" mariadb -u"$DB_USER" -p"$DB_PASS" -e "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS Size_MB FROM information_schema.tables WHERE table_schema = '$DB_NAME';" 2>/dev/null | tail -n 1)
    echo -ne "\rCurrent database size: ${DB_SIZE} MB  "
    sleep 5
done

wait $IMPORT_PID
IMPORT_EXIT_CODE=$?

echo ""
echo "Completed at: $(date '+%Y-%m-%d %H:%M:%S')"

if [ $IMPORT_EXIT_CODE -eq 0 ]; then
    echo -e "${GREEN}✓ Database import successful!${NC}\n"

    # Show final database stats
    echo -e "${BLUE}Database Statistics:${NC}"
    docker exec "$MYSQL_CONTAINER" mariadb -u"$DB_USER" -p"$DB_PASS" -e "
        SELECT
            table_schema AS 'Database',
            COUNT(*) AS 'Tables',
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
        FROM information_schema.tables
        WHERE table_schema = '$DB_NAME'
        GROUP BY table_schema;
    " 2>&1 | grep -v "mariadb: \[Warning\]"

    echo ""
    echo -e "${GREEN}=== Import Complete ===${NC}"
    echo "You can now access the application at http://localhost:3000"
    echo "PHPMyAdmin available at http://localhost:8080"
else
    echo -e "${RED}✗ Database import failed${NC}"
    exit 1
fi

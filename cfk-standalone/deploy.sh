#!/bin/bash

# Christmas for Kids - Production Deployment Script
# Safely deploys changes to live website using credentials from .env file

set -e  # Exit on error

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== CFK Production Deployment ===${NC}\n"

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo -e "${RED}ERROR: .env file not found!${NC}"
    echo "Please create .env file with SSH credentials."
    echo "See .env.example for template."
    exit 1
fi

# Load environment variables from .env
export $(cat .env | grep -v '^#' | grep -v '^[[:space:]]*$' | xargs)

# Verify required SSH credentials are set
if [ -z "$SSH_HOST" ] || [ -z "$SSH_USER" ] || [ -z "$SSH_PASSWORD" ] || [ -z "$SSH_REMOTE_PATH" ]; then
    echo -e "${RED}ERROR: Missing SSH credentials in .env file!${NC}"
    echo "Required variables: SSH_HOST, SSH_USER, SSH_PASSWORD, SSH_REMOTE_PATH"
    exit 1
fi

# Check if sshpass is installed
if ! command -v sshpass &> /dev/null; then
    echo -e "${RED}ERROR: sshpass not installed!${NC}"
    echo "Install with: brew install sshpass"
    exit 1
fi

# Get list of files to deploy (default to changed files)
if [ $# -eq 0 ]; then
    echo -e "${YELLOW}No specific files provided. Deploying common changed files...${NC}\n"
    FILES="assets/css/styles.css pages/children.php"
else
    FILES="$@"
fi

echo -e "${GREEN}Files to deploy:${NC}"
for file in $FILES; do
    if [ -f "$file" ]; then
        echo "  ✓ $file"
    else
        echo -e "  ${RED}✗ $file (not found!)${NC}"
        exit 1
    fi
done
echo ""

# Create deployment package
DEPLOY_DATE=$(date +%Y%m%d-%H%M%S)
PACKAGE_NAME="cfk-deploy-${DEPLOY_DATE}.tar.gz"
PACKAGE_PATH="/tmp/${PACKAGE_NAME}"

echo -e "${GREEN}Creating deployment package...${NC}"
tar -czf "$PACKAGE_PATH" $FILES
echo -e "  ✓ Package created: $PACKAGE_PATH"
echo -e "  Size: $(ls -lh $PACKAGE_PATH | awk '{print $5}')\n"

# Upload to production (use base home directory, not user-specific)
echo -e "${GREEN}Uploading to production...${NC}"
# Extract base username without suffix (a4409d26_1 -> a4409d26)
BASE_USER=$(echo "$SSH_USER" | cut -d'_' -f1)
sshpass -p "$SSH_PASSWORD" scp -o StrictHostKeyChecking=no -P ${SSH_PORT:-22} \
    "$PACKAGE_PATH" \
    "${SSH_USER}@${SSH_HOST}:/home/${BASE_USER}/"

if [ $? -eq 0 ]; then
    echo -e "  ${GREEN}✓ Upload successful${NC}\n"
else
    echo -e "  ${RED}✗ Upload failed${NC}"
    exit 1
fi

# Extract on production server
echo -e "${GREEN}Extracting files on production...${NC}"
# Extract base username without suffix (a4409d26_1 -> a4409d26)
BASE_USER=$(echo "$SSH_USER" | cut -d'_' -f1)
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no -p ${SSH_PORT:-22} \
    "${SSH_USER}@${SSH_HOST}" \
    "cd ${SSH_REMOTE_PATH} && tar -xzf /home/${BASE_USER}/${PACKAGE_NAME} && echo 'Extraction complete' && rm /home/${BASE_USER}/${PACKAGE_NAME}"

if [ $? -eq 0 ]; then
    echo -e "  ${GREEN}✓ Files deployed successfully${NC}\n"
else
    echo -e "  ${RED}✗ Deployment failed${NC}"
    exit 1
fi

# Run composer install on production (for autoloader)
echo -e "${GREEN}Running composer install on production...${NC}"
sshpass -p "$SSH_PASSWORD" ssh -o StrictHostKeyChecking=no -p ${SSH_PORT:-22} \
    "${SSH_USER}@${SSH_HOST}" \
    "cd ${SSH_REMOTE_PATH} && composer install --no-dev --optimize-autoloader --no-interaction 2>&1"

if [ $? -eq 0 ]; then
    echo -e "  ${GREEN}✓ Composer dependencies installed${NC}\n"
else
    echo -e "  ${YELLOW}⚠ Composer install failed (may need to run manually)${NC}\n"
fi

# Cleanup local package
rm "$PACKAGE_PATH"

# Verify deployment
echo -e "${GREEN}Verifying deployment...${NC}"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "https://cforkids.org/?page=children")
if [ "$HTTP_CODE" = "200" ]; then
    echo -e "  ${GREEN}✓ Website responding (HTTP $HTTP_CODE)${NC}\n"
else
    echo -e "  ${YELLOW}⚠ Website returned HTTP $HTTP_CODE${NC}\n"
fi

echo -e "${GREEN}=== Deployment Complete ===${NC}"
echo -e "Deployed at: $(date)"
echo -e "Files: $FILES\n"

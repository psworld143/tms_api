#!/bin/bash

# Database Setup API Test Script
# Usage: ./test-api.sh

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# API Base URL
BASE_URL="http://localhost/tms_api/super-admin/database-setup"

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}   TMS Database Setup API Tests${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

# Test 1: Create a carrier database
echo -e "${YELLOW}Test 1: Creating carrier database for '2GO Logistics'...${NC}"
echo ""
response=$(curl -s -X POST "${BASE_URL}/clone-database.php" \
  -H "Content-Type: application/json" \
  -d '{"carrier_name": "2GO Logistics"}')

if echo "$response" | grep -q '"status":"success"'; then
    echo -e "${GREEN}✓ Success: Carrier database created${NC}"
    echo "$response" | python -m json.tool
else
    echo -e "${RED}✗ Error: Failed to create database${NC}"
    echo "$response" | python -m json.tool
fi

echo ""
echo -e "${BLUE}================================================${NC}"
echo ""

# Wait a moment
sleep 2

# Test 2: List all carrier databases
echo -e "${YELLOW}Test 2: Listing all carrier databases...${NC}"
echo ""
response=$(curl -s "${BASE_URL}/list-carrier-databases.php")

if echo "$response" | grep -q '"status":"success"'; then
    echo -e "${GREEN}✓ Success: Retrieved carrier databases${NC}"
    echo "$response" | python -m json.tool
else
    echo -e "${RED}✗ Error: Failed to retrieve databases${NC}"
    echo "$response" | python -m json.tool
fi

echo ""
echo -e "${BLUE}================================================${NC}"
echo ""

# Test 3: Try to create the same database again (should fail)
echo -e "${YELLOW}Test 3: Attempting to create duplicate database (should fail)...${NC}"
echo ""
response=$(curl -s -X POST "${BASE_URL}/clone-database.php" \
  -H "Content-Type: application/json" \
  -d '{"carrier_name": "2GO Logistics"}')

if echo "$response" | grep -q "already exists"; then
    echo -e "${GREEN}✓ Success: Duplicate prevention working${NC}"
    echo "$response" | python -m json.tool
else
    echo -e "${RED}✗ Warning: Duplicate database was created (unexpected)${NC}"
    echo "$response" | python -m json.tool
fi

echo ""
echo -e "${BLUE}================================================${NC}"
echo ""

# Test 4: Delete the carrier database
echo -e "${YELLOW}Test 4: Deleting carrier database 'tms_2go_logistics'...${NC}"
echo ""
response=$(curl -s -X DELETE "${BASE_URL}/delete-carrier-database.php" \
  -H "Content-Type: application/json" \
  -d '{"database_name": "tms_2go_logistics"}')

if echo "$response" | grep -q '"status":"success"'; then
    echo -e "${GREEN}✓ Success: Database deleted${NC}"
    echo "$response" | python -m json.tool
else
    echo -e "${RED}✗ Error: Failed to delete database${NC}"
    echo "$response" | python -m json.tool
fi

echo ""
echo -e "${BLUE}================================================${NC}"
echo ""

# Test 5: Verify deletion
echo -e "${YELLOW}Test 5: Verifying database deletion...${NC}"
echo ""
response=$(curl -s "${BASE_URL}/list-carrier-databases.php")

if ! echo "$response" | grep -q "tms_2go_logistics"; then
    echo -e "${GREEN}✓ Success: Database successfully removed${NC}"
    echo "$response" | python -m json.tool
else
    echo -e "${RED}✗ Error: Database still exists${NC}"
    echo "$response" | python -m json.tool
fi

echo ""
echo -e "${BLUE}================================================${NC}"
echo -e "${GREEN}All tests completed!${NC}"
echo -e "${BLUE}================================================${NC}"


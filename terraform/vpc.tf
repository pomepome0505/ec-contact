# ================================================================
# VPC
# ================================================================
resource "aws_vpc" "main" {
  cidr_block           = var.vpc_cidr
  enable_dns_hostnames = true
  enable_dns_support   = true

  tags = {
    Name        = "${var.project_name}-${var.environment}-vpc"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

# ================================================================
# Internet Gateway
# ================================================================
resource "aws_internet_gateway" "main" {
  vpc_id = aws_vpc.main.id

  tags = {
    Name        = "${var.project_name}-${var.environment}-igw"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
  }
}

# ================================================================
# Public Subnet
# ================================================================
# AZ1 (ap-northeast-1a)
resource "aws_subnet" "public_1a" {
  vpc_id                  = aws_vpc.main.id
  cidr_block              = "10.0.1.0/24"
  availability_zone       = var.availability_zones[0]
  map_public_ip_on_launch = true

  tags = {
    Name        = "${var.project_name}-${var.environment}-public-subnet-1a"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
    Type        = "public"
  }
}

# AZ2 (ap-northeast-1c)
resource "aws_subnet" "public_1c" {
  vpc_id                  = aws_vpc.main.id
  cidr_block              = "10.0.2.0/24"
  availability_zone       = var.availability_zones[1]
  map_public_ip_on_launch = true

  tags = {
    Name        = "${var.project_name}-${var.environment}-public-subnet-1c"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
    Type        = "public"
  }
}

# ================================================================
# Private Subnet (Fargate用)
# ================================================================
# AZ1 (ap-northeast-1a)
resource "aws_subnet" "private_fargate_1a" {
  vpc_id                  = aws_vpc.main.id
  cidr_block              = "10.0.11.0/24"
  availability_zone       = var.availability_zones[0]
  map_public_ip_on_launch = false

  tags = {
    Name        = "${var.project_name}-${var.environment}-private-fargate-subnet-1a"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
    Type        = "private"
    Purpose     = "fargate"
  }
}

# ================================================================
# Private Subnet (RDS用)
# ================================================================
# AZ1 (ap-northeast-1a)
resource "aws_subnet" "private_rds_1a" {
  vpc_id                  = aws_vpc.main.id
  cidr_block              = "10.0.12.0/24"
  availability_zone       = var.availability_zones[0]
  map_public_ip_on_launch = false

  tags = {
    Name        = "${var.project_name}-${var.environment}-private-rds-subnet-1a"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
    Type        = "private"
    Purpose     = "rds"
  }
}

# AZ2 (ap-northeast-1c)
resource "aws_subnet" "private_rds_1c" {
  vpc_id                  = aws_vpc.main.id
  cidr_block              = "10.0.22.0/24"
  availability_zone       = var.availability_zones[1]
  map_public_ip_on_launch = false

  tags = {
    Name        = "${var.project_name}-${var.environment}-private-rds-subnet-1c"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
    Type        = "private"
    Purpose     = "rds"
  }
}

# ================================================================
# Public Route Table
# ================================================================
resource "aws_route_table" "public" {
  vpc_id = aws_vpc.main.id

  tags = {
    Name        = "${var.project_name}-${var.environment}-public-rt"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
    Type        = "public"
  }
}

# Public Route (インターネットへのデフォルトルート)
resource "aws_route" "public_internet_gateway" {
  route_table_id         = aws_route_table.public.id
  destination_cidr_block = "0.0.0.0/0"
  gateway_id             = aws_internet_gateway.main.id
}

# Public Subnet (AZ1) の関連付け
resource "aws_route_table_association" "public_1a" {
  subnet_id      = aws_subnet.public_1a.id
  route_table_id = aws_route_table.public.id
}

# Public Subnet (AZ2) の関連付け
resource "aws_route_table_association" "public_1c" {
  subnet_id      = aws_subnet.public_1c.id
  route_table_id = aws_route_table.public.id
}

# ================================================================
# Private Route Table (Fargate用)
# ================================================================
resource "aws_route_table" "private_fargate" {
  vpc_id = aws_vpc.main.id

  tags = {
    Name        = "${var.project_name}-${var.environment}-private-fargate-rt"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
    Type        = "private"
    Purpose     = "fargate"
  }
}

# Private Fargate Subnet (AZ1) の関連付け
resource "aws_route_table_association" "private_fargate_1a" {
  subnet_id      = aws_subnet.private_fargate_1a.id
  route_table_id = aws_route_table.private_fargate.id
}

# ================================================================
# Private Route Table (RDS用)
# ================================================================
resource "aws_route_table" "private_rds" {
  vpc_id = aws_vpc.main.id

  tags = {
    Name        = "${var.project_name}-${var.environment}-private-rds-rt"
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "terraform"
    Type        = "private"
    Purpose     = "rds"
  }
}

# Private RDS Subnet (AZ1) の関連付け
resource "aws_route_table_association" "private_rds_1a" {
  subnet_id      = aws_subnet.private_rds_1a.id
  route_table_id = aws_route_table.private_rds.id
}

# Private RDS Subnet (AZ2) の関連付け
resource "aws_route_table_association" "private_rds_1c" {
  subnet_id      = aws_subnet.private_rds_1c.id
  route_table_id = aws_route_table.private_rds.id
}

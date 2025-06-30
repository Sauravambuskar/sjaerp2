-- SJA Foundation Investment Management Platform Database Schema

-- Drop tables if they exist (for clean installation)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS investments;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS wallets;
DROP TABLE IF EXISTS kyc_docs;
DROP TABLE IF EXISTS nominees;
DROP TABLE IF EXISTS referrals;
DROP TABLE IF EXISTS earnings;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS investment_plans;
SET FOREIGN_KEY_CHECKS = 1;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'client') NOT NULL DEFAULT 'client',
    parent_id INT NULL,
    level INT DEFAULT 1,
    photo VARCHAR(255) NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    email_verified_at TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Clients table (extended user profile)
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    dob DATE NULL,
    gender ENUM('male', 'female', 'other') NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    pincode VARCHAR(20) NULL,
    country VARCHAR(100) DEFAULT 'India',
    occupation VARCHAR(100) NULL,
    annual_income DECIMAL(15,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Investment Plans table
CREATE TABLE investment_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    min_amount DECIMAL(15,2) NOT NULL,
    max_amount DECIMAL(15,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    duration_months INT NOT NULL,
    lock_period_months INT NOT NULL,
    early_withdrawal_penalty DECIMAL(5,2) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Investments table
CREATE TABLE investments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    start_date DATE NOT NULL,
    maturity_date DATE NOT NULL,
    duration_months INT NOT NULL,
    status ENUM('active', 'matured', 'withdrawn', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES investment_plans(id)
) ENGINE=InnoDB;

-- Transactions table
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    investment_id INT NULL,
    type ENUM('deposit', 'withdrawal', 'interest', 'commission', 'penalty', 'admin_adjustment') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    fee DECIMAL(15,2) DEFAULT 0.00,
    net_amount DECIMAL(15,2) NOT NULL,
    description TEXT NULL,
    reference_id VARCHAR(100) NULL,
    payment_method VARCHAR(50) NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    withdrawal_type ENUM('regular', 'emergency') NULL,
    withdrawal_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (investment_id) REFERENCES investments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Wallets table
CREATE TABLE wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    balance DECIMAL(15,2) DEFAULT 0.00,
    total_deposits DECIMAL(15,2) DEFAULT 0.00,
    total_withdrawals DECIMAL(15,2) DEFAULT 0.00,
    total_earnings DECIMAL(15,2) DEFAULT 0.00,
    last_updated TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- KYC Documents table
CREATE TABLE kyc_docs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    aadhaar VARCHAR(255) NULL,
    pan VARCHAR(255) NULL,
    passport VARCHAR(255) NULL,
    signature VARCHAR(255) NULL,
    address_proof VARCHAR(255) NULL,
    passbook VARCHAR(255) NULL,
    profile_photo VARCHAR(255) NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    remarks TEXT NULL,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Nominees table
CREATE TABLE nominees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    dob DATE NULL,
    relation VARCHAR(50) NOT NULL,
    blood_group VARCHAR(10) NULL,
    phone VARCHAR(20) NULL,
    photo VARCHAR(255) NULL,
    address TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Referrals table
CREATE TABLE referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    referred_id INT NOT NULL UNIQUE,
    level INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Earnings table
CREATE TABLE earnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    source_id INT NULL,
    source_type ENUM('investment', 'referral') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    type ENUM('interest', 'commission', 'bonus') NOT NULL,
    level INT NULL,
    status ENUM('pending', 'processed', 'cancelled') DEFAULT 'pending',
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (source_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('system', 'transaction', 'kyc', 'investment', 'referral', 'birthday', 'promotion') NOT NULL,
    read_status BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert default investment plans
INSERT INTO investment_plans (name, description, min_amount, max_amount, interest_rate, duration_months, lock_period_months, early_withdrawal_penalty, status) VALUES
('Standard Plan', 'Our standard investment plan with 10% annual return', 10000.00, 10000000.00, 10.00, 11, 11, 3.00, 'active'),
('Premium Plan', 'Higher returns for premium investors', 50000.00, 10000000.00, 12.00, 11, 11, 3.00, 'active'),
('Elite Plan', 'For elite investors with maximum returns', 100000.00, 10000000.00, 15.00, 11, 11, 3.00, 'active');

-- Create triggers for wallet updates

-- Create wallet when user is created
DELIMITER $$
CREATE TRIGGER create_wallet_after_user_insert
AFTER INSERT ON users
FOR EACH ROW
BEGIN
    IF NEW.role = 'client' THEN
        INSERT INTO wallets (user_id) VALUES (NEW.id);
    END IF;
END$$
DELIMITER ;

-- Update wallet balance after transaction is completed
DELIMITER $$
CREATE TRIGGER update_wallet_after_transaction
AFTER UPDATE ON transactions
FOR EACH ROW
BEGIN
    DECLARE wallet_balance DECIMAL(15,2);
    
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        -- Get current wallet balance
        SELECT balance INTO wallet_balance FROM wallets WHERE user_id = NEW.user_id;
        
        -- Update wallet based on transaction type
        IF NEW.type = 'deposit' OR NEW.type = 'interest' OR NEW.type = 'commission' THEN
            UPDATE wallets 
            SET balance = balance + NEW.net_amount,
                total_deposits = CASE WHEN NEW.type = 'deposit' THEN total_deposits + NEW.net_amount ELSE total_deposits END,
                total_earnings = CASE WHEN NEW.type IN ('interest', 'commission') THEN total_earnings + NEW.net_amount ELSE total_earnings END
            WHERE user_id = NEW.user_id;
        ELSEIF NEW.type = 'withdrawal' OR NEW.type = 'penalty' THEN
            UPDATE wallets 
            SET balance = balance - NEW.net_amount,
                total_withdrawals = CASE WHEN NEW.type = 'withdrawal' THEN total_withdrawals + NEW.net_amount ELSE total_withdrawals END
            WHERE user_id = NEW.user_id;
        ELSEIF NEW.type = 'admin_adjustment' THEN
            UPDATE wallets 
            SET balance = balance + NEW.net_amount
            WHERE user_id = NEW.user_id;
        END IF;
    END IF;
END$$
DELIMITER ;

-- Create indexes for performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_parent_id ON users(parent_id);
CREATE INDEX idx_investments_user_id ON investments(user_id);
CREATE INDEX idx_investments_status ON investments(status);
CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_status ON transactions(status);
CREATE INDEX idx_referrals_referrer_id ON referrals(referrer_id);
CREATE INDEX idx_earnings_user_id ON earnings(user_id);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_read_status ON notifications(read_status); 
-- RepubliCAIXA - estrutura oficial inicial
CREATE DATABASE IF NOT EXISTS republicaixa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE republicaixa;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE republics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    invite_code VARCHAR(12) NOT NULL UNIQUE,
    created_by INT UNSIGNED NOT NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_republics_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE republic_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    republic_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role ENUM('admin','member') NOT NULL DEFAULT 'member',
    joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT uq_republic_member UNIQUE (republic_id,user_id),
    CONSTRAINT fk_members_republic FOREIGN KEY (republic_id) REFERENCES republics(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE expense_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    icon VARCHAR(80) NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE expenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    republic_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    due_date DATE NULL,
    split_type ENUM('equal','custom','percentage') NOT NULL DEFAULT 'equal',
    receipt_path VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_expenses_republic FOREIGN KEY (republic_id) REFERENCES republics(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_expenses_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_expenses_category FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE expense_shares (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expense_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    amount_due DECIMAL(10,2) NOT NULL,
    percentage DECIMAL(5,2) NULL,
    status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
    paid_at DATETIME NULL,
    CONSTRAINT uq_expense_share UNIQUE (expense_id,member_id),
    CONSTRAINT fk_shares_expense FOREIGN KEY (expense_id) REFERENCES expenses(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_shares_member FOREIGN KEY (member_id) REFERENCES republic_members(id) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expense_share_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    proof_path VARCHAR(255) NULL,
    status ENUM('pending','confirmed','rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_share FOREIGN KEY (expense_share_id) REFERENCES expense_shares(id) ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE TABLE cash_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    republic_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    type ENUM('income','expense') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    transaction_date DATE NOT NULL,
    related_expense_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cash_republic FOREIGN KEY (republic_id) REFERENCES republics(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_cash_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_cash_expense FOREIGN KEY (related_expense_id) REFERENCES expenses(id) ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE INDEX idx_expenses_republic_date ON expenses (republic_id, expense_date);
CREATE INDEX idx_expenses_due_date ON expenses (due_date);
CREATE INDEX idx_cash_republic_date ON cash_transactions (republic_id, transaction_date);

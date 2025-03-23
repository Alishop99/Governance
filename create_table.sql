CREATE TABLE IF NOT EXISTS gabu_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wallet_address VARCHAR(255) NOT NULL UNIQUE,
    vote_choice ENUM('yes', 'no') NOT NULL,
    voting_power DOUBLE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
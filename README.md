# GABU Voting System

This is a simple web-based voting system for GABU token holders on the Solana blockchain.  
It allows users to connect their Phantom wallet and vote to burn 10 million GABU tokens.

## Requirements
- PHP server with MySQL (e.g. XAMPP, cPanel, or WSL with Apache/MySQL)
- Phantom Wallet browser extension
- GABU token on Solana blockchain

## Setup Instructions
1. Clone this repository or upload the files to your server.
2. Import the `create_table.sql` file into your MySQL database.
3. Update `db.php` with your own database credentials.
4. Open `vote-burn.php` in your browser and connect your Phantom wallet.
5. Ensure your wallet holds at least **100 GABU** to vote.

## Notes
- Each wallet can only vote **once**.
- Voting power is based on the amount of GABU held.
- Voting results are shown in real-time.

## License
This project is MIT licensed.
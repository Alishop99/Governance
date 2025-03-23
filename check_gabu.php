<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Connect to Solflare & Check $GABU</title>
  <script src="https://unpkg.com/@solana/web3.js@1.73.2/lib/index.iife.min.js"></script>
  <style>
    body { font-family: sans-serif; padding: 20px; }
    button { padding: 10px 15px; font-size: 16px; }
    #status { margin-top: 20px; }
  </style>
</head>
<body>

  <h2>🔗 Connect Solflare Wallet</h2>
  <button id="connectBtn">Connect Wallet</button>
  <div id="status">Status: Not connected</div>
  <div id="walletInfo"></div>
  <div id="gabuBalance"></div>

  <script>
    const connectBtn = document.getElementById('connectBtn');
    const status = document.getElementById('status');
    const walletInfo = document.getElementById('walletInfo');
    const gabuBalance = document.getElementById('gabuBalance');

    const GABU_MINT = "AqkM3S3zdXVkAB2vmk8czpJMCo3zSq8XqAMakaEouEiH"; // GABU Token Address
    const TOKEN_PROGRAM_ID = new solanaWeb3.PublicKey("TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA");

    // Ganti koneksi ke Serum agar tidak error
    const connection = new solanaWeb3.Connection("https://solana-api.projectserum.com");

    async function connectWallet() {
      try {
        if (!window.solflare?.isSolflare) {
          alert("Solflare Wallet not found. Please install it.");
          return;
        }

        await window.solflare.connect();
        const publicKey = window.solflare.publicKey;

        status.textContent = "✅ Connected to Solflare";
        walletInfo.innerHTML = `👛 Wallet: <strong>${publicKey.toBase58()}</strong>`;

        const tokenAccounts = await connection.getParsedTokenAccountsByOwner(
          publicKey,
          { programId: TOKEN_PROGRAM_ID }
        );

        let found = false;

        tokenAccounts.value.forEach(({ account }) => {
          const info = account.data.parsed.info;
          if (info.mint === GABU_MINT) {
            const balance = info.tokenAmount.uiAmount;
            gabuBalance.innerHTML = `💰 $GABU Balance: <strong>${balance}</strong>`;
            found = true;
          }
        });

        if (!found) {
          gabuBalance.innerHTML = "❌ $GABU token not found in wallet.";
        }

      } catch (err) {
        console.error(err);
        status.textContent = "❌ Failed to connect: " + err.message;
      }
    }

    connectBtn.addEventListener('click', connectWallet);
  </script>
</body>
</html>

<?php
include '/..../..../.../db.php';
$vote_choice = $_POST['vote_choice'] ?? '';
$wallet_address = $_POST['wallet_address'] ?? '';
$voting_power = $_POST['voting_power'] ?? 0;

if ($vote_choice && $wallet_address && $voting_power >= 100) {
    // Gunakan prepared statement untuk keamanan
    $stmt = $conn->prepare("SELECT * FROM gabu_votes WHERE wallet_address = ?");
    $stmt->bind_param("s", $wallet_address);
    $stmt->execute();
    $check = $stmt->get_result();

    if ($check->num_rows > 0) {
        echo "Wallet has already voted.";
    } else {
        $stmt = $conn->prepare("INSERT INTO gabu_votes (wallet_address, vote_choice, voting_power) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $wallet_address, $vote_choice, $voting_power);
        echo $stmt->execute() ? "Vote submitted." : "Error submitting vote.";
    }
    exit;
}

$result = $conn->query("SELECT vote_choice, SUM(voting_power) as total FROM gabu_votes GROUP BY vote_choice");
$yes = 0; $no = 0;
while ($row = $result->fetch_assoc()) {
    if ($row['vote_choice'] == 'yes') $yes = $row['total'];
    if ($row['vote_choice'] == 'no')  $no = $row['total'];
}
$total_votes = $yes + $no;
$yes_pct = $total_votes > 0 ? round(($yes / $total_votes) * 100, 2) : 0;
$no_pct = $total_votes > 0 ? round(($no / $total_votes) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>GABU Burn Vote</title>
  <!-- Coba versi lain dari @solana/web3.js yang lebih stabil -->
  <script src="https://cdn.jsdelivr.net/npm/@solana/web3.js@1.78.0/dist/web3.min.js"></script>
  <style>
    body { font-family: Arial; max-width: 600px; margin: auto; padding: 20px; }
    button { padding: 10px; margin: 5px 0; width: 100%; }
    .wallet-options { margin-top: 20px; }
    .result { margin-top: 30px; padding: 20px; background: #f0f0f0; border-radius: 5px; }
  </style>
</head>
<body>
<h2>Vote to Burn 10M GABU</h2>
<p>Do you agree to burn 10,000,000 GABU tokens?</p>

<div class="wallet-options">
  <p>Select your wallet:</p>
  <button id="choosePhantom">Use Phantom</button>
  <button id="chooseSolflare">Use Solflare</button>
</div>

<button id="connectButton" style="display:none;">Connect Wallet</button>
<button id="voteYesButton" style="display:none;">Vote Yes</button>
<button id="voteNoButton" style="display:none;">Vote No</button>

<div class="result">
  <h3>Current Vote Results</h3>
  <p>Yes: <?= $yes ?> GABU (<?= $yes_pct ?>%)</p>
  <p>No: <?= $no ?> GABU (<?= $no_pct ?>%)</p>
</div>

<script>
// Fungsi untuk memuat library Solana
async function loadSolanaLibrary() {
    if (typeof window.SolanaWeb3 === 'undefined') {
        console.error('SolanaWeb3 is not defined. Attempting to load library...');
        return false;
    }
    console.log('SolanaWeb3 loaded successfully:', window.SolanaWeb3);
    return true;
}

// Deklarasi variabel Solana
let Connection, PublicKey, clusterApiUrl;
if (window.SolanaWeb3) {
    ({ Connection, PublicKey, clusterApiUrl } = window.SolanaWeb3);
}

// Fungsi untuk menunggu dompet diinisialisasi
async function waitForWallet() {
    return new Promise((resolve) => {
        const checkInterval = setInterval(() => {
            if (window.solana || window.solflare) {
                clearInterval(checkInterval);
                resolve(true);
            }
        }, 100);
        setTimeout(() => {
            clearInterval(checkInterval);
            resolve(false);
        }, 5000);
    });
}

// Fungsi untuk menghubungkan dompet
async function connectWallet(walletType) {
    let walletProvider = null;
    let walletName = walletType === 'phantom' ? 'Phantom' : 'Solflare';

    // Tunggu hingga dompet diinisialisasi
    console.log('Waiting for wallet to initialize...');
    const walletDetected = await waitForWallet();
    if (!walletDetected) {
        alert(`No Solana wallet detected. Please ensure ${walletName} is installed and enabled.`);
        return;
    }

    // Pilih provider dompet
    if (walletType === 'phantom' && window.solana?.isPhantom) {
        walletProvider = window.solana;
    } else if (walletType === 'solflare' && (window.solflare?.isSolflare || window.solana?.isSolflare)) {
        walletProvider = window.solflare || window.solana;
    } else {
        alert(`${walletName} not detected or not supported.`);
        return;
    }

    // Pastikan library Solana dimuat
    const libraryLoaded = await loadSolanaLibrary();
    if (!libraryLoaded) {
        alert('Failed to load Solana library. Please refresh the page or check your internet connection.');
        return;
    }

    try {
        // Pastikan dompet belum terkoneksi, jika sudah, disconnect dulu
        if (walletProvider.isConnected) {
            await walletProvider.disconnect();
        }

        // Tambahkan event listener untuk mendeteksi koneksi
        let publicKey = null;
        walletProvider.on('connect', (key) => {
            console.log('Wallet connected via event:', key);
            publicKey = key;
        });

        // Coba koneksi
        console.log(`Attempting to connect with ${walletName}...`);
        await walletProvider.connect();

        // Tunggu hingga publicKey tersedia
        for (let i = 0; i < 10; i++) {
            if (publicKey || walletProvider.publicKey) {
                break;
            }
            await new Promise(resolve => setTimeout(resolve, 500)); // Tunggu 500ms
        }

        // Ambil publicKey
        const wallet = publicKey?.toString() || walletProvider.publicKey?.toString();
        if (!wallet) {
            throw new Error('No publicKey received from wallet. Please try again.');
        }

        console.log(`${walletName} connected: ${wallet}`);

        // Sembunyikan tombol pemilihan dan connect, tampilkan tombol voting
        document.querySelector('.wallet-options').style.display = 'none';
        document.getElementById('connectButton').style.display = 'none';
        document.getElementById('voteYesButton').style.display = 'block';
        document.getElementById('voteNoButton').style.display = 'block';

        // Koneksi ke Solana dan cek kepemilikan GABU
        const conn = new Connection(clusterApiUrl('mainnet-beta'));
        const mint = new PublicKey('AqkM3S3zdXVkAB2vmk8czpJMCo3zSq8XqAMakaEouEiH');
        const accs = await conn.getParsedTokenAccountsByOwner(new PublicKey(wallet), { mint });
        if (accs.value.length === 0) return alert('You do not own GABU.');

        const gabuAmount = accs.value[0].account.data.parsed.info.tokenAmount.uiAmount;
        if (gabuAmount < 100) return alert('Minimum 100 GABU required to vote.');

        const form = new FormData();
        form.append('wallet_address', wallet);
        form.append('voting_power', gabuAmount);

        // Event handler untuk tombol voting
        document.getElementById('voteYesButton').onclick = () => { form.set('vote_choice', 'yes'); sendVote(form); };
        document.getElementById('voteNoButton').onclick = () => { form.set('vote_choice', 'no'); sendVote(form); };

    } catch (e) {
        console.error(`Error connecting with ${walletName}:`, e);
        alert(`Failed to connect with ${walletName}: ${e.message}. Ensure it is installed, unlocked, and set to Mainnet.`);
    }
}

// Fungsi untuk mengirim vote
async function sendVote(form) {
    try {
        const res = await fetch('vote-burn.php', { method: 'POST', body: form });
        const txt = await res.text();
        alert(txt);
        location.reload();
    } catch (e) {
        console.error('Error submitting vote:', e);
        alert('Failed to submit vote. Please try again.');
    }
}

// Pasang event listener untuk tombol, terlepas dari pemuatan library
document.getElementById('choosePhantom').onclick = () => connectWallet('phantom');
document.getElementById('chooseSolflare').onclick = () => connectWallet('solflare');
</script>
</body>
</html>

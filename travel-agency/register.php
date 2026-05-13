<?php
session_start();
require_once 'config/db.php';

$blad = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imie   = trim($_POST['name']);
    $email  = trim($_POST['email']);
    $haslo  = $_POST['password'];
    $haslo2 = $_POST['password_confirm'];

    if (empty($imie) || empty($email) || empty($haslo)) {
        $blad = 'Wypełnij wszystkie pola.';
    } elseif ($haslo !== $haslo2) {
        $blad = 'Hasła nie są zgodne.';
    } elseif (strlen($haslo) < 6) {
        $blad = 'Hasło musi mieć minimum 6 znaków.';
    } else {
        // Sprawdź czy podany email jest już w bazie
        $sprawdzEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $sprawdzEmail->bind_param("s", $email);
        $sprawdzEmail->execute();
        $sprawdzEmail->store_result();

        if ($sprawdzEmail->num_rows > 0) {
            $blad = 'Ten e-mail jest już zajęty.';
        } else {
            // Zaszyfruj hasło i dodaj użytkownika do bazy
            $hasloZaszyfrowane = password_hash($haslo, PASSWORD_DEFAULT);
            $dodajUzytkownika  = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $dodajUzytkownika->bind_param("sss", $imie, $email, $hasloZaszyfrowane);
            $dodajUzytkownika->execute();
            header('Location: login.php?registered=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja | TravelAgency</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f1623; color: #e2e8f0; display: flex; flex-direction: column; min-height: 100vh; }

        .strona { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
        .karta { background: #1a2236; border: 1px solid #2a3450; padding: 2.25rem 2rem; border-radius: 14px; width: 100%; max-width: 420px; }
        .karta-logo { text-align: center; font-size: 1.4rem; font-weight: 700; color: #fff; margin-bottom: 0.25rem; }
        .karta-logo span { color: #4a90e2; }
        .karta-opis { text-align: center; font-size: 0.88rem; color: #718096; margin-bottom: 1.75rem; }

        label { display: block; font-size: 0.83rem; color: #a0aec0; margin-bottom: 5px; margin-top: 14px; }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%; padding: 10px 13px;
            background: #0f1623; border: 1px solid #2a3450;
            border-radius: 8px; font-size: 0.95rem; color: #e2e8f0;
            transition: border-color .2s;
        }
        input:focus { outline: none; border-color: #4a90e2; }

        button { width: 100%; margin-top: 1.5rem; padding: 11px; background: #4a90e2; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; }
        button:hover { background: #357abd; }

        .blad { background: rgba(192,57,43,0.15); border: 1px solid rgba(192,57,43,0.35); color: #e07070; padding: 10px 13px; border-radius: 8px; font-size: 0.88rem; margin-bottom: 0.5rem; }

        .link { text-align: center; margin-top: 1.25rem; font-size: 0.88rem; color: #718096; }
        .link a { color: #4a90e2; text-decoration: none; }
        .link a:hover { text-decoration: underline; }

        footer { background: #1a2236; border-top: 1px solid #2a3450; text-align: center; padding: 1rem; font-size: 0.82rem; color: #4a5568; }
    </style>
</head>
<body>

<div class="strona">
    <div class="karta">
        <div class="karta-logo">✈ <span>Travel</span>Agency</div>
        <p class="karta-opis">Utwórz konto i zarezerwuj wymarzone wakacje</p>

        <?php if ($blad): ?>
            <div class="blad"><?= htmlspecialchars($blad) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Imię i nazwisko</label>
            <input type="text" name="name" placeholder="Jan Kowalski" required>

            <label>Adres e-mail</label>
            <input type="email" name="email" placeholder="jan@example.com" required>

            <label>Hasło</label>
            <input type="password" name="password" placeholder="Min. 6 znaków" required>

            <label>Powtórz hasło</label>
            <input type="password" name="password_confirm" placeholder="Powtórz hasło" required>

            <button type="submit">Zarejestruj się</button>
        </form>

        <div class="link">Masz już konto? <a href="login.php">Zaloguj się</a></div>
    </div>
</div>

<footer>
    <div style="display:flex;gap:1.5rem;justify-content:center;padding:1rem;">
        <a href="statute.html">Regulamin</a>
        <a href="contact.html">Kontakt</a>
    </div>
</footer>

</body>
</html>

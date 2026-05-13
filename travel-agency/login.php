<?php
session_start();
require_once 'config/db.php';

// Jeśli użytkownik jest już zalogowany – przekieruj na stronę główną
if (isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$blad    = '';
$sukces  = '';

// Pokaż komunikat po pomyślnej rejestracji
if (isset($_GET['registered'])) {
    $sukces = 'Konto utworzone! Możesz się teraz zalogować.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email  = trim($_POST['email']);
    $haslo  = $_POST['password'];

    if (empty($email) || empty($haslo)) {
        $blad = 'Wypełnij wszystkie pola.';
    } else {
        // Pobierz użytkownika z bazy po adresie email
        $zapytanie = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $zapytanie->bind_param("s", $email);
        $zapytanie->execute();
        $wynik      = $zapytanie->get_result();
        $uzytkownik = $wynik->fetch_assoc();

        // Sprawdź czy email istnieje i czy hasło się zgadza
        if ($uzytkownik && password_verify($haslo, $uzytkownik['password'])) {
            $_SESSION['user_id']   = $uzytkownik['id'];
            $_SESSION['user_name'] = $uzytkownik['name'];

            // Zapisz email w ciasteczku na 7 dni jeśli zaznaczono "zapamiętaj mnie"
            if (isset($_POST['remember'])) {
                setcookie('user_email', $email, time() + (7 * 24 * 60 * 60), '/');
            }

            header('Location: index.html');
            exit;
        } else {
            $blad = 'Błędny e-mail lub hasło.';
        }
    }
}

// Wczytaj email z ciasteczka jeśli istnieje
$zapisanyEmail = $_COOKIE['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie | TravelAgency</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f1623; color: #e2e8f0; display: flex; flex-direction: column; min-height: 100vh; }

        .strona { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
        .karta { background: #1a2236; border: 1px solid #2a3450; padding: 2.25rem 2rem; border-radius: 14px; width: 100%; max-width: 420px; }
        .karta-logo { text-align: center; font-size: 1.4rem; font-weight: 700; color: #fff; margin-bottom: 0.25rem; }
        .karta-logo span { color: #4a90e2; }
        .karta-opis { text-align: center; font-size: 0.88rem; color: #718096; margin-bottom: 1.75rem; }

        label { display: block; font-size: 0.83rem; color: #a0aec0; margin-bottom: 5px; margin-top: 14px; }
        input[type="email"], input[type="password"] { width: 100%; padding: 10px 13px; background: #0f1623; border: 1px solid #2a3450; border-radius: 8px; font-size: 0.95rem; color: #e2e8f0; transition: border-color .2s; }
        input[type="email"]:focus, input[type="password"]:focus { outline: none; border-color: #4a90e2; }

        .pamietaj { display: flex; align-items: center; gap: 8px; margin-top: 14px; font-size: 0.88rem; color: #a0aec0; }
        .pamietaj input[type="checkbox"] { accent-color: #4a90e2; width: 15px; height: 15px; }

        button { width: 100%; margin-top: 1.5rem; padding: 11px; background: #4a90e2; color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background .2s; }
        button:hover { background: #357abd; }

        .blad   { background: rgba(192,57,43,0.15); border: 1px solid rgba(192,57,43,0.35); color: #e07070; padding: 10px 13px; border-radius: 8px; font-size: 0.88rem; margin-bottom: 0.5rem; }
        .sukces { background: rgba(30,132,73,0.15);  border: 1px solid rgba(30,132,73,0.35);  color: #68d391; padding: 10px 13px; border-radius: 8px; font-size: 0.88rem; margin-bottom: 0.5rem; }

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
        <p class="karta-opis">Zaloguj się, aby zarządzać rezerwacjami</p>

        <?php if ($sukces): ?>
            <div class="sukces"><?= htmlspecialchars($sukces) ?></div>
        <?php endif; ?>
        <?php if ($blad): ?>
            <div class="blad"><?= htmlspecialchars($blad) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Adres e-mail</label>
            <input type="email" name="email" value="<?= htmlspecialchars($zapisanyEmail) ?>" placeholder="jan@example.com" required>

            <label>Hasło</label>
            <input type="password" name="password" placeholder="Twoje hasło" required>

            <div class="pamietaj">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember" style="margin: 0;">Zapamiętaj mnie</label>
            </div>

            <button type="submit">Zaloguj się</button>
        </form>

        <div class="link">Nie masz konta? <a href="register.php">Zarejestruj się</a></div>
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

<?php
session_start();
require_once 'config/db.php';

// Jeśli użytkownik nie jest zalogowany – wyślij do logowania
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$blad   = '';
$sukces = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $noweImie  = trim($_POST['name']             ?? '');
    $nowyEmail = trim($_POST['email']            ?? '');
    $noweHaslo = $_POST['password']              ?? '';
    $haslo2    = $_POST['password_confirm']      ?? '';

    if (empty($noweImie) || empty($nowyEmail)) {
        $blad = 'Imię i e-mail nie mogą być puste.';
    } elseif (!filter_var($nowyEmail, FILTER_VALIDATE_EMAIL)) {
        $blad = 'Podaj prawidłowy adres e-mail.';
    } elseif ($noweHaslo !== '' && strlen($noweHaslo) < 6) {
        $blad = 'Nowe hasło musi mieć minimum 6 znaków.';
    } elseif ($noweHaslo !== '' && $noweHaslo !== $haslo2) {
        $blad = 'Hasła nie są zgodne.';
    } else {
        // Sprawdź czy nowy email nie należy już do innego konta
        $sprawdzEmail = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $sprawdzEmail->bind_param("si", $nowyEmail, $_SESSION['user_id']);
        $sprawdzEmail->execute();
        $sprawdzEmail->store_result();

        if ($sprawdzEmail->num_rows > 0) {
            $blad = 'Ten adres e-mail jest już zajęty.';
        } else {
            if ($noweHaslo !== '') {
                // Aktualizuj imię, email i hasło
                $hasloZaszyfrowane = password_hash($noweHaslo, PASSWORD_DEFAULT);
                $aktualizuj = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                $aktualizuj->bind_param("sssi", $noweImie, $nowyEmail, $hasloZaszyfrowane, $_SESSION['user_id']);
            } else {
                // Aktualizuj tylko imię i email
                $aktualizuj = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $aktualizuj->bind_param("ssi", $noweImie, $nowyEmail, $_SESSION['user_id']);
            }
            $aktualizuj->execute();
            $_SESSION['user_name'] = $noweImie;
            $sukces = 'Dane zostały zaktualizowane.';
        }
    }
}

// Pobierz dane zalogowanego użytkownika z bazy
$pobierzDane = $conn->prepare("SELECT name, email, created_at FROM users WHERE id = ?");
$pobierzDane->bind_param("i", $_SESSION['user_id']);
$pobierzDane->execute();
$uzytkownik = $pobierzDane->get_result()->fetch_assoc();

// Pierwsza litera imienia do awatara
$litera      = mb_strtoupper(mb_substr($uzytkownik['name'], 0, 1));
$dataRejestr = date('d.m.Y', strtotime($uzytkownik['created_at']));
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil | TravelAgency</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #0f1623;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header { background: #1a2236; border-bottom: 1px solid #2a3450; position: sticky; top: 0; z-index: 100; }
        nav { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem; height: 62px; display: flex; align-items: center; justify-content: space-between; }
        .nav-logo { font-size: 1.2rem; font-weight: 700; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .nav-logo span { color: #4a90e2; }
        .nav-links { display: flex; gap: 2rem; list-style: none; }
        .nav-links a { color: #a0aec0; text-decoration: none; font-size: 0.95rem; transition: color .2s; }
        .nav-links a:hover, .nav-links a.active { color: #fff; }
        .nav-btn { background: #4a90e2; color: #fff; border: none; padding: 8px 18px; border-radius: 8px; font-size: 0.9rem; cursor: pointer; text-decoration: none; transition: background .2s; }
        .nav-btn:hover { background: #357abd; }

        main { flex: 1; max-width: 600px; margin: 3.5rem auto; padding: 0 1.5rem 4rem; width: 100%; }

        .naglowek-profilu {
            background: #1a2236;
            border: 1px solid #2a3450;
            border-radius: 14px;
            padding: 2.25rem 2rem;
            display: flex;
            align-items: center;
            gap: 1.75rem;
            margin-bottom: 1.25rem;
        }
        .awatar {
            width: 78px; height: 78px;
            border-radius: 50%;
            background: #4a90e2;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; font-weight: 700; color: #fff;
            flex-shrink: 0;
            border: 3px solid #2a5298;
        }
        .imie-profilu  { font-size: 1.35rem; font-weight: 700; color: #fff; }
        .email-profilu { font-size: 0.88rem; color: #718096; margin-top: 0.3rem; }
        .odznaka {
            margin-top: 0.65rem;
            display: inline-block;
            background: rgba(74,144,226,0.15);
            border: 1px solid rgba(74,144,226,0.35);
            color: #4a90e2;
            font-size: 0.78rem;
            padding: 3px 12px;
            border-radius: 20px;
        }

        .karta {
            background: #1a2236;
            border: 1px solid #2a3450;
            border-radius: 14px;
            padding: 1.75rem 2rem;
            margin-bottom: 1.25rem;
        }
        .karta h3 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #4a90e2;
            margin-bottom: 1.25rem;
        }

        .wiersz-danych { display: flex; justify-content: space-between; align-items: center; padding: 0.8rem 0; border-bottom: 1px solid #2a3450; }
        .wiersz-danych:last-child { border-bottom: none; }
        .etykieta { font-size: 0.85rem; color: #718096; }
        .wartosc  { font-size: 0.93rem; color: #e2e8f0; font-weight: 500; }

        .btn-edytuj { background: none; border: 1px solid #2a3450; color: #a0aec0; padding: 6px 14px; border-radius: 8px; font-size: 0.83rem; cursor: pointer; transition: all .2s; }
        .btn-edytuj:hover { border-color: #4a90e2; color: #fff; }

        .formularz-edycji { display: none; }
        .formularz-edycji.widoczny { display: block; }

        label { display: block; font-size: 0.83rem; color: #a0aec0; margin-bottom: 5px; margin-top: 14px; }
        label:first-of-type { margin-top: 0; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 10px 13px;
            background: #0f1623; border: 1px solid #2a3450;
            border-radius: 8px; font-size: 0.93rem; color: #e2e8f0;
            transition: border-color .2s;
        }
        input:focus { outline: none; border-color: #4a90e2; }
        .podpowiedz { font-size: 0.78rem; color: #4a5568; margin-top: 4px; }
        .btn-zapisz { margin-top: 1.25rem; padding: 10px 24px; background: #4a90e2; color: #fff; border: none; border-radius: 8px; font-size: 0.93rem; font-weight: 600; cursor: pointer; transition: background .2s; }
        .btn-zapisz:hover { background: #357abd; }

        .komunikat { padding: 10px 13px; border-radius: 8px; font-size: 0.88rem; margin-bottom: 1.25rem; }
        .komunikat-sukces { background: rgba(30,132,73,0.15);  border: 1px solid rgba(30,132,73,0.35);  color: #68d391; }
        .komunikat-blad   { background: rgba(192,57,43,0.15); border: 1px solid rgba(192,57,43,0.35); color: #e07070; }

        footer { background: #1a2236; border-top: 1px solid #2a3450; text-align: center; padding: 1rem; font-size: 0.82rem; color: #4a5568; }
        footer a { color: #4a90e2; text-decoration: none; }
    </style>
</head>
<body>

<header>
    <nav>
        <a href="index.html" class="nav-logo">✈ <span>Travel</span>Agency</a>
        <ul class="nav-links">
            <li><a href="index.html">Strona główna</a></li>
            <li><a href="offers.html">Oferty</a></li>
            <li><a href="contact.html">Kontakt</a></li>
            <li><a href="statute.html">Regulamin</a></li>
            <li><a href="profile.php" class="active">Profil</a></li>
        </ul>
        <a href="logout.php" class="nav-btn">Wyloguj</a>
    </nav>
</header>

<main>

    <?php if ($sukces): ?>
        <div class="komunikat komunikat-sukces"><?= htmlspecialchars($sukces) ?></div>
    <?php endif; ?>
    <?php if ($blad): ?>
        <div class="komunikat komunikat-blad"><?= htmlspecialchars($blad) ?></div>
    <?php endif; ?>

    <!-- Awatar i podstawowe info o koncie -->
    <div class="naglowek-profilu">
        <div class="awatar"><?= htmlspecialchars($litera) ?></div>
        <div>
            <div class="imie-profilu"><?= htmlspecialchars($uzytkownik['name']) ?></div>
            <div class="email-profilu"><?= htmlspecialchars($uzytkownik['email']) ?></div>
            <span class="odznaka">Klient standardowy</span>
        </div>
    </div>

    <!-- Podgląd danych konta z przyciskiem edycji -->
    <div class="karta">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
            <h3 style="margin-bottom:0;">Dane konta</h3>
            <button class="btn-edytuj" onclick="toggleEdycja()">Edytuj dane</button>
        </div>

        <div>
            <div class="wiersz-danych">
                <span class="etykieta">Imię i nazwisko</span>
                <span class="wartosc"><?= htmlspecialchars($uzytkownik['name']) ?></span>
            </div>
            <div class="wiersz-danych">
                <span class="etykieta">Adres e-mail</span>
                <span class="wartosc"><?= htmlspecialchars($uzytkownik['email']) ?></span>
            </div>
            <div class="wiersz-danych">
                <span class="etykieta">Konto założone</span>
                <span class="wartosc"><?= $dataRejestr ?></span>
            </div>
        </div>

        <!-- Formularz edycji danych – domyślnie ukryty -->
        <form method="POST" class="formularz-edycji <?= $blad ? 'widoczny' : '' ?>" id="formularzEdycji">
            <label for="name">Imię i nazwisko</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($uzytkownik['name']) ?>" required>

            <label for="email">Adres e-mail</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($uzytkownik['email']) ?>" required>

            <label for="password">Nowe hasło</label>
            <input type="password" id="password" name="password" placeholder="Zostaw puste, by nie zmieniać">
            <p class="podpowiedz">Minimum 6 znaków. Pozostaw puste jeśli nie chcesz zmieniać hasła.</p>

            <label for="password_confirm">Powtórz nowe hasło</label>
            <input type="password" id="password_confirm" name="password_confirm" placeholder="Powtórz nowe hasło">

            <button type="submit" class="btn-zapisz">Zapisz zmiany</button>
        </form>
    </div>

</main>

<footer>
    <div style="max-width:600px;margin:0 auto;padding:1.5rem;display:flex;gap:1.5rem;justify-content:center;">
        <a href="index.html">Strona główna</a>
        <a href="offers.html">Oferty</a>
        <a href="contact.html">Kontakt</a>
        <a href="statute.html">Regulamin</a>
    </div>
</footer>

<script>
    // Pokaż lub ukryj formularz edycji i zmień tekst przycisku
    function toggleEdycja() {
        var formularz = document.getElementById('formularzEdycji');
        var przycisk  = document.querySelector('.btn-edytuj');
        var widoczny  = formularz.classList.toggle('widoczny');
        przycisk.textContent = widoczny ? 'Anuluj' : 'Edytuj dane';
    }
</script>

</body>
</html>

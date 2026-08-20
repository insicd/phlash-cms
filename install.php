<?php
/**
 * Installer Phlash — da lanciare una volta sola dal browser.
 */
define('PHLASH_ROOT', __DIR__);

$lock = PHLASH_ROOT . '/install.lock';
if (is_file($lock) && is_file(PHLASH_ROOT . '/config.php')) {
    header('Location: index.php');
    exit;
}

$errors = [];
$ok = [];
$step = 'form';

if (PHP_VERSION_ID < 70400) {
    $errors[] = 'Serve PHP 7.4 o superiore (trovato ' . PHP_VERSION . ').';
}
if (!extension_loaded('pdo_mysql')) {
    $errors[] = 'Manca l’estensione PHP pdo_mysql.';
}
if (!extension_loaded('mbstring')) {
    $errors[] = 'Manca l’estensione PHP mbstring.';
}

function inst_h($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function php_lit(string $s): string
{
    return var_export($s, true);
}

function run_sql(PDO $pdo, string $sql): void
{
    $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;
    $parts = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($parts as $stmt) {
        if ($stmt === '' || stripos($stmt, 'SET NAMES') === 0) {
            continue;
        }
        $pdo->exec($stmt);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim((string) ($_POST['db_host'] ?? 'localhost'));
    $name = trim((string) ($_POST['db_name'] ?? ''));
    $user = trim((string) ($_POST['db_user'] ?? ''));
    $pass = (string) ($_POST['db_pass'] ?? '');
    $site = trim((string) ($_POST['site_name'] ?? 'Phlash'));
    $base = rtrim(trim((string) ($_POST['base_url'] ?? '')), '/');
    $adminUser = trim((string) ($_POST['admin_user'] ?? 'admin'));
    $adminEmail = trim((string) ($_POST['admin_email'] ?? ''));
    $adminPass = (string) ($_POST['admin_pass'] ?? '');

    if ($name === '' || $user === '') {
        $errors[] = 'Database e utente MySQL sono obbligatori.';
    }
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $adminUser)) {
        $errors[] = 'Username admin: 3-20 caratteri alfanumerici.';
    }
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email admin non valida.';
    }
    if (strlen($adminPass) < 8) {
        $errors[] = 'Password admin: almeno 8 caratteri.';
    }
    if (!is_writable(PHLASH_ROOT)) {
        $errors[] = 'La directory del progetto non è scrivibile: non posso creare config.php. Imposta i permessi (chmod 755 o 775) e riprova.';
    }

    if (!$errors) {
        try {
            $dsn = 'mysql:host=' . $host . ';dbname=' . $name . ';charset=utf8mb4';
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (Throwable $e) {
            $errors[] = 'Connessione MySQL fallita: ' . $e->getMessage();
            $pdo = null;
        }
    }

    if (!$errors && $pdo) {
        try {
            $schema = file_get_contents(PHLASH_ROOT . '/schema.sql');
            run_sql($pdo, $schema);

            $cfg = "<?php\n"
                . "if (basename(\$_SERVER['SCRIPT_FILENAME'] ?? '') === 'config.php') { http_response_code(403); exit; }\n"
                . "define('PHLASH_DB_HOST', " . php_lit($host) . ");\n"
                . "define('PHLASH_DB_NAME', " . php_lit($name) . ");\n"
                . "define('PHLASH_DB_USER', " . php_lit($user) . ");\n"
                . "define('PHLASH_DB_PASS', " . php_lit($pass) . ");\n"
                . "define('PHLASH_DB_CHARSET', 'utf8mb4');\n"
                . "define('PHLASH_BASE_URL', " . php_lit($base) . ");\n";
            if (file_put_contents(PHLASH_ROOT . '/config.php', $cfg) === false) {
                throw new RuntimeException('Impossibile scrivere config.php');
            }

            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $st = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash, role, karma, created_at, status)
                 VALUES (?, ?, ?, ?, 10, NOW(), ?)'
            );
            $st->execute([$adminUser, $adminEmail, $hash, 'admin', 'active']);
            $adminId = (int) $pdo->lastInsertId();

            $topics = [
                ['Notizie', 'notizie', 'Segnalazioni e fatti del giorno', 'newspaper', 1],
                ['Tecnologia', 'tecnologia', 'Software, hardware, rete', 'microchip', 2],
                ['Scienza', 'scienza', 'Ricerca, spazio, natura', 'flask', 3],
                ['Cultura', 'cultura', 'Libri, cinema, idee', 'book-open', 4],
                ['Chiedi a Phlash', 'chiedi-a-phlash', 'Domande alla comunità', 'circle-question', 5],
                ['Giochi', 'giochi', 'Videogiochi e tavolo', 'gamepad', 6],
                ['Società', 'societa', 'Politica, diritti, vita in rete', 'users', 7],
            ];
            $insT = $pdo->prepare('INSERT INTO topics (name, slug, description, icon, sort_order) VALUES (?,?,?,?,?)');
            foreach ($topics as $t) {
                $insT->execute($t);
            }

            $settings = [
                'site_name' => $site !== '' ? $site : 'Phlash',
                'site_tagline' => 'Notizie per nerd, cose che contano.',
                'stories_per_page' => '10',
                'promote_threshold' => '5',
                'allow_anon_comments' => '1',
                'registration_open' => '1',
                'comment_threshold' => '1',
                'timezone' => 'Europe/Rome',
                'custom_css' => '',
                'ip_salt' => bin2hex(random_bytes(8)),
            ];
            $insS = $pdo->prepare('INSERT INTO settings (k, v) VALUES (?, ?)');
            foreach ($settings as $k => $v) {
                $insS->execute([$k, $v]);
            }

            $topicTech = (int) $pdo->query("SELECT id FROM topics WHERE slug='tecnologia'")->fetchColumn();
            $body = <<<'MD'
C’era una volta un sito verde, rumoroso e magnificamente rozzo. Si chiamava Slashdot: titoli da barra colorata, dipartimenti sarcastici, thread infiniti e il **Codardo Anonimo** che diceva la sua senza chiedere permesso. Poi arrivarono i social news in stile Pligg: la coda delle storie, i voti della comunità, la promozione in homepage.

**Phlash** prende entrambe le idee e le mette su un hosting shared, in PHP e MySQL, senza Composer, senza npm, senza rituali di deploy. Solo file da caricare e un database da creare.

## Cosa puoi fare qui

- **Scrivere post lunghi**, non solo incollare un URL. Il campo testo è il cuore della storia: argomenta, cita, racconta.
- **Proporre una storia** se sei registrato. Finisce in *In arrivo* e sale in homepage quando raccoglie abbastanza voti (o quando un admin la pubblica).
- **Commentare anche da anonimo**, come su Slashdot. Serve solo una somma di due numeri, per tenere lontani i bot più pigri.
- **Votare** storie e commenti se hai un account. Il karma non è una religione, ma un termometro.

Il markup delle storie è un Markdown minimale: titoli, liste, citazioni, link, `codice`. I commenti restano testo, come si conviene a una discussion board vecchia scuola.

## Perché arancione

Il verde di Slashdot è sacro, ma copiarlo alla lettera sarebbe un costume di Halloween. Phlash usa ruggine, ambra e crema: stessa geometria da fine anni Novanta, altra temperatura.

Se stai leggendo questa storia dopo l’installazione, sei l’amministratore. Pubblicala, cambia il sondaggio, invita qualcuno a registrarsi e — soprattutto — **scrivi**. Un CMS senza voci è solo uno scheletro HTML.

Buona caccia alle notizie.
MD;
            $insStory = $pdo->prepare(
                'INSERT INTO stories (user_id, topic_id, title, slug, dept, body, source_url, status, score, comment_count, views, created_at, published_at)
                 VALUES (?, ?, ?, ?, ?, ?, NULL, ?, 3, 1, 0, NOW(), NOW())'
            );
            $insStory->execute([
                $adminId,
                $topicTech,
                'Benvenuti su Phlash: Slashdot incontra Pligg, in arancione',
                'benvenuti-su-phlash',
                'hello-world',
                $body,
                'published',
            ]);
            $storyId = (int) $pdo->lastInsertId();

            $insC = $pdo->prepare(
                'INSERT INTO comments (story_id, user_id, parent_id, author_name, body, score, ip_hash, status, created_at)
                 VALUES (?, NULL, NULL, ?, ?, 2, ?, ?, NOW())'
            );
            $insC->execute([
                $storyId,
                '',
                "Primo!",
                hash('sha256', 'install'),
                'visible',
            ]);

            $pdo->exec("INSERT INTO polls (question, is_active, created_at) VALUES ('Da dove vieni, lettore di Phlash?', 1, NOW())");
            $pollId = (int) $pdo->lastInsertId();
            $insO = $pdo->prepare('INSERT INTO poll_options (poll_id, label, votes) VALUES (?,?,0)');
            foreach (['Slashdot classico', 'Pligg / Digg era d’oro', 'Forum e blog anni 2000', 'Sono nato ieri, spiegatemi tutto'] as $lab) {
                $insO->execute([$pollId, $lab]);
            }

            file_put_contents($lock, date('c'));
            $step = 'done';
            $ok[] = 'Installazione completata.';
        } catch (Throwable $e) {
            $errors[] = 'Errore durante l’installazione: ' . $e->getMessage();
        }
    }
}

$guessBase = '';
if (!empty($_SERVER['HTTP_HOST'])) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $script = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if ($script === '/' || $script === '.') {
        $script = '';
    }
    $guessBase = $scheme . '://' . $_SERVER['HTTP_HOST'] . $script;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Installa Phlash</title>
<style>
body{margin:0;background:#1c1008;color:#1c1410;font:14px/1.45 Verdana,Geneva,sans-serif}
.wrap{max-width:560px;margin:40px auto;background:#fff8f0;border:3px solid #c2410c;padding:24px 28px}
h1{margin:0 0 6px;font:italic 28px Georgia,serif;color:#9a3412}
.tag{color:#7c2d12;font-size:12px;margin-bottom:18px}
label{display:block;font-size:12px;font-weight:bold;margin:12px 0 4px}
input[type=text],input[type=password],input[type=email]{width:100%;box-sizing:border-box;padding:7px 8px;border:1px solid #c4a484;background:#fff;font:14px Verdana,sans-serif}
.row{display:flex;gap:12px}
.row>div{flex:1}
button{margin-top:18px;background:#c2410c;color:#fff7ed;border:0;padding:8px 16px;font:bold 13px Verdana,sans-serif;cursor:pointer}
.err{background:#fee2e2;border:1px solid #b91c1c;padding:8px 10px;margin:0 0 12px;font-size:13px}
.ok{background:#ffedd5;border:1px solid #c2410c;padding:10px;margin:0 0 12px}
a{color:#c2410c}
.hint{font-size:11px;color:#6b5344;margin:2px 0 0}
</style>
</head>
<body>
<div class="wrap">
  <h1>Phlash</h1>
  <div class="tag">Installazione · PHP + MySQL · hosting shared</div>
  <?php foreach ($errors as $e): ?>
    <div class="err"><?= inst_h($e) ?></div>
  <?php endforeach; ?>

  <?php if ($step === 'done'): ?>
    <div class="ok">
      <p><strong>Phlash è pronto.</strong> Account admin creato. Per sicurezza cancella o rinomina <code>install.php</code> dal server.</p>
      <p><a href="index.php">Entra nel sito</a></p>
    </div>
  <?php else: ?>
    <p>Crea un database MySQL vuoto dal pannello hosting, poi compila i campi. Phlash scriverà <code>config.php</code> e le tabelle.</p>
    <form method="post">
      <label>Host MySQL</label>
      <input type="text" name="db_host" value="<?= inst_h($_POST['db_host'] ?? 'localhost') ?>" required>
      <div class="row">
        <div>
          <label>Nome database</label>
          <input type="text" name="db_name" value="<?= inst_h($_POST['db_name'] ?? '') ?>" required>
        </div>
        <div>
          <label>Utente MySQL</label>
          <input type="text" name="db_user" value="<?= inst_h($_POST['db_user'] ?? '') ?>" required>
        </div>
      </div>
      <label>Password MySQL</label>
      <input type="password" name="db_pass" value="">
      <label>URL pubblico del sito</label>
      <input type="text" name="base_url" value="<?= inst_h($_POST['base_url'] ?? $guessBase) ?>">
      <p class="hint">Lascia il valore proposto. Se il sito sta in una sottocartella, l’URL deve includerla (senza slash finale).</p>
      <label>Nome del sito</label>
      <input type="text" name="site_name" value="<?= inst_h($_POST['site_name'] ?? 'Phlash') ?>">
      <div class="row">
        <div>
          <label>Username admin</label>
          <input type="text" name="admin_user" value="<?= inst_h($_POST['admin_user'] ?? 'admin') ?>" required>
        </div>
        <div>
          <label>Email admin</label>
          <input type="email" name="admin_email" value="<?= inst_h($_POST['admin_email'] ?? '') ?>" required>
        </div>
      </div>
      <label>Password admin</label>
      <input type="password" name="admin_pass" required>
      <button type="submit">Installa Phlash</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>

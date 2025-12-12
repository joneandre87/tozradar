<?php
require_once '../../config.php';
requireSuperAdmin();

$message = '';
$messageType = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message .= "POST received! ";

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $featurePackage = $_POST['feature_package'] ?? '';

    if (empty($title)) {
        $message = "Error: Title is required!";
        $messageType = 'error';
    } else {
        try {
            // Generate slug
            $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $title)));

            // Check if exists
            $stmt = $pdo->prepare("SELECT id FROM features WHERE slug = ?");
            $stmt->execute([$slug]);

            if ($stmt->fetch()) {
                $message = "Error: A feature with this title already exists!";
                $messageType = 'error';
            } else {
                $parsed = [
                    'frontend' => '',
                    'backend' => '',
                    'sql' => ''
                ];

                $patterns = [
                    'frontend' => '/FRONTEND START(.*?)FRONTEND END/si',
                    'backend'  => '/BACKEND START(.*?)BACKEND END/si',
                    'sql'      => '/SQL START(.*?)SQL END/si'
                ];

                foreach ($patterns as $key => $pattern) {
                    if (preg_match($pattern, $featurePackage, $matches)) {
                        $parsed[$key] = trim($matches[1]);
                    }
                }

                if (empty($parsed['frontend']) && empty($parsed['backend']) && empty($parsed['sql'])) {
                    $parsed['frontend'] = trim($featurePackage);
                }

                // Insert into database
                $stmt = $pdo->prepare("INSERT INTO features (title, slug, description, frontend_code, backend_code, sql_code, created_by, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
                $result = $stmt->execute([$title, $slug, $description, $parsed['frontend'], $parsed['backend'], $parsed['sql'], $_SESSION['user_id']]);

                if ($result) {
                    $featureId = $pdo->lastInsertId();

                    // Execute SQL if provided
                    if (!empty($parsed['sql'])) {
                        try {
                            $statements = array_filter(array_map('trim', explode(';', $parsed['sql'])));
                            foreach ($statements as $sql) {
                                if (!empty($sql)) {
                                    $pdo->exec($sql);
                                }
                            }
                            $message .= "SQL executed | ";
                        } catch (Exception $e) {
                            $message .= "SQL Error: " . $e->getMessage() . " | ";
                        }
                    }

                    $message = "SUCCESS! Feature created: <a href='/feature.php?slug=$slug' style='color:#00ff88;'>View Feature</a>";
                    if (!empty($parsed['backend'])) {
                        $message .= " | <a href='/feature_backend.php?slug=$slug' style='color:#ffaa00;'>Settings Panel</a>";
                    }
                    $messageType = 'success';

                    // Clear form
                    $_POST = [];
                } else {
                    $message = "Database insert failed!";
                    $messageType = 'error';
                }
            }
        } catch (Exception $e) {
            $message = "Exception: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

$pageTitle = "Add New Feature";
include '../../header.php';
?>

<style>
.debug-box {
    background: rgba(255, 170, 0, 0.2);
    border: 2px solid #ffaa00;
    padding: 15px;
    margin: 20px 0;
    border-radius: 8px;
    font-family: monospace;
}
.success-box {
    background: rgba(0, 255, 136, 0.2);
    border: 2px solid #00ff88;
    padding: 15px;
    margin: 20px 0;
    border-radius: 8px;
}
.error-box {
    background: rgba(255, 0, 51, 0.2);
    border: 2px solid #ff0033;
    padding: 15px;
    margin: 20px 0;
    border-radius: 8px;
}
</style>

<main class="main-content">
    <div class="container">
        <h1 class="page-title">Add New Feature</h1>
        <p class="page-subtitle">Opprett en ny funksjon ved å lime inn én komplett pakke fra AI</p>

        <?php if ($message): ?>
        <div class="<?php echo $messageType === 'error' ? 'error-box' : ($messageType === 'success' ? 'success-box' : 'debug-box'); ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="settings-card" style="background: var(--dark-surface); padding: 2rem; border-radius: 12px;">
            <form method="POST" id="simpleForm">
                <h3>1) Grunninfo</h3>
                <div class="form-group">
                    <label for="title">Navn på feature *</label>
                    <input type="text" id="title" name="title" class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                           placeholder="f.eks. Bluetooth Scanner">
                </div>

                <div class="form-group">
                    <label for="description">Kort beskrivelse</label>
                    <textarea id="description" name="description" class="form-control" rows="3"
                              placeholder="Hva gjør denne funksjonen?"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <hr style="border-color: var(--border-color); margin: 2rem 0;">

                <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <h3 style="margin: 0;">2) Lim inn alt i ett felt</h3>
                    <button type="button" id="copyPrompt" class="btn btn-secondary" style="background:#ff0000; border: 1px solid #ff4d4d;">
                        Trykk her for å kopiere melding du kan sende til AI
                    </button>
                </div>
                <p style="color: var(--text-secondary); margin: 0.5rem 0 1rem; font-size: 0.95rem;">
                    Lim inn <strong>frontend HTML</strong>, <strong>backend HTML</strong> (innstillinger) og <strong>SQL/database-kode</strong> i samme tekstfelt.
                    Koden fra AI må være komplett med <code>&lt;html&gt;</code>, <code>&lt;style&gt;</code> og <code>&lt;script&gt;</code> slik at funksjonen fungerer 100% ut av boksen.
                    Dersom funksjonen trenger API-nøkler eller ekstra info må AI be deg om det. Hvis funksjonen lenker til andre tillegg/features må den bruke vår slug-struktur slik databasen er satt opp.
                </p>

                <div class="form-group">
                    <label for="feature_package">Frontend + backend + SQL i samme blokk</label>
                    <textarea id="feature_package" name="feature_package" class="form-control code-editor" rows="20"
                              placeholder="FRONTEND START
...html, css, js for brukergrensesnitt...
FRONTEND END

BACKEND START
...html/php for admin-innstillinger...
BACKEND END

SQL START
CREATE TABLE ...
SQL END"><?php echo htmlspecialchars($_POST['feature_package'] ?? ''); ?></textarea>
                    <p style="color: var(--text-secondary); margin-top: 0.5rem; font-size: 0.9rem;">
                        Strukturen over gjør at vi automatisk plukker ut frontend-, backend- og SQL-delen. Mangler markørene, legger vi alt som frontend. Husk å inkludere felter som refererer til <code>slug</code> der det trengs for koblinger mellom features.
                    </p>
                </div>

BACKEND START
...html/php for admin-innstillinger...
BACKEND END

                <h3>3) Opprett</h3>
                <button type="submit" class="btn btn-primary btn-large btn-glow">
                    <i class="fas fa-plus"></i> Opprett funksjon
                </button>
            </form>
        </div>

        <div class="back-link">
            <a href="/admin/superadmin/features.php">← Back to Features</a>
        </div>
    </div>
</main>

<script>
const steps = document.querySelectorAll('.step');
const toStep2Btn = document.getElementById('toStep2');
const backToStep1Btn = document.getElementById('backToStep1');
const form = document.getElementById('simpleForm');

const slugify = (text) => text.toLowerCase().trim()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-');

function showStep(stepNumber) {
    steps.forEach(step => {
        step.style.display = step.getAttribute('data-step') === String(stepNumber) ? 'block' : 'none';
    });
}

toStep2Btn.addEventListener('click', () => {
    if (!form.reportValidity()) return;
    showStep(2);
});

backToStep1Btn.addEventListener('click', () => showStep(1));

const startStep = '<?php echo ($messageType === "success") ? "1" : ($_SERVER["REQUEST_METHOD"] === "POST" ? "2" : "1"); ?>';
showStep(startStep);

document.getElementById('copyPrompt').addEventListener('click', async () => {
    const title = document.getElementById('title').value.trim();
    const description = document.getElementById('description').value.trim();
    const slug = slugify(title || 'din-feature');

    const promptText = `Du er en utvikler som skal levere én komplett kodeblokk for en ny feature til TozRadar.

Feature-navn: ${title || '[mangler tittel]'}
Beskrivelse: ${description || '[mangler beskrivelse]'}
Slug (brukes i URL og koblinger): ${slug}

Slik fungerer systemet:
- Offentlig side: https://dittdomene/feature.php?slug=${slug}
- Admin/innstillinger: https://dittdomene/feature_backend.php?slug=${slug}
- Relasjoner mellom features gjøres via feltet slug i databasen (tabell "features" har feltene title, slug, description, frontend_code, backend_code, sql_code, created_by, is_active).
- Backend-kode skal bruke $pdo for databasekall og $_SESSION['user_id'] der det trengs.

Lever ALT i samme svar i formatet:
FRONTEND START
[fullstendig HTML med <style> og <script> som fungerer alene i vårt oppsett]
FRONTEND END

BACKEND START
[HTML/PHP for admin- og innstillingspanel som bruker eksisterende slug og $pdo]
BACKEND END

SQL START
[SQL for tabeller/prosedyrer. Bruk MySQL/InnoDB, legg til user_id, slug-felter og relasjoner til andre features via slug]
SQL END

Krav:
- Koden må være profesjonell, responsiv og ferdig til bruk uten ekstra filer.
- Hvis funksjonen trenger API-nøkler eller annen konfigurasjon må du spørre meg om det.
- Hvis funksjonen lenker til andre features/tillegg må du bruke slug slik databasen vår gjør (f.eks. foreign keys eller koblingstabeller som refererer til slug).
- Ikke legg ved PHP header/footer, bare innholdet som skal inn i databasen.
- Gi klare instruksjoner dersom jeg må fylle inn nøkler eller miljøvariabler før det fungerer.`;

    try {
        await navigator.clipboard.writeText(promptText);
        alert('Prompt kopiert! Lim den inn hos AI for å generere koden.');
    } catch (err) {
        console.error('Kunne ikke kopiere', err);
        alert('Klarte ikke å kopiere automatisk. Kopier manuelt fra teksten over.');
    }
});
console.log('Feature creation form loaded');

document.getElementById('copyPrompt').addEventListener('click', async () => {
    const promptText = `Du er en utvikler som skal levere én komplett kodeblokk for en ny feature til TozRadar.

Lever ALT i samme svar og bruk denne strukturen:
FRONTEND START
[fullstendig HTML med style + script som fungerer alene i vårt oppsett]
FRONTEND END

BACKEND START
[HTML/PHP for admin- og innstillingspanel som bruker $pdo og $_SESSION['user_id'] der det trengs]
BACKEND END

SQL START
[SQL for tabeller/prosedyrer. Bruk MySQL/InnoDB og legg til user_id, slug-felter og relasjoner til andre features via slug]
SQL END

Krav:
- Koden må være profesjonell, responsiv og ferdig til bruk uten ekstra filer.
- Hvis funksjonen trenger API-nøkler eller annen konfigurasjon må du spørre meg om det.
- Hvis funksjonen lenker til andre features/tillegg må du bruke slug slik databasen vår gjør.
- Ikke legg ved PHP header/footer, bare innholdet som skal inn i databasen.`;

    try {
        await navigator.clipboard.writeText(promptText);
        alert('Prompt kopiert! Lim den inn hos AI for å generere koden.');
    } catch (err) {
        console.error('Kunne ikke kopiere', err);
        alert('Klarte ikke å kopiere automatisk. Kopier manuelt fra teksten under.');
    }
});
</script>

<?php include '../../footer.php'; ?>

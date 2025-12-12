<?php
require_once '../../config.php';
requireSuperAdmin();

$message = '';
$messageType = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
                <div class="step" data-step="1">
                    <h3>Steg 1: Grunninfo</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 1rem;">Start med navn og beskrivelse før du går videre til koden.</p>
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

                    <div class="btn-group" style="justify-content: flex-end;">
                        <button type="button" class="btn btn-primary btn-large" id="toStep2">Neste: kode</button>
                    </div>
                </div>

                <div class="step" data-step="2" style="display:none;">
                    <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: center; flex-wrap: wrap;">
                        <h3 style="margin: 0;">Steg 2: Lim inn alt i ett felt</h3>
                        <button type="button" id="copyPrompt" class="btn btn-secondary" style="background:#ff0000; border: 1px solid #ff4d4d;">
                            Trykk her for å kopiere melding du kan sende til AI
                        </button>
                    </div>
                    <p style="color: var(--text-secondary); margin: 0.5rem 0 1rem; font-size: 0.95rem;">
                        Lim inn <strong>én komplett kodeblokk</strong> med frontend, backend og SQL i samme felt. All detaljert veiledning ligger i meldingen som kopieres til AI.
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

                    <div class="btn-group" style="justify-content: space-between;">
                        <button type="button" class="btn btn-secondary" id="backToStep1">← Tilbake</button>
                        <button type="submit" class="btn btn-primary btn-large btn-glow">
                            <i class="fas fa-plus"></i> Opprett funksjon
                        </button>
                    </div>
                </div>
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
- Slug brukes som primary/foreign key i andre tabeller (f.eks. feltet feature_slug eller related_slug som refererer til features.slug).
- Backend-kode skal bruke $pdo for databasekall og $_SESSION['user_id'] der det trengs.
- Når koden sendes inn limer vi inn én samlet blokk i et tekstfelt, lagrer frontend_code, backend_code og sql_code i tabellen "features" og kjører SQL-delen direkte under lagringen.
- Du må skrive hvilke tabeller SQL-delen oppretter/oppdaterer og hvordan frontend/backend bruker dem.

Slik skal svaret ditt se ut (ALT i ÉN eneste kodeblokk – ikke flere separate blokker):
```
FRONTEND START
[fullstendig HTML/CSS/JS for offentlig side, rike beskrivelser av funksjonen, klare knapper/lenker, bruker slug=${slug} i lenker]
FRONTEND END

BACKEND START
[HTML/PHP for adminpanel som lagrer med $pdo og bruker eksisterende slug=${slug}; inkluderer fyldig UI med forklaringer]
BACKEND END

SQL START
[MySQL-skript for nødvendige tabeller/relasjoner, alltid med slug- og user_id-felter samt koblinger til andre features]
SQL END
```

Lever ALT i samme svar i formatet (én eneste kodeblokk med disse markørene):
FRONTEND START
[fullstendig HTML med <style> og <script> som fungerer alene i vårt oppsett, er innholdsrikt og lenker til /feature.php?slug=${slug} eller andre slug-baserte funksjoner]
FRONTEND END

BACKEND START
[HTML/PHP for admin- og innstillingspanel som bruker eksisterende slug, $pdo og lagrer trygt. Sørg for at UI-et er fyldig, forklar funksjonen og gir maksimal nytteverdi.]
BACKEND END

SQL START
[SQL for tabeller/prosedyrer. Bruk MySQL/InnoDB, legg til user_id, slug-felter og relasjoner til andre features via slug]
SQL END

Krav og forventninger:
 - Koden må være profesjonell, responsiv, innholdsrik og ferdig til bruk uten ekstra filer.
 - Beskriv hvilke miljøvariabler/API-nøkler som trengs før det fungerer. Stopp og spør om jeg har dem før du viser kode, forklar hvor kritiske de er, og om du kan levere en variant uten dem. Ikke bruk placeholders.
 - Ikke lever kode dersom informasjon eller nøkler mangler; be om alt som trengs først.
- Hvis funksjonen lenker til andre features/tillegg må du bruke slug slik databasen vår gjør (f.eks. foreign keys eller koblingstabeller som refererer til slug).
- Ikke legg ved PHP header/footer, bare innholdet som skal inn i databasen.
- Gi klare instrukser for eventuelle migrations/SQL-triggere som må kjøres.
- Svar med én eneste kodeblokk (en ```-blokk) som inneholder seksjonene FRONTEND/BACKEND/SQL uten ekstra kodeblokker.`;

    try {
        await navigator.clipboard.writeText(promptText);
        alert('Prompt kopiert! Lim den inn hos AI for å generere koden.');
    } catch (err) {
        console.error('Kunne ikke kopiere', err);
        alert('Klarte ikke å kopiere automatisk. Kopier manuelt fra teksten over.');
    }
});
</script>

<?php include '../../footer.php'; ?>

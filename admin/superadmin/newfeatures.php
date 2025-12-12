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
        
            
   <div class="debug-box" style="visibility:hidden;">
                        <strong>Hvordan systemet henger sammen:</strong>
                        <ul>
                            <li>Offentlig side: <code>https://dittdomene/feature.php?slug={slug}</code></li>
                            <li>Admin/innstillinger: <code>https://dittdomene/feature_backend.php?slug={slug}</code></li>
                            <li>Database: tabellen <code>features</code> med feltene title, slug, description, frontend_code, backend_code, sql_code, created_by, is_active.</li>
                            <li>Slug brukes som nøkkel for å koble mot andre features (f.eks. feltet <code>related_slug</code> i egne tabeller som refererer til <code>features.slug</code>).</li>
                            <li>Backend-kode må bruke <code>$pdo</code> og <code>$_SESSION['user_id']</code> for trygg lagring og autentisering.</li>
                            <li>Hvis noe krever API-nøkler eller konfig, skal AI eksplisitt be om det.</li>
                        </ul>
                    </div>
                     <div class="debug-box" style="visibility:hidden;">
                        <strong>Eksempel på forventet pakke og lenking:</strong>
                        <ul>
                            <li>Bruk formatet:
                                <pre style="white-space: pre-wrap; background:#0f0f0f; padding:10px; border-radius:6px;">
FRONTEND START
[full HTML med &lt;style&gt; og &lt;script&gt;, knapper som peker til /feature.php?slug=${slug} eller andre slug-baserte lenker]
FRONTEND END

BACKEND START
[HTML/PHP for admin-innstillinger som lagrer via $pdo og holder på eksisterende slug]
BACKEND END

SQL START
[MySQL DDL/DML. Inkluder felt som "feature_slug" eller "related_slug" som refererer til features.slug]
SQL END
                                </pre>
                            </li>
                            <li>AI må beskrive hvilke miljøvariabler/API-nøkler som trengs før koden kan kjøres.</li>
                            <li>Slugs skal aldri regenereres; bruk den som allerede er definert fra tittelen.</li>
                        </ul>
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
- Offentlig side: https://dittdomene/feature.php?slug=geoip
- Admin/innstillinger: https://dittdomene/feature_backend.php?slug=geoip
- Relasjoner mellom features gjøres via feltet slug i databasen (tabell "features" har feltene title, slug, description, frontend_code, backend_code, sql_code, created_by, is_active).
- Slug brukes som primary/foreign key i andre tabeller (f.eks. feltet feature_slug eller related_slug som refererer til features.slug).
- Backend-kode skal bruke $pdo for databasekall og $_SESSION['user_id'] der det trengs.

Lever ALT i samme svar, sammekodeblokk  i formatet:
"
FRONTEND START
[fullstendig HTML med <style> og <script> som fungerer alene i vårt oppsett og lenker til /feature.php?slug=geoip eller andre slug-baserte funksjoner]
FRONTEND END

BACKEND START
[HTML/PHP for admin- og innstillingspanel som bruker eksisterende slug, $pdo og lagrer trygt]
BACKEND END

SQL START
[SQL for tabeller/prosedyrer. Bruk MySQL/InnoDB, legg til user_id, slug-felter og relasjoner til andre features via slug]
SQL END

"

ikke del opp disse i forskjelige kodeblokker.

Krav og forventninger:
- Koden må være profesjonell, responsiv og ferdig til bruk uten ekstra filer.
- Dersom det trengs miljøvariabler/API-nøkler fra tredjeparter som trengs før det fungerer, informer om dette og legg inn et felt for å legge dette til i backend panelet.
- Hvis funksjonen lenker til andre features/tillegg på siden må du bruke slug slik databasen vår gjør (f.eks. foreign keys eller koblingstabeller som refererer til slug).
- Ikke legg ved PHP header/footer, bare innholdet som skal inn i databasen.
- For frontend og backend kode skal du kun inkludere html kode, <style> tags og <script> tags. Ikke PHP kode.
- Gi klare instrukser for eventuelle migrations/SQL-triggere som må kjøres.
- ikke gjør noe med standard css som feks body, header osv. kun spesifiser custom css for de nye elementene du legger til. 
- Gi meg alle tre kodene for frontend, backend og sql. Altså i en lang kode/ tekst i 1 canvas.
`;

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

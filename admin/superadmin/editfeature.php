<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

require_once '../../config.php';
requireSuperAdmin();

$message = '';
$messageType = '';

// Get feature ID
$featureId = intval($_GET['id'] ?? 0);

if ($featureId === 0) {
    header("Location: /admin/superadmin/features.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $featurePackage = $_POST['feature_package'] ?? '';

    if (empty($title)) {
        $message = "Error: Title is required!";
        $messageType = 'error';
    } else {
        try {
            // Get current slug
            $stmt = $pdo->prepare("SELECT slug FROM features WHERE id = ?");
            $stmt->execute([$featureId]);
            $currentFeature = $stmt->fetch();
            
            if (!$currentFeature) {
                $message = "Error: Feature not found!";
                $messageType = 'error';
            } else {
                $slug = $currentFeature['slug'];
                
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

                // Update database
                $stmt = $pdo->prepare("UPDATE features SET title = ?, description = ?, frontend_code = ?, backend_code = ?, sql_code = ? WHERE id = ?");
                $result = $stmt->execute([$title, $description, $parsed['frontend'], $parsed['backend'], $parsed['sql'], $featureId]);

                if ($result) {
                    // Execute SQL if provided and changed
                    if (!empty($parsed['sql'])) {
                        try {
                            $statements = array_filter(array_map('trim', explode(';', $parsed['sql'])));
                            foreach ($statements as $sql) {
                                if (!empty($sql)) {
                                    $pdo->exec($sql);
                                }
                            }
                        } catch (Exception $e) {
                            $message = "Warning: SQL execution error: " . $e->getMessage();
                            $messageType = 'warning';
                        }
                    }

                    $message = "✓ Feature updated successfully! <a href='/feature.php?slug=$slug' style='color:#00ff88;'>View Feature</a>";
                    if (!empty($parsed['backend'])) {
                        $message .= " | <a href='/feature_backend.php?slug=$slug' style='color:#ffaa00;'>View Settings</a>";
                    }
                    $messageType = 'success';
                } else {
                    $message = "Database update failed!";
                    $messageType = 'error';
                }
            }
        } catch (Exception $e) {
            $message = "Exception: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Fetch feature data
$stmt = $pdo->prepare("SELECT * FROM features WHERE id = ?");
$stmt->execute([$featureId]);
$feature = $stmt->fetch();

if (!$feature) {
    header("Location: /admin/superadmin/features.php");
    exit;
}

$featurePackageValue = $_POST['feature_package'] ?? "FRONTEND START\n" .
    ($feature['frontend_code'] ?? '') . "\nFRONTEND END\n\nBACKEND START\n" .
    ($feature['backend_code'] ?? '') . "\nBACKEND END\n\nSQL START\n" .
    ($feature['sql_code'] ?? '') . "\nSQL END";

$pageTitle = "Edit Feature";
include '../../header.php';
ob_end_flush();
?>

<style>
.message {
    padding: 15px;
    margin: 20px 0;
    border-radius: 8px;
    border: 2px solid;
}
.success {
    background: rgba(0, 255, 136, 0.2);
    border-color: #00ff88;
    color: #00ff88;
}
.error {
    background: rgba(255, 0, 51, 0.2);
    border-color: #ff0033;
    color: #ff0033;
}
.warning {
    background: rgba(255, 170, 0, 0.2);
    border-color: #ffaa00;
    color: #ffaa00;
}
.form-group {
    margin-bottom: 1.5rem;
}
.form-group label {
    display: block;
    color: #fff;
    font-weight: 600;
    margin-bottom: 0.5rem;
}
.form-control {
    width: 100%;
    padding: 0.75rem;
    background: #0a0a0a;
    border: 1px solid #333;
    border-radius: 8px;
    color: #fff;
    font-family: inherit;
    font-size: 14px;
}
.code-editor {
    font-family: 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.5;
}
.btn-group {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}
.btn-primary {
    background: linear-gradient(135deg, #ff0000, #cc0000);
    color: #fff;
    padding: 1rem 2rem;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 0, 0, 0.5);
}
.btn-secondary {
    background: #333;
    color: #fff;
    padding: 1rem 2rem;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.feature-info {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 2rem;
}
</style>

<main class="main-content">
    <div class="container">
        <h1 class="page-title"><i class="fas fa-edit"></i> Edit Feature</h1>
        <p class="page-subtitle">Gjør endringer med én samlet kodeblokk for frontend, backend og database</p>

        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="feature-info">
            <strong>Feature Slug:</strong> <code><?php echo htmlspecialchars($feature['slug']); ?></code><br>
            <strong>Created:</strong> <?php echo date('F j, Y, g:i a', strtotime($feature['created_at'])); ?><br>
            <strong>Status:</strong>
            <span style="color: <?php echo $feature['is_active'] ? '#00ff88' : '#ffaa00'; ?>">
                <?php echo $feature['is_active'] ? 'Active' : 'Inactive'; ?>
            </span>
        </div>

        <div class="settings-card" style="background: var(--dark-surface); padding: 2rem; border-radius: 12px;">
            <form method="POST">
                <h3 style="color: #ff0000; margin-bottom: 1rem;"><i class="fas fa-info-circle"></i> Grunninfo</h3>
                <div class="form-group">
                    <label for="title">Navn på feature *</label>
                    <input type="text" id="title" name="title" class="form-control" required
                           value="<?php echo htmlspecialchars($feature['title']); ?>">
                    <small style="color: #888;">Tittelen kan endres uten å påvirke eksisterende slug eller URL.</small>
                </div>

                <div class="form-group">
                    <label for="description">Kort beskrivelse</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($feature['description']); ?></textarea>
                </div>

                <hr style="border-color: var(--border-color); margin: 2rem 0;">

                <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <h3 style="margin: 0;">Frontend + backend + SQL i samme felt</h3>
                    <button type="button" id="copyPrompt" class="btn btn-secondary" style="background:#ff0000; border: 1px solid #ff4d4d;">
                        Trykk her for å kopiere melding du kan sende til AI
                    </button>
                </div>
                <p style="color: var(--text-secondary); margin: 0.5rem 0 1rem; font-size: 0.95rem;">
                    Lim inn alt i ett felt med markørene <code>FRONTEND START/END</code>, <code>BACKEND START/END</code> og <code>SQL START/END</code>.
                    Be AI om komplett, profesjonell kode (HTML, scripts og styles) som fungerer direkte. Dersom funksjonen trenger API-nøkler eller ekstra info skal AI spørre. 
                    Husk å bruke slug-koblinger hvis funksjonen lenker til andre features/tillegg slik databasen vår forventer.
                </p>

                <div class="form-group">
                    <label for="feature_package">Samlet kodeblokk</label>
                    <textarea id="feature_package" name="feature_package" class="form-control code-editor" rows="20"><?php echo htmlspecialchars($featurePackageValue); ?></textarea>
                    <small style="color: #ffaa00;">⚠️ SQL-delen kjøres når du lagrer. Sørg for at den er trygg.</small>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Lagre endringer
                    </button>
                    <a href="/admin/superadmin/features.php" class="btn-secondary">
                        <i class="fas fa-times"></i> Avbryt
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
console.log('Edit feature page loaded');
console.log('Feature ID:', <?php echo $featureId; ?>);
console.log('Feature Slug:', '<?php echo $feature['slug']; ?>');

document.getElementById('copyPrompt').addEventListener('click', async () => {
    const promptText = `Du er en utvikler som skal levere én komplett kodeblokk for en eksisterende feature til TozRadar.

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
        alert('Klarte ikke å kopiere automatisk. Kopier manuelt fra teksten over.');
    }
});
</script>

<?php include '../../footer.php'; ?>

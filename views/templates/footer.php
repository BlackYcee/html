<div class="debug-toggle-wrapper">
    <button id="toggle-console" class="btn btn-small" style="background: var(--cafe-700); color: var(--cafe-100);">
        Consola
    </button>
</div>

<div id="console-panel" class="console-panel collapsed">
    <div class="console-header">
        <span>Console</span>
        <button id="clear-console">Limpiar</button>
    </div>
    <div id="console-log" class="console-log"></div>
</div>

<script>
(function() {
    const console = {
        log: document.getElementById('console-log'),
        panel: document.getElementById('console-panel'),
        toggleBtn: document.getElementById('toggle-console'),
        clearBtn: document.getElementById('clear-console'),

        init() {
            this.toggleBtn?.addEventListener('click', () => this.toggle());
            this.clearBtn?.addEventListener('click', () => this.clear());
        },

        toggle() {
            this.panel?.classList.toggle('collapsed');
        },

        clear() {
            if (this.log) this.log.innerHTML = '';
        },

        add(message, type = 'info') {
            if (!this.log) return;

            const timestamp = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit', fractionalSecondDigits: 3 });
            const entry = document.createElement('div');
            entry.className = `console-entry ${type}`;
            entry.innerHTML = `<span class="console-time">${timestamp}</span><span class="console-msg">${message}</span>`;
            this.log.appendChild(entry);
            this.log.scrollTop = this.log.scrollHeight;
        },

        info(msg) { this.add(msg, 'info'); },
        success(msg) { this.add(msg, 'success'); },
        error(msg) { this.add(msg, 'error'); },
        warn(msg) { this.add(msg, 'warn'); }
    };

    window.DebugConsole = console;
    console.init();
})();
</script>

<?php
$db = Database::getInstance();
$dbStatus = $db->isConnected();
$dbError = $db->getError();
?>

<script>
DebugConsole.info('[APP] Inicializando aplicacion');
DebugConsole.info('[ENV] Modo debug: <?= Config::isDebug() ? 'ON' : 'OFF' ?>');
DebugConsole.info('[ENV] Entorno: <?= Config::get('app_env') ?>');
DebugConsole.info('[PHP] Version: <?= PHP_VERSION ?>');
DebugConsole.info('[PHP] Memory: <?= round(memory_get_usage() / 1024, 2) ?> KB');
DebugConsole.info('[PHP] Peak memory: <?= round(memory_get_peak_usage() / 1024, 2) ?> KB');
DebugConsole.info('[DB] Host: <?= Config::get('db_host') ?>:<?= Config::get('db_port') ?>');
DebugConsole.info('[DB] Database: <?= Config::get('db_name') ?>');
DebugConsole.info('[DB] User: <?= Config::get('db_user') ?>');
DebugConsole.info('[DB] Status: <?= $dbStatus ? 'CONNECTED' : 'DISCONNECTED' ?>');
<?php if (!$dbStatus): ?>
DebugConsole.error('[DB] Error: <?= addslashes($dbError ?? 'Unknown') ?>');
<?php endif; ?>
DebugConsole.info('[S3] Bucket: <?= Config::get('aws_s3_bucket') ?: 'No configurado' ?>');
DebugConsole.info('[S3] Region: <?= Config::get('aws_region') ?>');
DebugConsole.info('[S3] Folder: <?= Config::get('aws_s3_folder') ?>');
DebugConsole.info('[S3] SDK: <?= class_exists('Aws\S3\S3Client') ? 'AWS SDK v3' : (class_exists('S3Client') ? 'AWS SDK' : 'No disponible') ?>');
DebugConsole.info('[UPLOAD] Directory: <?= Config::get('upload_dir') ?>');
DebugConsole.info('[UPLOAD] Max size: <?= round(Config::get('max_file_size') / 1024) ?> KB');
DebugConsole.info('[UPLOAD] Allowed types: <?= implode(', ', Config::get('allowed_types')) ?>');
</script>

</body>
</html>